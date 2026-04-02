<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class StreakService
{
    public function recordStudyActivity(User $user, ?Carbon $activityDate = null): void
    {
        $activityDate = ($activityDate ?? now())->startOfDay();
        $lastStudyDate = $user->last_study_date ? Carbon::parse($user->last_study_date)->startOfDay() : null;

        if (! $lastStudyDate) {
            $currentStreak = 1;
        } elseif ($lastStudyDate->equalTo($activityDate)) {
            $currentStreak = $user->current_streak;
        } elseif ($lastStudyDate->copy()->addDay()->equalTo($activityDate)) {
            $currentStreak = $user->current_streak + 1;
        } else {
            $currentStreak = 1;
        }

        $longestStreak = max($user->longest_streak, $currentStreak);

        $user->forceFill([
            'current_streak' => $currentStreak,
            'longest_streak' => $longestStreak,
            'last_study_date' => $activityDate->toDateString(),
        ])->save();

        $user->studyStreak()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'current_streak' => $currentStreak,
                'longest_streak' => $longestStreak,
                'last_study_date' => $activityDate->toDateString(),
            ]
        );
    }
}
