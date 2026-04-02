<?php

namespace App\Services;

use App\Models\StudySession;

class SpacedRepetitionService
{
    public function initializeSchedule(StudySession $studySession): void
    {
        if ($studySession->next_review_at) {
            return;
        }

        $studySession->update([
            'next_review_at' => now()->addDay(),
        ]);
    }

    public function markReviewed(StudySession $studySession): void
    {
        $nextReviewCount = $studySession->review_count + 1;
        $intervals = [1, 3, 7, 14, 30];
        $daysUntilNextReview = $intervals[min($nextReviewCount - 1, count($intervals) - 1)];

        $studySession->update([
            'review_count' => $nextReviewCount,
            'last_reviewed_at' => now(),
            'next_review_at' => now()->addDays($daysUntilNextReview),
        ]);
    }
}
