<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            // Left empty so the model assigns M-00001, M-00002, ...
            'member_no' => null,
            'name' => fake()->name(),
            'phone' => fake()->numerify('9#######'),
            'email' => fake()->optional()->safeEmail(),
            'address' => null,
            'note' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
