<?php

namespace Database\Factories;

use App\Enums\CopyStatus;
use App\Models\Book;
use App\Models\BookCopy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookCopy>
 */
class BookCopyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            // Left empty so the model assigns INV-000001, INV-000002, ...
            'inventory_no' => null,
            'status' => CopyStatus::Available,
            'shelf' => fake()->optional()->bothify('?-##'),
            'acquired_at' => fake()->dateTimeBetween('-5 years'),
            'note' => null,
        ];
    }

    public function status(CopyStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
