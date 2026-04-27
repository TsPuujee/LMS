<?php

namespace Tests\Feature;

use App\Enums\CopyStatus;
use App\Exceptions\LoanException;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use App\Services\LoanService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanServiceTest extends TestCase
{
    use RefreshDatabase;

    private LoanService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LoanService::class);
        config(['library.loan_days' => 14, 'library.max_active_loans' => 2, 'library.renew_days' => 7]);
    }

    public function test_it_issues_an_available_copy(): void
    {
        $copy = BookCopy::factory()->create();
        $member = Member::factory()->create();
        $librarian = User::factory()->create();

        $loan = $this->service->issue($copy, $member, by: $librarian);

        $this->assertTrue($loan->isOpen());
        $this->assertSame($member->id, $loan->member_id);
        $this->assertSame($librarian->id, $loan->issued_by);
        $this->assertSame(today()->addDays(14)->toDateString(), $loan->due_at->toDateString());
        $this->assertSame(CopyStatus::Borrowed, $copy->fresh()->status);
    }

    public function test_it_uses_the_given_due_date(): void
    {
        $loan = $this->service->issue(
            BookCopy::factory()->create(),
            Member::factory()->create(),
            today()->addDays(3),
        );

        $this->assertSame(today()->addDays(3)->toDateString(), $loan->due_at->toDateString());
    }

    public function test_a_borrowed_copy_cannot_be_issued_again(): void
    {
        $copy = BookCopy::factory()->create();
        $this->service->issue($copy, Member::factory()->create());

        $this->expectException(LoanException::class);

        $this->service->issue($copy, Member::factory()->create());
    }

    public function test_lost_or_damaged_copies_cannot_be_issued(): void
    {
        $copy = BookCopy::factory()->status(CopyStatus::Lost)->create();

        try {
            $this->service->issue($copy, Member::factory()->create());
            $this->fail('A lost copy was issued.');
        } catch (LoanException $e) {
            $this->assertSame('book_copy_id', $e->field);
        }

        $this->assertSame(0, Loan::query()->count());
    }

    public function test_inactive_members_cannot_borrow(): void
    {
        $this->expectException(LoanException::class);

        $this->service->issue(BookCopy::factory()->create(), Member::factory()->inactive()->create());
    }

    public function test_members_cannot_exceed_the_active_loan_limit(): void
    {
        $member = Member::factory()->create();
        $this->service->issue(BookCopy::factory()->create(), $member);
        $this->service->issue(BookCopy::factory()->create(), $member);

        try {
            $this->service->issue(BookCopy::factory()->create(), $member);
            $this->fail('The loan limit was not enforced.');
        } catch (LoanException $e) {
            $this->assertSame('member_id', $e->field);
        }

        $this->assertSame(2, $member->openLoans()->count());
    }

    public function test_returning_frees_the_copy_and_the_member_slot(): void
    {
        $member = Member::factory()->create();
        $copy = BookCopy::factory()->create();
        $librarian = User::factory()->create();
        $loan = $this->service->issue($copy, $member);

        $this->service->return($loan, $librarian);

        $loan->refresh();
        $this->assertFalse($loan->isOpen());
        $this->assertSame($librarian->id, $loan->returned_by);
        $this->assertSame(CopyStatus::Available, $copy->fresh()->status);
        $this->assertSame(0, $member->openLoans()->count());

        // The same copy can be lent again.
        $this->service->issue($copy, Member::factory()->create());
        $this->assertSame(CopyStatus::Borrowed, $copy->fresh()->status);
    }

    public function test_a_copy_can_come_back_damaged(): void
    {
        $copy = BookCopy::factory()->create();
        $loan = $this->service->issue($copy, Member::factory()->create());

        $this->service->return($loan, copyStatus: CopyStatus::Damaged);

        $this->assertSame(CopyStatus::Damaged, $copy->fresh()->status);
    }

    public function test_a_loan_cannot_be_returned_twice(): void
    {
        $loan = $this->service->issue(BookCopy::factory()->create(), Member::factory()->create());
        $this->service->return($loan);

        $this->expectException(LoanException::class);

        $this->service->return($loan);
    }

    public function test_renewing_extends_from_the_due_date(): void
    {
        $loan = $this->service->issue(BookCopy::factory()->create(), Member::factory()->create(), today()->addDays(2));

        $loan = $this->service->renew($loan);

        $this->assertSame(today()->addDays(9)->toDateString(), $loan->due_at->toDateString());
        $this->assertSame(1, $loan->renewals);
    }

    public function test_renewing_an_overdue_loan_extends_from_today(): void
    {
        $loan = Loan::factory()->overdue(10)->create();

        $loan = $this->service->renew($loan, 5);

        $this->assertSame(today()->addDays(5)->toDateString(), $loan->due_at->toDateString());
        $this->assertFalse($loan->is_overdue);
    }

    public function test_overdue_scope_and_attributes(): void
    {
        $overdue = Loan::factory()->overdue(4)->create();
        Loan::factory()->create(); // due in the future
        Loan::factory()->create(['due_at' => today()]); // due today is not overdue
        Loan::factory()->returned()->create(); // past due date but returned

        $this->assertEquals([$overdue->id], Loan::query()->overdue()->pluck('id')->all());
        $this->assertTrue($overdue->fresh()->is_overdue);
        $this->assertSame(4, $overdue->fresh()->days_overdue);
    }

    public function test_the_database_rejects_two_open_loans_for_one_copy(): void
    {
        $loan = Loan::factory()->create();

        $this->expectException(QueryException::class);

        Loan::query()->create([
            'book_copy_id' => $loan->book_copy_id,
            'member_id' => Member::factory()->create()->id,
            'borrowed_at' => now(),
            'due_at' => today()->addDays(7),
        ]);
    }

    public function test_returned_loans_do_not_block_the_unique_index(): void
    {
        $copy = BookCopy::factory()->create();

        Loan::factory()->returned()->for($copy, 'copy')->create();
        Loan::factory()->returned()->for($copy, 'copy')->create();
        Loan::factory()->for($copy, 'copy')->create();

        $this->assertSame(3, $copy->loans()->count());
        $this->assertSame(1, $copy->loans()->open()->count());
    }
}
