<?php

namespace App\Services;

use App\Models\StudySession;

class StudySessionGenerationStatusService
{
    /**
     * @return array<string, mixed>
     */
    public function initializeGenerationStatus(StudySession $studySession): array
    {
        $metadata = is_array($studySession->metadata) ? $studySession->metadata : [];
        $nowIso = now()->toIso8601String();

        $metadata['generation_status'] = [
            'summary' => [
                'status' => 'queued',
                'updated_at' => $nowIso,
            ],
            'flashcards' => [
                'status' => 'queued',
                'updated_at' => $nowIso,
            ],
            'quiz' => [
                'status' => 'queued',
                'updated_at' => $nowIso,
            ],
        ];

        return $metadata;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function markSectionStatus(StudySession $studySession, string $section, string $status, array $context = []): void
    {
        $metadata = is_array($studySession->metadata) ? $studySession->metadata : [];
        $generationStatus = is_array($metadata['generation_status'] ?? null)
            ? $metadata['generation_status']
            : [];

        $entry = [
            'status' => $status,
            'updated_at' => now()->toIso8601String(),
        ];

        foreach ($context as $key => $value) {
            $entry[$key] = $value;
        }

        $generationStatus[$section] = $entry;
        $metadata['generation_status'] = $generationStatus;
        $studySession->metadata = $metadata;
        $studySession->save();
    }

    public function synchronizeOverallStatus(StudySession $studySession): string
    {
        $generationStatus = data_get($studySession->metadata, 'generation_status', []);

        $summaryStatus = (string) data_get($generationStatus, 'summary.status', 'queued');
        $flashcardsStatus = (string) data_get($generationStatus, 'flashcards.status', 'queued');
        $quizStatus = (string) data_get($generationStatus, 'quiz.status', 'queued');

        $statuses = [$summaryStatus, $flashcardsStatus, $quizStatus];
        $terminalStatuses = ['completed', 'failed'];
        $allTerminal = count(array_diff($statuses, $terminalStatuses)) === 0;

        $nextStatus = 'processing';
        if ($allTerminal) {
            $nextStatus = in_array('failed', $statuses, true) ? 'failed' : 'completed';
        }

        if ($studySession->status !== $nextStatus) {
            $studySession->status = $nextStatus;
            $studySession->save();
        }

        return $nextStatus;
    }
}
