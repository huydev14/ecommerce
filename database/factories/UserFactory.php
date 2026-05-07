<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Department;
use App\Models\Position;
use App\Models\Team;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-2 years', 'now');

        return [
            'name'  => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'       => static::$password ??= Hash::make('123'),
            'remember_token' => null,

            'department_id'   => Department::inRandomOrder()->first()?->id ?? 1,
            'position_id'     => Position::inRandomOrder()->first()?->id ?? 1,
            'team_id'     => Team::inRandomOrder()->first()?->id ?? 1,

            'employment_type' => fake()->randomElement([0, 0, 1]),
            'status' => fake()->randomElement([0, 0, 0, 1]),

            'start_date' => $startDate,
            'end_date'   => fake()->optional(0.1)->dateTimeBetween($startDate, '+1 year'),

            'gender'   => fake()->randomElement([0,1]),
            'birthday' => fake()->dateTimeBetween('-40 years', '-20 years')->format('Y-m-d'),
            'phone'    => fake()->phoneNumber(),
            'address'  => fake()->optional(0.3)->address(),

            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
