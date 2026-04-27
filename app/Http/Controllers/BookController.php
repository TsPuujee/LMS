<?php

namespace App\Http\Controllers;

use App\Enums\CopyStatus;
use App\Http\Requests\BookRequest;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BookController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'category' => $request->integer('category') ?: null,
            'availability' => $request->string('availability')->toString(),
        ];

        $books = Book::query()
            ->with('category:id,name')
            ->withCopyCounts()
            ->search($filters['search'])
            ->when($filters['category'], fn (Builder $q, int $id) => $q->where('category_id', $id))
            ->when($filters['availability'] === 'available', fn (Builder $q) => $q->whereHas('copies', fn (Builder $c) => $c->where('status', CopyStatus::Available)))
            ->when($filters['availability'] === 'unavailable', fn (Builder $q) => $q->whereDoesntHave('copies', fn (Builder $c) => $c->where('status', CopyStatus::Available)))
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('books/Index', [
            'books' => $books,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('books/Form', [
            'book' => null,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(BookRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $copies = (int) ($data['copies'] ?? 0);
        unset($data['copies']);

        $book = DB::transaction(function () use ($data, $copies): Book {
            $book = Book::query()->create($data);

            for ($i = 0; $i < $copies; $i++) {
                $book->copies()->create(['acquired_at' => today()]);
            }

            return $book;
        });

        $this->toast("«{$book->title}» ном бүртгэгдлээ.");

        return to_route('books.show', $book);
    }

    public function show(Book $book): Response
    {
        $book->load('category:id,name');

        return Inertia::render('books/Show', [
            'book' => $book,
            'copies' => $book->copies()
                ->with('openLoan.member:id,member_no,name,phone')
                ->orderBy('id')
                ->get(),
            'loans' => $book->loans()
                ->with(['copy:id,inventory_no', 'member:id,member_no,name'])
                ->latest('borrowed_at')
                ->latest('loans.id')
                ->limit(30)
                ->get(),
            'statusOptions' => CopyStatus::options(),
        ]);
    }

    public function edit(Book $book): Response
    {
        return Inertia::render('books/Form', [
            'book' => $book,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(BookRequest $request, Book $book): RedirectResponse
    {
        $book->update($request->validated());

        $this->toast('Номын мэдээлэл хадгалагдлаа.');

        return to_route('books.show', $book);
    }

    public function destroy(Book $book): RedirectResponse
    {
        if ($book->loans()->exists()) {
            $this->toast('Зээлийн түүхтэй номыг устгах боломжгүй. Хувиудыг нь «Хасагдсан» төлөвт шилжүүлнэ үү.', 'error');

            return back();
        }

        $title = $book->title;
        $book->delete();

        $this->toast("«{$title}» ном устгагдлаа.");

        return to_route('books.index');
    }
}
