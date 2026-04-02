<?php

namespace App\Jobs;

use App\Models\StudySession;
use App\Services\StudyMaterialGenerationService;
use App\Services\StudySessionGenerationStatusService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateStudySessionQuizJob implements ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;
    public int $timeout = 180;
    public array $backoff = [15, 60, 180];

    public function __construct(public int $studySessionId)
    {
    }

    public function handle(
        StudyMaterialGenerationService $generationService,
        StudySessionGenerationStatusService $statusService
    ): void {
        $studySession = StudySession::query()->find($this->studySessionId);
        if (! $studySession) {
            return;
        }

        $startedAt = microtime(true);
        Log::info('Quiz generation job started.', ['study_session_id' => $this->studySessionId]);
        $statusService->markSectionStatus($studySession, 'quiz', 'processing');

        try {
            $material = (string) ($studySession->extracted_text ?? $studySession->input_text ?? '');
            if ($material === '') {
                throw new \RuntimeException('Session material is empty for quiz generation.');
            }

            $preferences = data_get($studySession->metadata, 'generation_preferences.quiz', []);
            $generationService->generateQuizForSession($studySession, $material, is_array($preferences) ? $preferences : []);

            $latestQuiz = $studySession->fresh()->quizzes()->withCount('questions')->latest('id')->first();
            $statusService->markSectionStatus($studySession->fresh(), 'quiz', 'completed', [
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'items_generated' => (int) ($latestQuiz->questions_count ?? 0),
            ]);

            Log::info('Quiz generation job completed.', [
                'study_session_id' => $this->studySessionId,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Quiz generation job failed.', [
                'study_session_id' => $this->studySessionId,
                'error' => $exception->getMessage(),
            ]);

            $statusService->markSectionStatus($studySession->fresh(), 'quiz', 'failed', [
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
