<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'title' => rtrim(fake()->sentence(3), '.'),
            'author' => fake()->name(),
            'isbn' => fake()->unique()->isbn13(),
            'publisher' => fake()->optional()->company(),
            'published_year' => fake()->optional()->numberBetween(1950, (int) date('Y')),
            'language' => 'Монгол',
            'pages' => fake()->optional()->numberBetween(40, 900),
            'description' => fake()->optional()->paragraph(),
        ];
    }
}
