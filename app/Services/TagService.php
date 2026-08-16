<?php

namespace App\Services;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TagService
{
    /**
     * List the tags of a team, ordered by name.
     */
    public function listForTeam(int $teamId): LengthAwarePaginator
    {
        return Tag::query()
            ->where('team_id', $teamId)
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Create a new tag for the user's team.
     */
    public function create(array $data, User $user): Tag
    {
        return Tag::create([
            'team_id' => $user->team_id,
            ...$data,
        ]);
    }

    /**
     * Update an existing tag.
     */
    public function update(Tag $tag, array $data): bool
    {
        return $tag->update($data);
    }

    /**
     * Delete a tag.
     */
    public function delete(Tag $tag): ?bool
    {
        return $tag->delete();
    }
}
