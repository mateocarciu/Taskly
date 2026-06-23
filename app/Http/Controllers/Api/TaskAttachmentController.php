<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaskAttachmentController extends Controller
{

    public function show(Request $request, string $id)
    {
        $attachment = TaskAttachment::findOrFail($id);

        if ($attachment->team_id !== $request->user()->team_id) {
            abort(403);
        }

        if (!Storage::disk('local')->exists($attachment->path)) {
            abort(404);
        }

        return response()->file(Storage::disk('local')->path($attachment->path));

        // if we want the browser not to cache the file, we can use the following code instead of response()->file():
        // example if we sxitch teams we should not see the previous teams attachments in the browser cache
        // ->path($attachment->path), [
        //     'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
        //     'Pragma' => 'no-cache',
        //     'Expires' => '0',
        // ]);
    }

    public function destroy(Request $request, string $id)
    {
        $attachment = TaskAttachment::findOrFail($id);

        if ($attachment->team_id !== $request->user()->team_id) {
            abort(403);
        }

        Storage::disk('local')->delete($attachment->path);
        $attachment->delete();

        return response()->noContent();
    }
}
