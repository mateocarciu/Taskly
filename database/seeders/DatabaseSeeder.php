<?php

namespace Database\Seeders;

use App\Models\Task;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
        }

        foreach ($teams as $team) {
            $teamUsers = User::where('team_id', $team->id)->pluck('id');

            Task::factory(10)->create([
                'team_id' => $team->id,
                'created_by' => fn() => $teamUsers->random(),
            ]);
        }
    }
}
