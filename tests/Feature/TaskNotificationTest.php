<?php

use App\Models\Column;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use App\Notifications\TaskAssigned;
use App\Notifications\TaskCommented;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create(['team_id' => $this->team->id]);
    TeamMembership::create(['team_id' => $this->team->id, 'user_id' => $this->user->id]);

    $this->assignee = User::factory()->create(['team_id' => $this->team->id]);
    TeamMembership::create(['team_id' => $this->team->id, 'user_id' => $this->assignee->id]);

    $this->creator = User::factory()->create(['team_id' => $this->team->id]);
    TeamMembership::create(['team_id' => $this->team->id, 'user_id' => $this->creator->id]);

    $this->column = Column::create(['team_id' => $this->team->id, 'name' => 'To Do', 'order' => 1]);

    $this->makeTask = fn (array $overrides = []) => Task::factory()->create(array_merge([
        'team_id' => $this->team->id,
        'column_id' => $this->column->id,
        'created_by' => $this->creator->id,
        'assigned_to' => null,
    ], $overrides));
});

describe('assignment notifications', function () {
    test('notifies the assignee when a task is created', function () {
        Notification::fake();

        $this->actingAs($this->user)
            ->post(route('tasks.store'), [
                'title' => 'New task',
                'assigned_to' => $this->assignee->id,
            ])
            ->assertRedirect(route('tasks.index'));

        Notification::assertSentTo(
            $this->assignee,
            TaskAssigned::class,
            fn (TaskAssigned $notification, array $channels) => $channels === ['database', 'mail']
        );
        Notification::assertNotSentTo($this->user, TaskAssigned::class);
    });

    test('does not notify when the actor assigns the task to themselves', function () {
        Notification::fake();

        $this->actingAs($this->user)
            ->post(route('tasks.store'), [
                'title' => 'Self assigned task',
                'assigned_to' => $this->user->id,
            ])
            ->assertRedirect(route('tasks.index'));

        Notification::assertNothingSent();
    });

    test('notifies the new assignee when a task is reassigned', function () {
        Notification::fake();
        $task = ($this->makeTask)();

        $this->actingAs($this->user)
            ->put(route('tasks.update', $task), [
                'assigned_to' => $this->assignee->id,
            ])
            ->assertRedirect();

        Notification::assertSentTo($this->assignee, TaskAssigned::class);
    });

    test('does not notify the previous assignee when a task is reassigned', function () {
        Notification::fake();
        $previous = User::factory()->create(['team_id' => $this->team->id]);
        TeamMembership::create(['team_id' => $this->team->id, 'user_id' => $previous->id]);
        $task = ($this->makeTask)(['assigned_to' => $previous->id]);

        $this->actingAs($this->user)
            ->put(route('tasks.update', $task), [
                'assigned_to' => $this->assignee->id,
            ])
            ->assertRedirect();

        Notification::assertSentTo($this->assignee, TaskAssigned::class);
        Notification::assertNotSentTo($previous, TaskAssigned::class);
    });

    test('stores the assignment notification in the database', function () {
        $this->actingAs($this->user)
            ->post(route('tasks.store'), [
                'title' => 'Notify me',
                'assigned_to' => $this->assignee->id,
            ])
            ->assertRedirect(route('tasks.index'));

        $notification = $this->assignee->notifications()
            ->where('type', TaskAssigned::class)
            ->first();

        expect($notification)->not->toBeNull()
            ->and($notification->data['task_id'])->toBe(Task::first()->id)
            ->and($notification->data['task_title'])->toBe('Notify me')
            ->and($notification->data['assigner'])->toBe($this->user->name)
            ->and($this->user->notifications()->count())->toBe(0);
    });

    test('maps the assignment notification to a frontend alias', function () {
        $this->actingAs($this->user)
            ->post(route('tasks.store'), [
                'title' => 'Aliased task',
                'assigned_to' => $this->assignee->id,
            ])
            ->assertRedirect(route('tasks.index'));

        $this->actingAs($this->assignee)
            ->getJson(route('notifications.index'))
            ->assertJsonPath('data.0.type', 'task_assigned');
    });

    test('removes task notifications when the task is deleted', function () {
        $task = ($this->makeTask)(['assigned_to' => $this->assignee->id]);

        $this->actingAs($this->user)
            ->post(route('tasks.comments.store', $task), ['body' => 'On this task'])
            ->assertStatus(204);

        expect($this->assignee->notifications()->count())->toBe(1);

        $this->actingAs($this->user)
            ->delete(route('tasks.destroy', $task))
            ->assertRedirect();

        $this->assertDatabaseMissing('notifications', ['data->task_id' => $task->id]);
    });
});

describe('comment notifications', function () {
    test('notifies the assignee and the creator when someone comments', function () {
        Notification::fake();
        $task = ($this->makeTask)(['assigned_to' => $this->assignee->id]);

        $this->actingAs($this->user)
            ->post(route('tasks.comments.store', $task), [
                'body' => 'A useful comment',
            ])
            ->assertStatus(204);

        Notification::assertSentTo(
            $this->assignee,
            TaskCommented::class,
            fn (TaskCommented $notification, array $channels) => $channels === ['database', 'mail']
        );
        Notification::assertSentTo($this->creator, TaskCommented::class);
        Notification::assertNotSentTo($this->user, TaskCommented::class);
    });

    test('does not notify the author of their own comment', function () {
        Notification::fake();
        $task = ($this->makeTask)(['assigned_to' => $this->user->id]);

        $this->actingAs($this->user)
            ->post(route('tasks.comments.store', $task), [
                'body' => 'Commenting on my own task',
            ])
            ->assertStatus(204);

        Notification::assertNotSentTo($this->user, TaskCommented::class);
        Notification::assertSentTo($this->creator, TaskCommented::class);
    });

    test('notifies the parent comment author when replying', function () {
        Notification::fake();
        $task = ($this->makeTask)();

        $parent = $task->comments()->create([
            'user_id' => $this->assignee->id,
            'body' => 'Root comment',
        ]);

        $this->actingAs($this->user)
            ->post(route('tasks.comments.store', $task), [
                'body' => 'A reply to the root comment',
                'parent_id' => $parent->id,
            ])
            ->assertStatus(204);

        Notification::assertSentTo($this->assignee, TaskCommented::class);
        Notification::assertSentTo($this->creator, TaskCommented::class);
    });

    test('sends a single notification when a stakeholder holds several roles', function () {
        Notification::fake();
        $task = ($this->makeTask)(['assigned_to' => $this->creator->id]);

        $this->actingAs($this->user)
            ->post(route('tasks.comments.store', $task), [
                'body' => 'Only one notification for the creator',
            ])
            ->assertStatus(204);

        Notification::assertSentToTimes($this->creator, TaskCommented::class, 1);
    });

    test('stores the comment notification with its payload and alias', function () {
        $task = ($this->makeTask)(['assigned_to' => $this->assignee->id, 'title' => 'Commented task']);

        $this->actingAs($this->user)
            ->post(route('tasks.comments.store', $task), [
                'body' => 'Looks good to me',
            ])
            ->assertStatus(204);

        $response = $this->actingAs($this->assignee)
            ->getJson(route('notifications.index'));

        $response->assertJsonPath('data.0.type', 'task_commented');
        expect($response->json('data.0.data.task_id'))->toBe($task->id)
            ->and($response->json('data.0.data.task_title'))->toBe('Commented task')
            ->and($response->json('data.0.data.commenter'))->toBe($this->user->name)
            ->and($response->json('data.0.data.comment'))->toContain('Looks good to me');
    });
});
