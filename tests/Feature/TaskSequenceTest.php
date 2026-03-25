<?php

use App\Models\Column;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create(['team_id' => $this->team->id]);
    $this->columnA = Column::create(['team_id' => $this->team->id, 'name' => 'Col A', 'order' => 1]);
    $this->columnB = Column::create(['team_id' => $this->team->id, 'name' => 'Col B', 'order' => 2]);
});

describe('sequence updates', function () {
    test('moving a task to a different column records time spent', function () {
        // Create task exactly now
        $task = Task::factory()->create([
            'team_id' => $this->team->id,
            'column_id' => $this->columnA->id,
            'column_updated_at' => now(),
            'order' => 0,
        ]);

        // Fast forward 1 day (86400 seconds)
        $this->travel(1)->days();

        // Move task to column B
        $this->actingAs($this->user)
            ->put(route('tasks.sequence.update', $task), [
                'column_id' => $this->columnB->id,
                'order' => 0,
            ])
            ->assertRedirect();

        $task->refresh();

        // Verify task updated correctly
        $this->assertEquals($this->columnB->id, $task->column_id);
        $this->assertEquals(0, $task->order);

        // Verify time tracking
        $this->assertIsArray($task->time_spent_in_columns);
        $this->assertTrue(isset($task->time_spent_in_columns[$this->columnA->id]));
        $this->assertEquals(86400, $task->time_spent_in_columns[$this->columnA->id]);

        // Fast forward 2 days (172800 seconds)
        $this->travel(2)->days();

        // Move task back to column A
        $this->actingAs($this->user)
            ->put(route('tasks.sequence.update', $task), [
                'column_id' => $this->columnA->id,
                'order' => 0,
            ])
            ->assertRedirect();

        $task->refresh();

        // Verify time tracking for Column B
        $this->assertEquals(172800, $task->time_spent_in_columns[$this->columnB->id]);

        // Move task AGAIN (after 10 seconds) to ensure time accumulates rather than overwrites
        $this->travel(10)->seconds();

        $this->actingAs($this->user)
            ->put(route('tasks.sequence.update', $task), [
                'column_id' => $this->columnB->id,
                'order' => 0,
            ])
            ->assertRedirect();

        $task->refresh();

        // Column A should now have 86400 + 10 = 86410 seconds
        $this->assertEquals(86410, $task->time_spent_in_columns[$this->columnA->id]);
    });
});
