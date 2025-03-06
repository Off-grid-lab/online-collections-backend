<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Collection>
 */
class CollectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word,
            'text' => fake()->sentence,
        ];
    }

    public function published()
    {
        return $this->state(['published_at' => fake()->dateTime]);
    }

    public function featured()
    {
        return $this->state(['featured' => true]);
    }
}
