<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_categories_are_listed_with_book_counts(): void
    {
        $category = Category::factory()->create(['name' => 'Түүх']);
        Book::factory()->count(2)->for($category)->create();

        $this->get(route('categories.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('categories/Index')
                ->where('categories.0.name', 'Түүх')
                ->where('categories.0.books_count', 2));
    }

    public function test_a_category_can_be_created_and_updated(): void
    {
        $this->post(route('categories.store'), ['name' => 'Уран зохиол'])->assertRedirect(route('categories.index'));

        $category = Category::query()->sole();
        $this->put(route('categories.update', $category), ['name' => 'Яруу найраг', 'description' => 'Шүлэг'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Яруу найраг', $category->fresh()->name);
    }

    public function test_category_names_are_unique(): void
    {
        Category::factory()->create(['name' => 'Түүх']);

        $this->post(route('categories.store'), ['name' => 'Түүх'])->assertSessionHasErrors('name');
    }

    public function test_a_category_with_books_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        Book::factory()->for($category)->create();

        $this->delete(route('categories.destroy', $category))->assertRedirect(route('categories.index'));

        $this->assertModelExists($category);
    }

    public function test_an_empty_category_can_be_deleted(): void
    {
        $category = Category::factory()->create();

        $this->delete(route('categories.destroy', $category));

        $this->assertModelMissing($category);
    }
}
