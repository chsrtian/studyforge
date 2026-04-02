<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudySessionStoreRequest;
use App\Jobs\FinalizeStudySessionGenerationJob;
use App\Jobs\GenerateStudySessionFlashcardsJob;
use App\Jobs\GenerateStudySessionQuizJob;
use App\Jobs\GenerateStudySessionSummaryJob;
use App\Models\SessionInputSource;
use App\Models\StudySession;
use App\Services\ContentProcessor;
use App\Services\StudySessionGenerationStatusService;
use App\Services\TagService;
use Illuminate\Bus\Batch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudySessionController extends Controller
{
    private const MIN_GENERATED_ITEMS = 15;
    private const MAX_GENERATED_ITEMS = 50;

    public function __construct(
        protected TagService $tagService,
        protected StudySessionGenerationStatusService $generationStatusService
    ) {
    }

    public function create()
    {
        return view('study_sessions.create');
    }

    public function store(StudySessionStoreRequest $request, ContentProcessor $contentProcessor): RedirectResponse
    {
        $session = null;
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $sourceType = $validated['input_source_type'];
            $generationPreferences = $this->buildGenerationPreferences($validated);

            $extractedText = $contentProcessor->processAndExtract(
                $sourceType,
                $validated['input_text'] ?? null,
                $request->file('pdf_file')
            );

            $session = StudySession::create([
                'user_id' => Auth::id(),
                'title' => $validated['title'],
                'input_text' => $sourceType === 'text' ? ($validated['input_text'] ?? null) : null,
                'input_source_type' => $sourceType,
                'extracted_text' => $extractedText,
                'metadata' => [
                    'generation_preferences' => $generationPreferences,
                ],
                'status' => 'pending',
            ]);

            if ($sourceType === 'pdf' && $request->hasFile('pdf_file')) {
                $file = $request->file('pdf_file');
                $path = $file->store('session_pdfs', 'local');

                SessionInputSource::create([
                    'study_session_id' => $session->id,
                    'source_type' => 'pdf',
                    'original_filename' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'extracted_text' => $extractedText,
                    'extraction_status' => 'success',
                    'file_size_bytes' => $file->getSize(),
                ]);
            } else {
                SessionInputSource::create([
                    'study_session_id' => $session->id,
                    'source_type' => 'text',
                    'extracted_text' => $extractedText,
                    'extraction_status' => 'success',
                    'file_size_bytes' => strlen($extractedText),
                ]);
            }

            DB::commit();

            if (! empty($validated['tags'])) {
                $this->tagService->syncTagsForSession($session, $validated['tags']);
            }

            $metadata = $this->generationStatusService->initializeGenerationStatus($session);
            $session->metadata = $metadata;
            $session->status = 'processing';
            $session->save();

            $sessionId = $session->id;
            $batch = Bus::batch([
                new GenerateStudySessionSummaryJob($sessionId),
                new GenerateStudySessionFlashcardsJob($sessionId),
                new GenerateStudySessionQuizJob($sessionId),
            ])
                ->name('study-session-generation-'.$sessionId)
                ->allowFailures()
                ->finally(function (Batch $batch) use ($sessionId): void {
                    FinalizeStudySessionGenerationJob::dispatch($sessionId, $batch->id);
                })
                ->dispatch();

            $metadata = is_array($session->metadata) ? $session->metadata : [];
            $metadata['generation_batch_id'] = $batch->id;
            $session->metadata = $metadata;
            $session->save();

            return redirect()
                ->route('study_sessions.show', $session->id)
                ->with('success', 'Session created. Generating study materials in the background...');
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('Session creation failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            if ($session instanceof StudySession) {
                $metadata = is_array($session->metadata) ? $session->metadata : [];
                $metadata['generation_outcome'] = 'failed';
                $metadata['generation_failure_reason'] = $e->getMessage();
                $metadata['generation_failed_at'] = now()->toIso8601String();
                $session->metadata = $metadata;
                $session->status = 'failed';
                $session->save();

                return redirect()
                    ->route('study_sessions.show', $session->id)
                    ->with('error', 'Session was created, but generation dispatch failed. Please retry from the session page.');
            }

            $safeMessage = 'Failed to create study session.';
            if (str_contains(strtolower($e->getMessage()), 'pdf') || str_contains(strtolower($e->getMessage()), 'study material')) {
                $safeMessage = $e->getMessage();
            }

            return back()->with('error', $safeMessage)->withInput();
        }
    }

    public function show(StudySession $studySession)
    {
        $this->authorize('view', $studySession);

        $this->normalizeLegacyProcessingStatus($studySession);
        $studySession->refresh();

        $studySession->load([
            'tags',
            'flashcards' => fn ($query) => $query->orderBy('order'),
            'quizzes.questions' => fn ($query) => $query->orderBy('order'),
        ]);

        $summary = $studySession->generatedOutputs()->where('type', 'summary')->first();

        return view('study_sessions.show', compact('studySession', 'summary'));
    }

    private function buildGenerationPreferences(array $validated): array
    {
        return [
            'flashcards' => [
                'difficulty' => $validated['flashcard_difficulty'] ?? 'average',
                'count' => (int) ($validated['flashcard_count'] ?? self::MIN_GENERATED_ITEMS),
            ],
            'quiz' => [
                'difficulty' => $validated['quiz_difficulty'] ?? 'average',
                'count' => (int) ($validated['quiz_count'] ?? self::MIN_GENERATED_ITEMS),
            ],
        ];
    }

    private function normalizeLegacyProcessingStatus(StudySession $studySession): void
    {
        if ($studySession->status !== 'processing') {
            return;
        }

        $generationStatus = data_get($studySession->metadata, 'generation_status', null);
        if (is_array($generationStatus) && ! empty($generationStatus)) {
            return;
        }

        $hasSummary = $studySession->generatedOutputs()->where('type', 'summary')->exists();
        $hasFlashcards = $studySession->flashcards()->exists();
        $hasQuiz = $studySession->quizzes()->whereHas('questions')->exists();

        if ($hasSummary || $hasFlashcards || $hasQuiz) {
            $studySession->status = 'completed';
            $studySession->save();
        }
    }
}
