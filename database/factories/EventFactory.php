<?php

namespace Database\Factories;

use App\Models\Calendar;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'calendar_id' => Calendar::factory(),
            'organizer_id' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'start_at' => fake()->dateTimeBetween('now', '+1 week'),
            'end_at' => fn (array $attributes) => fake()->dateTimeBetween($attributes['start_at'], '+2 week'),
            'timezone' => 'UTC',
            'has_video' => false,
            'status' => 'confirmed',
        ];
    }
}
