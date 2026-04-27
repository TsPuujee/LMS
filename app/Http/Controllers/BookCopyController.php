<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookCopyRequest;
use App\Models\Book;
use App\Models\BookCopy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class BookCopyController extends Controller
{
    public function store(BookCopyRequest $request, Book $book): RedirectResponse
    {
        $data = $request->safe()->except('quantity');
        $data['acquired_at'] ??= today();
        $quantity = max(1, (int) $request->validated('quantity', 1));

        DB::transaction(function () use ($book, $data, $quantity): void {
            for ($i = 0; $i < $quantity; $i++) {
                $book->copies()->create($data);
            }
        });

        $this->toast($quantity === 1 ? 'Шинэ хувь нэмэгдлээ.' : "{$quantity} хувь нэмэгдлээ.");

        return to_route('books.show', $book);
    }

    public function update(BookCopyRequest $request, BookCopy $copy): RedirectResponse
    {
        $copy->update($request->validated());

        $this->toast("«{$copy->inventory_no}» хувь хадгалагдлаа.");

        return to_route('books.show', $copy->book_id);
    }

    public function destroy(BookCopy $copy): RedirectResponse
    {
        if ($copy->loans()->exists()) {
            $this->toast('Зээлийн түүхтэй хувийг устгах боломжгүй. «Хасагдсан» төлөвт шилжүүлнэ үү.', 'error');

            return to_route('books.show', $copy->book_id);
        }

        $copy->delete();

        $this->toast("«{$copy->inventory_no}» хувь устгагдлаа.");

        return to_route('books.show', $copy->book_id);
    }
}
