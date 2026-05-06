<?php

namespace App\Http\Controllers;

use App\Http\Requests\TagStoreRequest;
use App\Http\Requests\TagUpdateRequest;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function index(Request $request): Response
    {
        $tags = Tag::where('team_id', $request->user()->team_id)
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        return Inertia::render('Tags', [
            'tags' => $tags,
        ]);
    }

    public function store(TagStoreRequest $request)
    {
        Tag::create([
            'team_id' => $request->user()->team_id,
            ...$request->validated(),
        ]);

        return to_route('tags.index');
    }

    public function update(TagUpdateRequest $request, Tag $tag)
    {
        $this->authorize('update', $tag);

        $tag->update($request->validated());

        return to_route('tags.index');
    }

    public function destroy(Request $request, Tag $tag)
    {
        $this->authorize('delete', $tag);

        $tag->delete();

        return to_route('tags.index');
    }
}
