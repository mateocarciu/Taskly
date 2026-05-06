<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;
    private User $user;
    private Task $task;

    #[Test]
    public function test_can_create_tag_for_team(): void
    {
        $this->team = Team::factory()->create();
        $this->user = User::factory()->create(['team_id' => $this->team->id]);

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
    }

    #[Test]
    public function test_can_list_team_tags(): void
    {
        $this->team = Team::factory()->create();
        $this->user = User::factory()->create(['team_id' => $this->team->id]);

        Tag::factory(3)->create(['team_id' => $this->team->id]);

        $this->actingAs($this->user)
            ->get('/tags')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tags')
                ->has('tags', 3)
            );
    }

    #[Test]
    public function test_can_attach_tags_to_task(): void
    {
        $this->team = Team::factory()->create();
        $this->user = User::factory()->create(['team_id' => $this->team->id]);
        $this->task = Task::factory()->create(['team_id' => $this->team->id]);

        $tags = Tag::factory(2)->create(['team_id' => $this->team->id]);

        $this->actingAs($this->user)
            ->putJson("/tasks/{$this->task->id}", [
                'title' => $this->task->title,
                'tag_ids' => $tags->pluck('id')->toArray(),
            ])
            ->assertStatus(302);

        $this->assertCount(2, $this->task->fresh()->tags);
    }

    #[Test]
    public function test_task_has_tags_in_resource(): void
    {
        $this->team = Team::factory()->create();
        $this->user = User::factory()->create(['team_id' => $this->team->id]);
        $this->task = Task::factory()->create(['team_id' => $this->team->id]);

        $tags = Tag::factory(2)->create(['team_id' => $this->team->id]);
        $this->task->tags()->attach($tags);

        $this->actingAs($this->user)
            ->getJson("/tasks/{$this->task->id}")
            ->assertStatus(200)
            ->assertJsonPath('tags.0.name', $tags[0]->name)
            ->assertJsonPath('tags.1.name', $tags[1]->name);
    }

    #[Test]
    public function test_cannot_attach_tags_from_different_team(): void
    {
        $this->team = Team::factory()->create();
        $this->user = User::factory()->create(['team_id' => $this->team->id]);
        $this->task = Task::factory()->create(['team_id' => $this->team->id]);

        $otherTeam = Team::factory()->create();
        $tag = Tag::factory()->create(['team_id' => $otherTeam->id]);

        $this->actingAs($this->user)
            ->putJson("/tasks/{$this->task->id}", [
                'title' => $this->task->title,
                'tag_ids' => [$tag->id],
            ])
            ->assertStatus(422);
    }
}
