<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GoogleCalendarEvent>
 */
class GoogleCalendarEventFactory extends Factory
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
            'google_calendar_id' => 'primary',
            'google_event_id' => Str::random(),
            'summary' => fake()->sentence(),
            'start' => fake()->dateTime(),
            'end' => fake()->dateTime(),
        ];
    }
}
