<?php

namespace Tests\Feature;

use App\Enums\CopyStatus;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LoanControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $librarian;

    protected function setUp(): void
    {
        parent::setUp();

        $this->librarian = User::factory()->create();
        $this->actingAs($this->librarian);
    }

    public function test_the_loans_page_shows_who_has_which_book(): void
    {
        $book = Book::factory()->create(['title' => 'Тунгалаг Тамир']);
        $copy = BookCopy::factory()->for($book)->create();
        $member = Member::factory()->create(['name' => 'Б. Батболд']);
        Loan::factory()->for($copy, 'copy')->for($member)->create();
        Loan::factory()->returned()->create();

        $this->get(route('loans.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('loans/Index')
                ->has('loans.data', 1)
                ->where('loans.data.0.member.name', 'Б. Батболд')
                ->where('loans.data.0.copy.book.title', 'Тунгалаг Тамир')
                ->where('counts.open', 1));
    }

    public function test_the_loans_page_can_show_only_overdue_loans(): void
    {
        Loan::factory()->overdue()->create();
        Loan::factory()->create();

        $this->get(route('loans.index', ['status' => 'overdue']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('loans.data', 1)
                ->where('loans.data.0.is_overdue', true)
                ->where('counts.overdue', 1));
    }

    public function test_the_loans_page_can_be_searched_by_member(): void
    {
        Loan::factory()->for(Member::factory()->state(['name' => 'Номин']))->create();
        Loan::factory()->for(Member::factory()->state(['name' => 'Ганбаатар']))->create();

        $this->get(route('loans.index', ['search' => 'Номин']))
            ->assertInertia(fn (Assert $page) => $page->has('loans.data', 1));
    }

    public function test_a_librarian_can_issue_a_loan(): void
    {
        $copy = BookCopy::factory()->create();
        $member = Member::factory()->create();

        $this->post(route('loans.store'), [
            'member_id' => $member->id,
            'book_copy_id' => $copy->id,
            'due_at' => today()->addDays(10)->toDateString(),
        ])->assertRedirect(route('loans.index'));

        $loan = Loan::query()->sole();
        $this->assertSame($this->librarian->id, $loan->issued_by);
        $this->assertSame(today()->addDays(10)->toDateString(), $loan->due_at->toDateString());
        $this->assertSame(CopyStatus::Borrowed, $copy->fresh()->status);
    }

    public function test_issuing_a_borrowed_copy_returns_a_validation_error(): void
    {
        $loan = Loan::factory()->create();

        $this->from(route('loans.create'))
            ->post(route('loans.store'), [
                'member_id' => Member::factory()->create()->id,
                'book_copy_id' => $loan->book_copy_id,
            ])
            ->assertRedirect(route('loans.create'))
            ->assertSessionHasErrors('book_copy_id');

        $this->assertSame(1, Loan::query()->count());
    }

    public function test_due_date_cannot_be_in_the_past(): void
    {
        $this->post(route('loans.store'), [
            'member_id' => Member::factory()->create()->id,
            'book_copy_id' => BookCopy::factory()->create()->id,
            'due_at' => today()->subDay()->toDateString(),
        ])->assertSessionHasErrors('due_at');
    }

    public function test_a_librarian_can_return_a_loan(): void
    {
        $loan = Loan::factory()->create();

        $this->from(route('loans.index'))
            ->post(route('loans.return-copy', $loan))
            ->assertRedirect(route('loans.index'))
            ->assertSessionHasNoErrors();

        $loan->refresh();
        $this->assertNotNull($loan->returned_at);
        $this->assertSame($this->librarian->id, $loan->returned_by);
        $this->assertSame(CopyStatus::Available, $loan->copy->status);
    }

    public function test_a_copy_can_be_returned_as_lost(): void
    {
        $loan = Loan::factory()->create();

        $this->post(route('loans.return-copy', $loan), ['status' => 'lost'])->assertSessionHasNoErrors();

        $this->assertSame(CopyStatus::Lost, $loan->copy->fresh()->status);
    }

    public function test_returned_status_cannot_be_borrowed(): void
    {
        $loan = Loan::factory()->create();

        $this->post(route('loans.return-copy', $loan), ['status' => 'borrowed'])->assertSessionHasErrors('status');

        $this->assertNull($loan->fresh()->returned_at);
    }

    public function test_a_librarian_can_renew_a_loan(): void
    {
        $loan = Loan::factory()->create(['due_at' => today()->addDays(2)]);

        $this->post(route('loans.renew', $loan), ['days' => 5])->assertSessionHasNoErrors();

        $this->assertSame(today()->addDays(7)->toDateString(), $loan->fresh()->due_at->toDateString());
    }

    public function test_history_lists_open_and_returned_loans(): void
    {
        Loan::factory()->create();
        Loan::factory()->returned()->count(2)->create();

        $this->get(route('loans.history'))
            ->assertInertia(fn (Assert $page) => $page->component('loans/History')->has('loans.data', 3));

        $this->get(route('loans.history', ['status' => 'returned']))
            ->assertInertia(fn (Assert $page) => $page->has('loans.data', 2));
    }

    public function test_create_page_can_preselect_member_and_copy(): void
    {
        $member = Member::factory()->create();
        $copy = BookCopy::factory()->create();

        $this->get(route('loans.create', ['member' => $member->id, 'copy' => $copy->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('loans/Create')
                ->where('member.id', $member->id)
                ->where('copy.id', $copy->id));
    }

    public function test_lookup_endpoints_return_json(): void
    {
        Member::factory()->create(['name' => 'Сарангэрэл']);
        $book = Book::factory()->create(['title' => 'Алхимич']);
        BookCopy::factory()->for($book)->create();

        $this->getJson(route('lookup.members', ['q' => 'Саран']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Сарангэрэл');

        $this->getJson(route('lookup.copies', ['q' => 'Алхим']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.book.title', 'Алхимич')
            ->assertJsonPath('0.status', 'available');
    }
}
