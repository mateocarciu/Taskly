<?php

namespace Database\Seeders;

use App\Models\Column;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $teams = Team::factory(3)->create();

        $owner = User::factory()->withoutTwoFactor()->create([
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'team_id' => $teams->first()->id,
            'role' => 'owner',
            'remember_token' => Str::random(10),
        ]);

        $admin = User::factory()->withoutTwoFactor()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'team_id' => $teams->first()->id,
            'role' => 'admin',
            'remember_token' => Str::random(10),
        ]);

        // Add owner and admin to all teams so they appear in member lists.
        foreach ($teams as $team) {
            TeamMembership::firstOrCreate(['team_id' => $team->id, 'user_id' => $owner->id]);
            TeamMembership::firstOrCreate(['team_id' => $team->id, 'user_id' => $admin->id]);
        }

        $usersPerTeam = [3, 2, 1];
        $userIndex = 1;

        foreach ($teams as $index => $team) {
            for ($i = 0; $i < $usersPerTeam[$index]; $i++) {
                $member = User::factory()->withoutTwoFactor()->create([
                    'email' => "test{$userIndex}@example.com",
                    'password' => Hash::make('password'),
                    'team_id' => $team->id,
                    'remember_token' => Str::random(10),
                ]);

                TeamMembership::create([
                    'team_id' => $team->id,
                    'user_id' => $member->id,
                ]);
                $userIndex++;
            }

            $todoColumn = Column::create([
                'team_id' => $team->id,
                'name' => 'To Do',
                'order' => 1,
                'type' => 'todo',
            ]);

            $progressColumn = Column::create([
                'team_id' => $team->id,
                'name' => 'In Progress',
                'order' => 2,
                'type' => 'in_progress',
            ]);

            $doneColumn = Column::create([
                'team_id' => $team->id,
                'name' => 'Done',
                'order' => 3,
                'type' => 'done',
            ]);

            $teamUsers = User::query()
                ->whereIn('role', ['owner', 'admin'])
                ->orWhereHas('teamMemberships', fn ($membershipQuery) => $membershipQuery->where('team_id', $team->id))
                ->pluck('id');

            $tasks = Task::factory(10)->create([
                'team_id' => $team->id,
                'created_by' => fn () => $teamUsers->random(),
                'assigned_to' => fn () => rand(0, 100) < 80 ? $teamUsers->random() : null,
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
