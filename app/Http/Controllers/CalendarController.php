<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventRespondRequest;
use App\Http\Requests\EventStoreRequest;
use App\Http\Requests\EventUpdateRequest;
use App\Http\Resources\EventIndexResource;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Services\CalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function __construct(private CalendarService $calendarService) {}

    public function index(Request $request): Response
    {
        $data = $this->calendarService->indexData(
            $request->user(),
            $request->query('start'),
            $request->query('end'),
        );

        return Inertia::render('Calendar/Index', [
            'events' => EventIndexResource::collection($data['events'])->resolve($request),
            'teamMembers' => $data['teamMembers'],
            'view' => $request->query('view'),
            'start' => $request->query('start'),
            'end' => $request->query('end'),
        ]);
    }

    public function show(Request $request, Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        return response()->json(
            EventResource::make($this->calendarService->show($event))->resolve($request)
        );
    }

    public function store(EventStoreRequest $request): RedirectResponse
    {
        $this->calendarService->create($request->validated(), $request->user());

        return back();
    }

    public function update(EventUpdateRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $this->calendarService->update($event, $request->validated());

        return back();
    }

    public function destroy(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        $this->calendarService->hide($event, $request->user());

        return back();
    }

    public function respond(EventRespondRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('respond', $event);

        $this->calendarService->respond($event, $request->user(), $request->validated('response'));

        return back();
    }

    public function room(Request $request, Event $event): Response
    {
        $this->authorize('room', $event);

        return Inertia::render('Calendar/Room', [
            'event' => EventResource::make($event->load('organizer:id,name'))->resolve($request),
            'jitsiDomain' => config('services.jitsi.domain'),
            'jwt' => $this->calendarService->generateJitsiToken(
                $request->user(),
                $event->room_name,
                $request->user()->id === $event->organizer_id
            ),
        ]);
    }
}
