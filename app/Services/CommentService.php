<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CommentService
{
    /**
     * List the root comments of a task, with their replies.
     *
     * @return Collection<int, TaskComment>
     */
    public function listComments(Task $task): Collection
    {
        return $task->comments()
            ->whereNull('parent_id')
            ->with(['user:id,name', 'replies.user:id,name'])
            ->get();
    }

    /**
     * Store a comment or reply.
     */
    public function createComment(Task $task, array $data, User $user): TaskComment
    {
        return $task->comments()->create([
            'user_id'   => $user->id,
            'parent_id' => $data['parent_id'] ?? null,
            'body'      => $data['body'],
        ]);
    }

    /**
     * Update a comment.
     */
    public function updateComment(TaskComment $comment, array $data): bool
    {
        return $comment->update([
            'body' => $data['body'],
        ]);
    }

    /**
     * Delete a comment.
     */
    public function deleteComment(TaskComment $comment): ?bool
    {
        return $comment->delete();
    }
}
