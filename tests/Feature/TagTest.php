<?php

use App\Models\Tag;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create(['team_id' => $this->team->id]);
    $this->task = Task::factory()->create(['team_id' => $this->team->id]);
});

test('can create tag for team', function () {
    $this->actingAs($this->user)
        ->post('/tags', [
            'name' => 'Urgent',
            'color' => '#ff0000',
        ])
        ->assertRedirect('/tags');

    $this->assertDatabaseHas('tags', [
        'name' => 'Urgent',
        'color' => '#ff0000',
        'team_id' => $this->team->id,
    ]);
});

test('can list team tags', function () {
    Tag::factory(3)->create(['team_id' => $this->team->id]);

    $this->actingAs($this->user)
        ->get('/tags')
        ->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tags')
            ->has('tags', 3)
        );
});

test('can attach tags to task', function () {
    $tags = Tag::factory(2)->create(['team_id' => $this->team->id]);

    $this->actingAs($this->user)
        ->putJson("/tasks/{$this->task->id}", [
            'title' => $this->task->title,
            'tag_ids' => $tags->pluck('id')->toArray(),
        ])
        ->assertStatus(302);

    $this->assertCount(2, $this->task->fresh()->tags);
});

test('task has tags in resource', function () {
    $tags = Tag::factory(2)->create(['team_id' => $this->team->id]);
    $this->task->tags()->attach($tags);

    $this->actingAs($this->user)
        ->getJson("/tasks/{$this->task->id}")
        ->assertStatus(200)
        ->assertJsonPath('tags.0.name', $tags[0]->name)
        ->assertJsonPath('tags.1.name', $tags[1]->name);
});

test('cannot attach tags from different team', function () {
    $otherTeam = Team::factory()->create();
    $tag = Tag::factory()->create(['team_id' => $otherTeam->id]);

    $this->actingAs($this->user)
        ->putJson("/tasks/{$this->task->id}", [
            'title' => $this->task->title,
            'tag_ids' => [$tag->id],
        ])
        ->assertStatus(422);
});
