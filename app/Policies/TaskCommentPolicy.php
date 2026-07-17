<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TaskComment;

class TaskCommentPolicy
{
    /**
     * Determine whether the user can update the comment.
     */
    public function update(User $user, TaskComment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    /**
     * Determine whether the user can delete the comment.
     */
    public function delete(User $user, TaskComment $comment): bool
    {
        return $user->id === $comment->user_id;
    }
}
