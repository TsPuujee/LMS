<?php

namespace Tests\Feature;

use App\Enums\CopyStatus;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_books_list_shows_available_and_total_copies(): void
    {
        $book = Book::factory()->create(['title' => 'Алхимич']);
        BookCopy::factory()->count(2)->for($book)->create();
        Loan::factory()->for(BookCopy::factory()->for($book), 'copy')->create();

        $this->get(route('books.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('books/Index')
                ->has('books.data', 1)
                ->where('books.data.0.total_copies_count', 3)
                ->where('books.data.0.available_copies_count', 2));
    }

    public function test_books_can_be_searched_and_filtered(): void
    {
        $history = Category::factory()->create();
        Book::factory()->for($history)->create(['title' => 'Монголын нууц товчоо']);
        Book::factory()->create(['title' => 'Алхимич', 'author' => 'Пауло Коэльо']);

        $this->get(route('books.index', ['search' => 'Коэльо']))
            ->assertInertia(fn (Assert $page) => $page->has('books.data', 1)->where('books.data.0.title', 'Алхимич'));

        $this->get(route('books.index', ['category' => $history->id]))
            ->assertInertia(fn (Assert $page) => $page->has('books.data', 1)->where('books.data.0.title', 'Монголын нууц товчоо'));
    }

    public function test_books_can_be_filtered_by_availability(): void
    {
        $onShelf = BookCopy::factory()->create();
        $allOut = Loan::factory()->create();

        $this->get(route('books.index', ['availability' => 'available']))
            ->assertInertia(fn (Assert $page) => $page->has('books.data', 1)->where('books.data.0.id', $onShelf->book_id));

        $this->get(route('books.index', ['availability' => 'unavailable']))
            ->assertInertia(fn (Assert $page) => $page->has('books.data', 1)->where('books.data.0.id', $allOut->copy->book_id));
    }

    public function test_a_book_can_be_created_with_copies(): void
    {
        $category = Category::factory()->create();

        $response = $this->post(route('books.store'), [
            'category_id' => $category->id,
            'title' => 'Тунгалаг Тамир',
            'author' => 'Ч. Лодойдамба',
            'isbn' => '978-99929-0-123-4',
            'copies' => 3,
        ]);

        $book = Book::query()->sole();
        $response->assertRedirect(route('books.show', $book));
        $this->assertSame('9789992901234', $book->isbn);
        $this->assertSame(3, $book->copies()->count());

        // Inventory numbers are generated from the copy id.
        foreach ($book->copies as $copy) {
            $this->assertSame(sprintf('INV-%06d', $copy->id), $copy->inventory_no);
        }
    }

    public function test_book_validation(): void
    {
        $this->post(route('books.store'), ['isbn' => '123'])
            ->assertSessionHasErrors(['category_id', 'title', 'author', 'isbn']);
    }

    public function test_the_book_page_shows_copies_and_who_has_them(): void
    {
        $book = Book::factory()->create();
        BookCopy::factory()->for($book)->create();
        $loan = Loan::factory()->for(BookCopy::factory()->for($book), 'copy')->create();

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('books/Show')
                ->has('copies', 2)
                ->where('copies.1.open_loan.member.name', $loan->member->name)
                ->has('loans', 1));
    }

    public function test_a_book_can_be_updated(): void
    {
        $book = Book::factory()->create();

        $this->put(route('books.update', $book), [
            'category_id' => $book->category_id,
            'title' => 'Шинэ нэр',
            'author' => $book->author,
        ])->assertRedirect(route('books.show', $book));

        $this->assertSame('Шинэ нэр', $book->fresh()->title);
    }

    public function test_a_book_without_loans_can_be_deleted_with_its_copies(): void
    {
        $book = Book::factory()->create();
        BookCopy::factory()->count(2)->for($book)->create();

        $this->delete(route('books.destroy', $book))->assertRedirect(route('books.index'));

        $this->assertModelMissing($book);
        $this->assertSame(0, BookCopy::query()->count());
    }

    public function test_a_book_with_loan_history_cannot_be_deleted(): void
    {
        $loan = Loan::factory()->returned()->create();
        $book = $loan->copy->book;

        $this->delete(route('books.destroy', $book));

        $this->assertModelExists($book);
    }

    public function test_copies_can_be_added_in_bulk(): void
    {
        $book = Book::factory()->create();

        $this->post(route('books.copies.store', $book), ['quantity' => 4, 'shelf' => 'А-3'])
            ->assertRedirect(route('books.show', $book));

        $this->assertSame(4, $book->copies()->where('shelf', 'А-3')->count());
    }

    public function test_a_custom_inventory_number_only_adds_one_copy(): void
    {
        $book = Book::factory()->create();

        $this->post(route('books.copies.store', $book), ['inventory_no' => 'ЭХ-001', 'quantity' => 2])
            ->assertSessionHasErrors('quantity');

        $this->post(route('books.copies.store', $book), ['inventory_no' => 'ЭХ-001'])->assertSessionHasNoErrors();

        $this->assertSame('ЭХ-001', $book->copies()->sole()->inventory_no);
    }

    public function test_copy_status_can_be_changed_but_not_to_or_from_borrowed(): void
    {
        $copy = BookCopy::factory()->create();
        $this->put(route('copies.update', $copy), ['status' => 'damaged'])->assertSessionHasNoErrors();
        $this->assertSame(CopyStatus::Damaged, $copy->fresh()->status);

        $this->put(route('copies.update', $copy), ['status' => 'borrowed'])->assertSessionHasErrors('status');

        $loan = Loan::factory()->create();
        $this->put(route('copies.update', $loan->copy), ['status' => 'available'])->assertSessionHasErrors('status');
        $this->assertSame(CopyStatus::Borrowed, $loan->copy->fresh()->status);
    }

    public function test_a_copy_with_loans_cannot_be_deleted(): void
    {
        $loan = Loan::factory()->returned()->create();
        $unused = BookCopy::factory()->create();

        $this->delete(route('copies.destroy', $loan->copy));
        $this->delete(route('copies.destroy', $unused));

        $this->assertModelExists($loan->copy);
        $this->assertModelMissing($unused);
    }
}
