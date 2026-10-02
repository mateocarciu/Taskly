<?php

use App\Models\Calendar;
use App\Models\Event;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use App\Notifications\EventInvitation;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->admin()->create(['team_id' => $this->team->id]);
    TeamMembership::create(['team_id' => $this->team->id, 'user_id' => $this->user->id]);

    $this->otherMember = User::factory()->create(['team_id' => $this->team->id]);
    TeamMembership::create(['team_id' => $this->team->id, 'user_id' => $this->otherMember->id]);

    $this->calendar = Calendar::query()
        ->where('team_id', $this->team->id)
        ->where('owner_id', $this->user->id)
        ->firstOrFail();
});

test('can render calendar page with deferred events', function () {
    $event = Event::factory()->create([
        'calendar_id' => $this->calendar->id,
        'organizer_id' => $this->user->id,
        'start_at' => now()->addDay()->startOfHour(),
        'end_at' => now()->addDay()->startOfHour()->addHour(),
    ]);

    $this->actingAs($this->user)
        ->get(route('calendar.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Calendar/Index')
            ->has('teamMembers')
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->has('events', 1)
                ->where('events.0.id', $event->id)
            )
        );
});

test('can create an event and dispatch invitation to attendees', function () {
    Notification::fake();

    $payload = [
        'title' => 'Sprint Planning',
        'description' => 'Discuss upcoming sprint tasks.',
        'start_at' => now()->addDays(2)->startOfHour()->toIso8601String(),
        'end_at' => now()->addDays(2)->startOfHour()->addHour()->toIso8601String(),
        'timezone' => 'UTC',
        'has_video' => true,
        'attendee_ids' => [$this->otherMember->id],
    ];

    $this->actingAs($this->user)
        ->post(route('calendar.events.store'), $payload)
        ->assertRedirect();

    $this->assertDatabaseHas('events', [
        'title' => 'Sprint Planning',
        'has_video' => true,
        'organizer_id' => $this->user->id,
    ]);

    $event = Event::where('title', 'Sprint Planning')->firstOrFail();

    $this->assertNotEmpty($event->room_name);
    $this->assertDatabaseHas('event_attendees', [
        'event_id' => $event->id,
        'user_id' => $this->otherMember->id,
        'participation_status' => 'needs_action',
    ]);

    Notification::assertSentTo($this->otherMember, EventInvitation::class);
});

test('organizer can update an event', function () {
    $event = Event::factory()->create([
        'calendar_id' => $this->calendar->id,
        'organizer_id' => $this->user->id,
        'title' => 'Initial Title',
    ]);

    $this->actingAs($this->user)
        ->put(route('calendar.events.update', $event), [
            'title' => 'Updated Title',
            'start_at' => now()->addDays(3)->toIso8601String(),
            'end_at' => now()->addDays(3)->addHour()->toIso8601String(),
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('events', [
        'id' => $event->id,
        'title' => 'Updated Title',
    ]);
});

test('non-organizer cannot update an event', function () {
    $event = Event::factory()->create([
        'calendar_id' => $this->calendar->id,
        'organizer_id' => $this->user->id,
        'title' => 'Original Title',
    ]);

    $this->actingAs($this->otherMember)
        ->put(route('calendar.events.update', $event), [
            'title' => 'Hacked Title',
            'start_at' => now()->addDays(3)->toIso8601String(),
            'end_at' => now()->addDays(3)->addHour()->toIso8601String(),
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('events', [
        'id' => $event->id,
        'title' => 'Original Title',
    ]);
});

test('attendee can respond to an event invitation', function () {
    $event = Event::factory()->create([
        'calendar_id' => $this->calendar->id,
        'organizer_id' => $this->user->id,
    ]);
    $event->attendees()->attach($this->otherMember->id, ['participation_status' => 'needs_action']);

    $this->actingAs($this->otherMember)
        ->post(route('calendar.events.respond', ['event' => $event, 'response' => 'accepted']))
        ->assertRedirect();

    $this->assertDatabaseHas('event_attendees', [
        'event_id' => $event->id,
        'user_id' => $this->otherMember->id,
        'participation_status' => 'accepted',
    ]);
});

test('can access video meeting room when event has video enabled', function () {
    $event = Event::factory()->create([
        'calendar_id' => $this->calendar->id,
        'organizer_id' => $this->user->id,
        'has_video' => true,
        'room_name' => 'team-sync-room',
    ]);

    $this->actingAs($this->user)
        ->get(route('calendar.events.room', $event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Calendar/Room')
            ->has('event')
            ->has('jitsiDomain')
        );
});

test('cannot access video room when event has no video', function () {
    $event = Event::factory()->create([
        'calendar_id' => $this->calendar->id,
        'organizer_id' => $this->user->id,
        'has_video' => false,
    ]);

    $this->actingAs($this->user)
        ->get(route('calendar.events.room', $event))
        ->assertForbidden();
});

test('member cannot view event from another team', function () {
    $otherTeam = Team::factory()->create();
    $otherUser = User::factory()->create(['team_id' => $otherTeam->id]);
    TeamMembership::create(['team_id' => $otherTeam->id, 'user_id' => $otherUser->id]);

    $otherCalendar = Calendar::query()
        ->where('team_id', $otherTeam->id)
        ->where('owner_id', $otherUser->id)
        ->firstOrFail();

    $event = Event::factory()->create(['calendar_id' => $otherCalendar->id]);

    $this->actingAs($this->otherMember)
        ->getJson(route('calendar.events.show', $event))
        ->assertForbidden();
});
