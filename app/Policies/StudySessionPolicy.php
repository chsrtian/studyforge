<?php

namespace App\Policies;

use App\Models\StudySession;
use App\Models\User;

class StudySessionPolicy
{
    public function view(User $user, StudySession $studySession): bool
    {
        return $studySession->user_id === $user->id;
    }

    public function update(User $user, StudySession $studySession): bool
    {
        return $studySession->user_id === $user->id;
    }
}
