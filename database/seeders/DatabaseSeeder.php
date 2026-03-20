<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Models\Column;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $teams = Team::factory(3)->create(['count_completed_tasks' => 0]);

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
                'order' => 0,
            ]);
            $inProgressColumn = Column::create([
                'team_id' => $team->id,
                'name' => 'In Progress',
                'order' => 1,
            ]);
            $doneColumn = Column::create([
                'team_id' => $team->id,
                'name' => 'Done',
                'order' => 2,
            ]);

            $teamUsers = User::where('team_id', $team->id)->pluck('id');

            $tasks = Task::factory(10)->create([
                'team_id' => $team->id,
                'created_by' => fn() => $teamUsers->random(),
            ]);

            $todoOrder = 0;
            $progressOrder = 0;
            $doneOrder = 0;

            foreach ($tasks as $task) {
                $rand = rand(0, 2);
                if ($rand === 0) {
                    $task->update(['column_id' => $todoColumn->id, 'order' => $todoOrder++, 'completed' => false]);
                } elseif ($rand === 1) {
                    $task->update(['column_id' => $inProgressColumn->id, 'order' => $progressOrder++, 'completed' => false]);
                } else {
                    $task->update(['column_id' => $doneColumn->id, 'order' => $doneOrder++, 'completed' => true]);
                    DB::table('teams')->where('id', $team->id)->increment('count_completed_tasks');
                }
            }
        }
    }
}
