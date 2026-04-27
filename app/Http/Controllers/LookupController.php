<?php

namespace App\Http\Controllers;

use App\Enums\CopyStatus;
use App\Models\BookCopy;
use App\Models\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON search used by the "issue a loan" form.
 */
class LookupController extends Controller
{
    public function members(Request $request): JsonResponse
    {
        $term = $request->string('q')->trim()->toString();

        $members = Member::query()
            ->withCount('openLoans as open_loans_count')
            ->search($term)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'member_no', 'name', 'phone', 'is_active']);

        return response()->json($members);
    }

    public function copies(Request $request): JsonResponse
    {
        $term = $request->string('q')->trim()->toString();
        $like = '%'.$term.'%';

        $copies = BookCopy::query()
            ->with('book:id,title,author')
            ->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('inventory_no', 'like', $like)
                ->orWhereHas('book', fn (Builder $b) => $b
                    ->where('title', 'like', $like)
                    ->orWhere('author', 'like', $like)
                    ->orWhere('isbn', 'like', $like))))
            // Available copies first, then the rest so the librarian can see why.
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [CopyStatus::Available->value])
            ->orderBy('inventory_no')
            ->limit(15)
            ->get(['id', 'book_id', 'inventory_no', 'status', 'shelf']);

        return response()->json($copies);
    }
}
