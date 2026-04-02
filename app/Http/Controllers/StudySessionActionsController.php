<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudySessionRegenerateSectionRequest;
use App\Http\Requests\StudySessionUpdateTagsRequest;
use App\Jobs\RegenerateStudySectionJob;
use App\Models\StudySession;
use App\Services\SpacedRepetitionService;
use App\Services\StreakService;
use App\Services\QueueHealthService;
use App\Services\StudySessionGenerationStatusService;
use App\Services\TagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

class StudySessionActionsController extends Controller
{
    private const MIN_GENERATED_ITEMS = 15;
    private const MAX_GENERATED_ITEMS = 50;
    private const REGENERATION_LOCK_TTL_SECONDS = 900;

    public function __construct(
        protected StreakService $streakService,
        protected SpacedRepetitionService $spacedRepetitionService,
        protected TagService $tagService,
        protected StudySessionGenerationStatusService $generationStatusService,
        protected QueueHealthService $queueHealthService
    ) {
    }

    public function generationStatus(StudySession $studySession): JsonResponse
    {
        $this->authorize('view', $studySession);

        $this->normalizeLegacyProcessingStatus($studySession);
        $studySession->refresh();

        $this->generationStatusService->synchronizeOverallStatus($studySession);
        $studySession->refresh();

        $studySession->loadCount('flashcards');
        $latestQuiz = $studySession->quizzes()->withCount('questions')->latest('id')->first();

        $generationStatus = data_get($studySession->metadata, 'generation_status', []);
        $regenerationStatus = data_get($studySession->metadata, 'regeneration_status', []);

        $batchId = (string) data_get($studySession->metadata, 'generation_batch_id', '');
        $batch = $batchId !== '' ? Bus::findBatch($batchId) : null;
        $workerLikelyOffline = false;

        if ($studySession->status === 'processing') {
            if ($batch !== null && $batch->pendingJobs > 0) {
                $lastGenerationUpdateAt = $this->latestGenerationStatusUpdatedAt($studySession, $generationStatus);
                $workerLikelyOffline = $lastGenerationUpdateAt !== null
                    && $lastGenerationUpdateAt->lt(now()->subMinutes(2));
            }

            if ($batch !== null && $batch->finished()) {
                $this->generationStatusService->synchronizeOverallStatus($studySession);
                $studySession->refresh();
            } elseif ($batch === null && $this->isGenerationStale($studySession)) {
                $metadata = is_array($studySession->metadata) ? $studySession->metadata : [];
                $metadata['generation_outcome'] = 'failed';
                $metadata['generation_failure_reason'] = 'No active generation batch was found for this processing session.';
                $metadata['generation_failed_at'] = now()->toIso8601String();
                $studySession->metadata = $metadata;
                $studySession->status = 'failed';
                $studySession->save();
                $studySession->refresh();
            }
        }

        $queueHealth = $this->queueHealthService->snapshot();
        if (($queueHealth['status'] ?? '') === 'degraded') {
            $workerLikelyOffline = true;
        }

        $workerMessage = $workerLikelyOffline
            ? (($queueHealth['message'] ?? null) ?: 'Background jobs are queued but no worker appears to be processing them. Start a queue worker to continue generation.')
            : null;

        return response()->json([
            'success' => true,
            'session_status' => $studySession->status,
            'generation_status' => $generationStatus,
            'regeneration_status' => $regenerationStatus,
            'summary_ready' => $studySession->generatedOutputs()->where('type', 'summary')->exists(),
            'flashcards_count' => (int) $studySession->flashcards_count,
            'quiz_questions_count' => (int) ($latestQuiz->questions_count ?? 0),
            'worker_likely_offline' => $workerLikelyOffline,
            'worker_message' => $workerMessage,
            'queue_health' => $queueHealth,
        ]);
    }

    public function markReviewed(StudySession $studySession): RedirectResponse
    {
        $this->authorize('update', $studySession);

        $this->spacedRepetitionService->markReviewed($studySession);
        $this->streakService->recordStudyActivity(Auth::user());

        return back()->with('success', 'Review progress updated. Next reminder scheduled.');
    }

    public function toggleBookmark(StudySession $studySession): RedirectResponse
    {
        $this->authorize('update', $studySession);

        $studySession->update([
            'is_bookmarked' => ! $studySession->is_bookmarked,
        ]);

        return back()->with('success', $studySession->is_bookmarked ? 'Session bookmarked.' : 'Bookmark removed.');
    }

    public function togglePin(StudySession $studySession): RedirectResponse
    {
        $this->authorize('update', $studySession);

        $studySession->update([
            'is_pinned' => ! $studySession->is_pinned,
        ]);

        return back()->with('success', $studySession->is_pinned ? 'Session pinned.' : 'Session unpinned.');
    }

    public function updateTags(StudySessionUpdateTagsRequest $request, StudySession $studySession): RedirectResponse
    {
        $this->authorize('update', $studySession);

        $validated = $request->validated();
        $this->tagService->syncTagsForSession($studySession, $validated['tags'] ?? null);

        return back()->with('success', 'Tags updated successfully.');
    }

    public function regenerateSection(StudySessionRegenerateSectionRequest $request, StudySession $studySession): RedirectResponse
    {
        $this->authorize('update', $studySession);

        $validated = $request->validated();

        $material = $this->getSessionMaterial($studySession);
        if ($material === '') {
            return back()->with('error', 'Session material is missing, so regeneration cannot run.');
        }

        $preferences = is_array($studySession->metadata['generation_preferences'] ?? null)
            ? $studySession->metadata['generation_preferences']
            : $this->buildGenerationPreferencesFallback();

        $section = $validated['section'];
        $sectionLabel = $section === 'quiz' ? 'Quiz' : 'Flashcards';
        $sectionVerb = $section === 'quiz' ? 'is' : 'are';
        $difficulty = $validated['difficulty'];
        $count = (int) $validated['count'];

        if ($this->isUnchangedRegenerationRequest($studySession, $section, $difficulty, $count)) {
            return redirect()
                ->route('study_sessions.show', ['studySession' => $studySession->id, 'tab' => $section])
                ->with('success', $sectionLabel.' '.$sectionVerb.' already up to date for this difficulty and item count.');
        }

        $lockKey = $this->regenerationLockKey($studySession->id, $section);
        if (! Cache::add($lockKey, now()->toIso8601String(), self::REGENERATION_LOCK_TTL_SECONDS)) {
            return redirect()
                ->route('study_sessions.show', ['studySession' => $studySession->id, 'tab' => $section])
                ->with('error', ucfirst($section).' regeneration is already in progress. Please wait.');
        }

        $preferences[$section] = [
            'difficulty' => $difficulty,
            'count' => $count,
        ];

        $metadata = is_array($studySession->metadata) ? $studySession->metadata : [];
        $metadata['generation_preferences'] = $preferences;
        $regenerationStatus = is_array($metadata['regeneration_status'] ?? null)
            ? $metadata['regeneration_status']
            : [];

        $regenerationStatus[$section] = [
            'status' => 'queued',
            'updated_at' => now()->toIso8601String(),
        ];

        $metadata['regeneration_status'] = $regenerationStatus;
        $studySession->metadata = $metadata;
        $studySession->save();

        RegenerateStudySectionJob::dispatch(
            studySessionId: $studySession->id,
            section: $section,
            difficulty: $difficulty,
            count: $count,
            lockKey: $lockKey
        );

        return redirect()
            ->route('study_sessions.show', ['studySession' => $studySession->id, 'tab' => $section])
            ->with('success', ucfirst($section).' regeneration started. Refresh in a moment to see updates.');
    }

    private function buildGenerationPreferencesFallback(): array
    {
        return [
            'flashcards' => ['difficulty' => 'average', 'count' => self::MIN_GENERATED_ITEMS],
            'quiz' => ['difficulty' => 'average', 'count' => self::MIN_GENERATED_ITEMS],
        ];
    }

    private function getSessionMaterial(StudySession $studySession): string
    {
        return (string) ($studySession->extracted_text ?? $studySession->input_text ?? '');
    }

    private function regenerationLockKey(int $studySessionId, string $section): string
    {
        return "study-session:{$studySessionId}:regen:{$section}";
    }

    private function isUnchangedRegenerationRequest(StudySession $studySession, string $section, string $difficulty, int $count): bool
    {
        $savedPrefs = data_get($studySession->metadata, "generation_preferences.{$section}", []);
        $savedDifficulty = (string) ($savedPrefs['difficulty'] ?? '');
        $savedCount = (int) ($savedPrefs['count'] ?? 0);

        if ($savedDifficulty !== $difficulty || $savedCount !== $count) {
            return false;
        }

        if ($section === 'flashcards') {
            if ($studySession->flashcards_regenerated_at === null) {
                return false;
            }

            return $studySession->flashcards()->count() === $count;
        }

        if ($studySession->quiz_regenerated_at === null) {
            return false;
        }

        $quiz = $studySession->quizzes()->withCount('questions')->latest('id')->first();

        return $quiz !== null && (int) $quiz->questions_count === $count;
    }

    private function isGenerationStale(StudySession $studySession): bool
    {
        if ($studySession->created_at->gt(now()->subMinutes(2))) {
            return false;
        }

        $generationStatus = data_get($studySession->metadata, 'generation_status', []);
        if (! is_array($generationStatus)) {
            return true;
        }

        $summaryStatus = (string) data_get($generationStatus, 'summary.status', '');
        $flashcardsStatus = (string) data_get($generationStatus, 'flashcards.status', '');
        $quizStatus = (string) data_get($generationStatus, 'quiz.status', '');
        $statuses = [$summaryStatus, $flashcardsStatus, $quizStatus];

        foreach ($statuses as $status) {
            if (in_array($status, ['completed', 'failed'], true)) {
                return false;
            }
        }

        return true;
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

    private function latestGenerationStatusUpdatedAt(StudySession $studySession, array $generationStatus): ?Carbon
    {
        $timestamps = [];

        foreach (['summary', 'flashcards', 'quiz'] as $section) {
            $timestamp = data_get($generationStatus, $section.'.updated_at');
            if (! is_string($timestamp) || $timestamp === '') {
                continue;
            }

            try {
                $timestamps[] = Carbon::parse($timestamp);
            } catch (\Throwable) {
                continue;
            }
        }

        if ($timestamps === []) {
            return $studySession->created_at ?? null;
        }

        return collect($timestamps)->sortDesc()->first();
    }
}
