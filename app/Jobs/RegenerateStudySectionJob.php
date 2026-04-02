<?php

namespace App\Jobs;

use App\Models\StudySession;
use App\Services\StudyMaterialGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RegenerateStudySectionJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;
    public int $timeout = 180;
    public array $backoff = [15, 60, 180];

    public function __construct(
        public int $studySessionId,
        public string $section,
        public string $difficulty,
        public int $count,
        public string $lockKey
    ) {
    }

    public function handle(StudyMaterialGenerationService $generationService): void
    {
        $studySession = StudySession::query()->find($this->studySessionId);
        $startedAt = microtime(true);

        if (! $studySession) {
            Cache::forget($this->lockKey);
            return;
        }

        try {
            $this->updateRegenerationStatus($studySession, 'processing');

            $material = (string) ($studySession->extracted_text ?? $studySession->input_text ?? '');
            if ($material === '') {
                throw new \RuntimeException('Session material is empty.');
            }

            if ($this->section === 'flashcards') {
                $generationService->regenerateFlashcardsForSession($studySession, $material, [
                    'difficulty' => $this->difficulty,
                    'count' => $this->count,
                ]);
                $studySession->flashcards_regenerated_at = now();
            } else {
                $generationService->regenerateQuizForSession($studySession, $material, [
                    'difficulty' => $this->difficulty,
                    'count' => $this->count,
                ]);
                $studySession->quiz_regenerated_at = now();
            }

            $metadata = is_array($studySession->metadata) ? $studySession->metadata : [];
            $preferences = is_array($metadata['generation_preferences'] ?? null)
                ? $metadata['generation_preferences']
                : [];

            $preferences[$this->section] = [
                'difficulty' => $this->difficulty,
                'count' => $this->count,
            ];

            $metadata['generation_preferences'] = $preferences;
            $studySession->metadata = $metadata;
            $studySession->save();

            $this->updateRegenerationStatus($studySession->fresh(), 'completed');

            Log::info('Queued section regeneration completed.', [
                'session_id' => $studySession->id,
                'section' => $this->section,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Queued section regeneration failed.', [
                'session_id' => $studySession->id,
                'section' => $this->section,
                'error' => $exception->getMessage(),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);

            $this->updateRegenerationStatus($studySession, 'failed', $exception->getMessage());

            throw $exception;
        } finally {
            Cache::forget($this->lockKey);
        }
    }

    private function updateRegenerationStatus(StudySession $studySession, string $status, ?string $error = null): void
    {
        $metadata = is_array($studySession->metadata) ? $studySession->metadata : [];
        $statusBag = is_array($metadata['regeneration_status'] ?? null)
            ? $metadata['regeneration_status']
            : [];

        $entry = [
            'status' => $status,
            'updated_at' => now()->toIso8601String(),
        ];

        if ($error !== null && $error !== '') {
            $entry['error'] = $error;
        }

        $statusBag[$this->section] = $entry;
        $metadata['regeneration_status'] = $statusBag;
        $studySession->metadata = $metadata;
        $studySession->save();
    }
}
