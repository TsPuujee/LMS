<?php

namespace Database\Factories;

use App\Enums\CopyStatus;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Creates loans directly, bypassing LoanService. The copy's status is kept
 * in step with the loan in configure().
 *
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'book_copy_id' => BookCopy::factory(),
            'member_id' => Member::factory(),
            'issued_by' => null,
            'borrowed_at' => now()->subDays(3),
            'due_at' => today()->addDays(11),
            'returned_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Loan $loan): void {
            $loan->copy->update([
                'status' => $loan->returned_at === null ? CopyStatus::Borrowed : CopyStatus::Available,
            ]);
        });
    }

    public function overdue(int $days = 5): static
    {
        return $this->state([
            'borrowed_at' => now()->subDays(14 + $days),
            'due_at' => today()->subDays($days),
        ]);
    }

    public function returned(): static
    {
        return $this->state([
            'borrowed_at' => now()->subDays(20),
            'due_at' => today()->subDays(6),
            'returned_at' => now()->subDays(8),
        ]);
    }
}
