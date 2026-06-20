<?php

namespace Database\Factories;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => ActivityType::Sleep,
            'status' => ActivityStatus::Active,
            'started_at' => now(),
            'completes_at' => now()->addSeconds(ActivityType::Sleep->durationSeconds()),
        ];
    }

    /**
     * An activity whose completion time has already passed.
     */
    public function due(): static
    {
        return $this->state(fn () => [
            'started_at' => now()->subSeconds(ActivityType::Sleep->durationSeconds() + 60),
            'completes_at' => now()->subMinute(),
        ]);
    }
}
