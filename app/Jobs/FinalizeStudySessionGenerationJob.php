<?php

namespace App\Jobs;

use App\Models\StudySession;
use App\Services\SpacedRepetitionService;
use App\Services\StreakService;
use App\Services\StudySessionGenerationStatusService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FinalizeStudySessionGenerationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(
        public int $studySessionId,
        public ?string $batchId = null
    ) {
    }

    public function handle(
        StudySessionGenerationStatusService $statusService,
        SpacedRepetitionService $spacedRepetitionService,
        StreakService $streakService
    ): void {
        $studySession = StudySession::query()->with('user')->find($this->studySessionId);
        if (! $studySession) {
            return;
        }

        $status = $statusService->synchronizeOverallStatus($studySession);

        $metadata = is_array($studySession->metadata) ? $studySession->metadata : [];
        $metadata['generation_finalized_at'] = now()->toIso8601String();
        if ($this->batchId !== null && $this->batchId !== '') {
            $metadata['generation_batch_id'] = $this->batchId;
        }
        $metadata['generation_outcome'] = $status;
        $studySession->metadata = $metadata;
        $studySession->save();

        if ($status === 'completed') {
            $spacedRepetitionService->initializeSchedule($studySession);
            if ($studySession->user) {
                $streakService->recordStudyActivity($studySession->user);
            }
            return;
        }

        Log::warning('Study session generation finalized with non-complete status.', [
            'study_session_id' => $this->studySessionId,
            'batch_id' => $this->batchId,
            'status' => $status,
        ]);
    }
}
