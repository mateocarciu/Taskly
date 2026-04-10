<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Team;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Column;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $teams = Team::factory(3)->create();

        $usersPerTeam = [3, 2, 1];
        $userIndex = 1;

        foreach ($teams as $index => $team) {
            for ($i = 0; $i < $usersPerTeam[$index]; $i++) {
                User::factory()->withoutTwoFactor()->create([
                    'email' => "test{$userIndex}@example.com",
                    'password' => Hash::make('password'),
                    'team_id' => $team->id,
                    'remember_token' => Str::random(10),
                ]);
                $userIndex++;
            }

            $todoColumn = Column::create([
                'team_id' => $team->id,
                'name' => 'To Do',
                'order' => 1
            ]);

            $progressColumn = Column::create([
                'team_id' => $team->id,
                'name' => 'In Progress',
                'order' => 2
            ]);

            $doneColumn = Column::create([
                'team_id' => $team->id,
                'name' => 'Done',
                'order' => 3
            ]);

            $teamUsers = User::where('team_id', $team->id)->pluck('id');

            $tasks = Task::factory(10)->create([
                'team_id' => $team->id,
                'created_by' => fn() => $teamUsers->random(),
                'assigned_to' => fn() => rand(0, 100) < 80 ? $teamUsers->random() : null,
            ]);

            $todoOrder = 0;
            $progressOrder = 0;
            $doneOrder = 0;

            foreach ($tasks as $task) {
                // Randomly assign to a column
                $rand = rand(0, 2);
                if ($rand === 0) {
                    $task->update(['column_id' => $todoColumn->id, 'order' => $todoOrder++, 'column_updated_at' => now()]);
                } elseif ($rand === 1) {
                    $task->update(['column_id' => $progressColumn->id, 'order' => $progressOrder++, 'column_updated_at' => now()->subDays(rand(1, 5))]);
                } else {
                    $task->update(['column_id' => $doneColumn->id, 'order' => $doneOrder++, 'column_updated_at' => now()->subDays(rand(2, 10))]);
                }

                $commentCount = rand(0, 3);

                for ($commentIndex = 0; $commentIndex < $commentCount; $commentIndex++) {
                    TaskComment::create([
                        'task_id' => $task->id,
                        'user_id' => $teamUsers->random(),
                        'body' => fake()->sentences(rand(1, 2), true),
                    ]);
                }
            }
        }
    }
}
