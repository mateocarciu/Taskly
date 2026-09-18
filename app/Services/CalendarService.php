<?php

namespace App\Services;

use App\Models\Calendar;
use App\Models\Event;
use App\Models\User;
use App\Notifications\EventInvitation;
use Firebase\JWT\JWT;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CalendarService
{
    /**
     * The pivot table used to relate events and attendees.
     */
    private const ATTENDEE_TABLE = 'event_attendees';

    /**
     * Gather all the data needed to render the calendar page.
     *
     * @return array{events: EloquentCollection<int, Event>, teamMembers: EloquentCollection<int, User>}
     */
    public function indexData(User $user, ?string $start = null, ?string $end = null): array
    {
        $teamMembers = User::inTeam($user->team_id)
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        if (! $start || ! $end) {
            return [
                'events' => collect(),
                'teamMembers' => $teamMembers,
            ];
        }

        $events = Event::query()
            ->select(['id', 'calendar_id', 'organizer_id', 'title', 'start_at', 'end_at', 'has_video'])
            ->whereHas('calendar', fn ($query) => $query->where('team_id', $user->team_id))
            ->where(fn ($query) => $query->where('organizer_id', $user->id)
                ->orWhereHas('attendees', fn ($attendees) => $attendees
                    ->where(self::ATTENDEE_TABLE.'.user_id', $user->id)
                    ->where(self::ATTENDEE_TABLE.'.participation_status', 'accepted')))
            ->whereDoesntHave('attendees', fn ($attendees) => $attendees
                ->where(self::ATTENDEE_TABLE.'.user_id', $user->id)
                ->whereNotNull(self::ATTENDEE_TABLE.'.hidden_at'))
            ->where('start_at', '<=', $end)
            ->where('end_at', '>=', $start)
            ->orderBy('start_at')
            ->get();

        return [
            'events' => $events,
            'teamMembers' => $teamMembers,
        ];
    }

    /**
     * Load a single event with all its detail relations.
     */
    public function show(Event $event): Event
    {
        return $event->load(['organizer:id,name', 'attendees:id,name']);
    }

    /**
     * Get (or create) the user's personal calendar for their active team.
     */
    public function personalCalendar(User $user): Calendar
    {
        return Calendar::firstOrCreate(
            ['team_id' => $user->team_id, 'owner_id' => $user->id],
            ['name' => 'My Calendar', 'color' => null],
        );
    }

    /**
     * Create a new event on the user's calendar.
     */
    public function create(array $data, User $user): Event
    {
        $calendar = $this->personalCalendar($user);
        $hasVideo = (bool) ($data['has_video'] ?? false);

        $event = Event::create([
            'calendar_id' => $calendar->id,
            'organizer_id' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'start_at' => $data['start_at'],
            'end_at' => $data['end_at'],
            'timezone' => $data['timezone'] ?? 'UTC',
            'has_video' => $hasVideo,
            'room_name' => $this->generateRoomName($hasVideo, $data['title']),
        ]);

        $event->attendees()->attach(
            $this->teamAttendeeIds($user->team_id, $data['attendee_ids'] ?? [])
                ->mapWithKeys(fn (int $id) => [$id => ['participation_status' => 'needs_action']])
        );

        $event->load('attendees');
        $event->attendees->each->notify(new EventInvitation($event));

        return $event;
    }

    /**
     * Update an existing event while preserving responses.
     */
    public function update(Event $event, array $data): void
    {
        $hasVideo = (bool) ($data['has_video'] ?? false);

        $event->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'start_at' => $data['start_at'],
            'end_at' => $data['end_at'],
            'timezone' => $data['timezone'] ?? $event->timezone,
            'has_video' => $hasVideo,
            'room_name' => $this->generateRoomName($hasVideo, $data['title'], $event->room_name),
        ]);

        $existing = $event->attendees()->pluck(self::ATTENDEE_TABLE.'.participation_status', 'users.id');
        $existingHidden = $event->attendees()->pluck(self::ATTENDEE_TABLE.'.hidden_at', 'users.id');

        $attendeeIds = $this->teamAttendeeIds($event->calendar->team_id, $data['attendee_ids'] ?? []);

        $event->attendees()->sync(
            $attendeeIds->mapWithKeys(fn (int $id) => [
                $id => [
                    'participation_status' => $existing->get($id, 'needs_action'),
                    'hidden_at' => $existingHidden->get($id),
                ],
            ])
        );

        $addedIds = $attendeeIds->diff($existing->keys());

        if ($addedIds->isNotEmpty()) {
            $this->teamAttendees($event->calendar->team_id, $addedIds)
                ->each->notify(new EventInvitation($event));
        }

        $this->deleteInvitationNotifications($event, $existing->keys()->diff($attendeeIds));
    }

    /**
     * Remove the event from a user's calendar.
     */
    public function hide(Event $event, User $user): void
    {
        $event->attendees()->syncWithoutDetaching([
            $user->id => ['hidden_at' => now()],
        ]);

        if ($this->isAutomaticallyDeletable($event)) {
            $this->deletePermanently($event);
        }
    }

    /**
     * Record an attendee's response to an invitation and mark related
     * notifications as read.
     */
    public function respond(Event $event, User $user, string $response): void
    {
        $attendee = $event->attendees()->whereKey($user->id)->firstOrFail();

        $event->attendees()->updateExistingPivot($attendee->id, [
            'participation_status' => $response,
            'responded_at' => now(),
            'hidden_at' => null,
        ]);

        $this->markInvitationNotificationsResponded($user, $event, $response);

        if ($this->isAutomaticallyDeletable($event)) {
            $this->deletePermanently($event);
        }
    }

    /**
     * Generate a signed JWT for a Jitsi video room, or null when no secret
     * is configured.
     */
    public function generateJitsiToken(User $user, string $roomName, bool $isModerator = false): ?string
    {
        $secret = config('services.jitsi.secret');

        if (! $secret) {
            return null;
        }

        return JWT::encode([
            'aud' => 'jitsi',
            'iss' => config('services.jitsi.app_id'),
            'sub' => config('services.jitsi.app_id'),
            'room' => $roomName,
            'exp' => time() + 3600,
            'context' => [
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'id' => (string) $user->id,
                    'affiliation' => $isModerator ? 'owner' : 'member',
                ],
            ],
        ], $secret, 'HS256');
    }

    // -------------------------------------------------------------------------
    //  Private helpers
    // -------------------------------------------------------------------------

    /**
     * Generate a unique room name for a video event, reusing an existing one
     * when available.
     */
    private function generateRoomName(bool $hasVideo, string $title, ?string $existingRoom = null): ?string
    {
        if (! $hasVideo) {
            return null;
        }

        return $existingRoom ?? Str::slug($title).'-'.Str::random(10);
    }

    /**
     * Determine whether the event can be removed from the database: the organizer
     * hides it and no pending or visible accepted attendee remains.
     */
    private function isAutomaticallyDeletable(Event $event): bool
    {
        $organizerHidden = $event->attendees()
            ->where('users.id', $event->organizer_id)
            ->whereNotNull(self::ATTENDEE_TABLE.'.hidden_at')
            ->exists();

        if (! $organizerHidden) {
            return false;
        }

        $active = $event->attendees()
            ->whereIn(self::ATTENDEE_TABLE.'.participation_status', ['accepted', 'needs_action'])
            ->whereNull(self::ATTENDEE_TABLE.'.hidden_at')
            ->count();

        return $active === 0;
    }

    /**
     * Delete the event and its invitations once nobody needs it anymore.
     */
    private function deletePermanently(Event $event): void
    {
        $this->deleteInvitationNotifications($event, $event->attendees->pluck('id'));

        $event->delete();
    }

    /**
     * Remove the invitation notifications of an event for the given users.
     */
    private function deleteInvitationNotifications(Event $event, Collection $userIds): void
    {
        if ($userIds->isEmpty()) {
            return;
        }

        DB::table('notifications')
            ->where('type', EventInvitation::class)
            ->where('data->event_id', $event->id)
            ->whereIn('notifiable_id', $userIds)
            ->delete();
    }

    /**
     * Mark invitation notifications as responded for a specific user and event.
     */
    private function markInvitationNotificationsResponded(User $user, Event $event, string $response): void
    {
        $user->notifications()
            ->where('type', EventInvitation::class)
            ->where('data->event_id', $event->id)
            ->get()
            ->each(function (DatabaseNotification $notification) use ($response) {
                $notification->forceFill([
                    'data' => [...$notification->data, 'response' => $response],
                    'read_at' => now(),
                ])->save();
            });
    }

    /**
     * Restrict attendee ids to the members of the event's team.
     */
    private function teamAttendeeIds(int $teamId, array $attendeeIds): Collection
    {
        return User::inTeam($teamId)
            ->whereIn('id', $attendeeIds)
            ->pluck('id');
    }

    /**
     * Fetch the given team members as user models.
     */
    private function teamAttendees(int $teamId, Collection $ids): EloquentCollection
    {
        return User::inTeam($teamId)
            ->whereIn('id', $ids)
            ->get();
    }
}
