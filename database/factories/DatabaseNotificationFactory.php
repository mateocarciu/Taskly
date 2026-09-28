<?php

namespace Database\Factories;

use App\Notifications\EventInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

/**
 * @extends Factory<DatabaseNotification>
 */
class DatabaseNotificationFactory extends Factory
{
    protected $model = DatabaseNotification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'type' => EventInvitation::class,
            'data' => [
                'event_id' => fake()->numberBetween(1, 100),
                'title' => fake()->sentence(3),
                'description' => fake()->paragraph(),
                'start_time' => fake()->dateTime()->format('c'),
                'end_time' => fake()->dateTime()->format('c'),
                'inviter' => fake()->name(),
                'has_video' => false,
                'response' => 'needs_action',
            ],
            'read_at' => null,
        ];
    }

    /**
     * A notification the user already opened.
     */
    public function read(): static
    {
        return $this->state(fn () => ['read_at' => now()]);
    }

    /**
     * A notification belonging to the given notifiable.
     */
    public function forNotifiable(Model $notifiable): static
    {
        return $this->state(fn () => [
            'notifiable_id' => $notifiable->getKey(),
            'notifiable_type' => $notifiable->getMorphClass(),
        ]);
    }
}
