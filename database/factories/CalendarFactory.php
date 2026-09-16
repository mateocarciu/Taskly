<?php

namespace Database\Factories;

use App\Models\Calendar;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Calendar>
 */
class CalendarFactory extends Factory
{
    protected $model = Calendar::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'team_id' => null,
            'name' => fake()->words(2, true),
            'color' => fake()->hexColor(),
        ];
    }
}
