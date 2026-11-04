<?php

namespace App\Policies;

use App\Models\StudySession;
use App\Models\User;

class StudySessionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, StudySession $studySession): bool
    {
        return $user->id === $studySession->user_id;
    }
}
