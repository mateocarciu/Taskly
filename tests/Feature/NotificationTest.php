<?php

use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use Database\Factories\DatabaseNotificationFactory;
use Illuminate\Notifications\DatabaseNotification;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->admin()->create(['team_id' => $this->team->id]);
    TeamMembership::create(['team_id' => $this->team->id, 'user_id' => $this->user->id]);
    $this->otherUser = User::factory()->create(['team_id' => $this->team->id]);

    $this->notify = fn (User $user, int $count = 1) => DatabaseNotificationFactory::new()
        ->count($count)
        ->forNotifiable($user)
        ->create();
});

describe('index', function () {
    test('paginates notifications five per page', function () {
        ($this->notify)($this->user, 7);

        $response = $this->actingAs($this->user)->getJson(route('notifications.index'));

        $response->assertOk();
        $response->assertJsonPath('pagination.current_page', 1);
        $response->assertJsonPath('pagination.last_page', 2);
        $response->assertJsonPath('pagination.total', 7);
        $response->assertJsonPath('pagination.has_more', true);
        $this->assertCount(5, $response->json('data'));
    });

    test('returns the second page', function () {
        ($this->notify)($this->user, 7);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.index', ['page' => 2]));

        $response->assertOk();
        $response->assertJsonPath('pagination.current_page', 2);
        $response->assertJsonPath('pagination.has_more', false);
        $this->assertCount(2, $response->json('data'));
    });

    test('returns the most recent notifications first', function () {
        ($this->notify)($this->user, 3);

        $ids = $this->actingAs($this->user)
            ->getJson(route('notifications.index'))
            ->json('data.*.id');

        $expected = DatabaseNotification::query()
            ->latest()
            ->limit(3)
            ->pluck('id')
            ->all();

        expect($ids)->toEqualCanonicalizing($expected);
    });

    test('only returns the notifications of the authenticated user', function () {
        ($this->notify)($this->user);
        ($this->notify)($this->otherUser);

        $response = $this->actingAs($this->user)->getJson(route('notifications.index'));

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    });

    test('maps the notification type to a frontend alias', function () {
        ($this->notify)($this->user);

        $this->actingAs($this->user)
            ->getJson(route('notifications.index'))
            ->assertJsonPath('data.0.type', 'event_invitation');
    });

    test('is not accessible to guests', function () {
        $this->getJson(route('notifications.index'))->assertUnauthorized();
    });
});

describe('mark as read', function () {
    test('marks a single notification as read', function () {
        $notification = ($this->notify)($this->user)->first();

        $this->actingAs($this->user)
            ->post(route('notifications.mark-as-read', $notification->getKey()))
            ->assertRedirect();

        expect($notification->fresh()->read_at)->not->toBeNull();
    });

    test('cannot mark a notification of another user as read', function () {
        $notification = ($this->notify)($this->otherUser)->first();

        $this->actingAs($this->user)
            ->post(route('notifications.mark-as-read', $notification->getKey()))
            ->assertForbidden();

        expect($notification->fresh()->read_at)->toBeNull();
    });

    test('marks every notification as read', function () {
        ($this->notify)($this->user, 3);

        $this->actingAs($this->user)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        expect($this->user->unreadNotifications()->count())->toBe(0);
    });

    test('leaves the notifications of other users untouched', function () {
        ($this->notify)($this->user, 2);
        ($this->notify)($this->otherUser, 2);

        $this->actingAs($this->user)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        expect($this->user->unreadNotifications()->count())->toBe(0)
            ->and($this->otherUser->unreadNotifications()->count())->toBe(2);
    });
});
