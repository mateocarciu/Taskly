<?php

namespace App\Http\Controllers;

use App\Http\Requests\TagStoreRequest;
use App\Http\Requests\TagUpdateRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function __construct(private TagService $tagService) {}

    public function index(Request $request): Response
    {
        $tags = $this->tagService->listForTeam($request->user()->team_id);

        return Inertia::render('Tags', [
            'tags' => $tags->through(
                fn (Tag $tag) => (new TagResource($tag))->resolve($request)
            ),
        ]);
    }

    public function store(TagStoreRequest $request): RedirectResponse
    {
        $this->tagService->create($request->validated(), $request->user());

        return to_route('tags.index');
    }

    public function update(TagUpdateRequest $request, Tag $tag): RedirectResponse
    {
        $this->authorize('update', $tag);

        $this->tagService->update($tag, $request->validated());

        return to_route('tags.index');
    }

    public function destroy(Request $request, Tag $tag): RedirectResponse
    {
        $this->authorize('delete', $tag);

        $this->tagService->delete($tag);

        return to_route('tags.index');
    }
}
