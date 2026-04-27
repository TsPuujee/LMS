<?php

namespace App\Models;

use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $book_copy_id
 * @property int $member_id
 * @property int|null $issued_by
 * @property int|null $returned_by
 * @property Carbon $borrowed_at
 * @property Carbon $due_at
 * @property Carbon|null $returned_at
 * @property int $renewals
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read bool $is_overdue
 * @property-read int $days_overdue
 * @property-read BookCopy $copy
 * @property-read Member $member
 * @property-read User|null $issuer
 * @property-read User|null $receiver
 */
#[Fillable([
    'book_copy_id', 'member_id', 'issued_by', 'returned_by',
    'borrowed_at', 'due_at', 'returned_at', 'renewals', 'note',
])]
#[Hidden(['open_copy_id'])]
class Loan extends Model
{
    /** @use HasFactory<LoanFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $appends = ['is_overdue', 'days_overdue'];

    protected function casts(): array
    {
        return [
            'borrowed_at' => 'datetime:Y-m-d H:i',
            'due_at' => 'date:Y-m-d',
            'returned_at' => 'datetime:Y-m-d H:i',
            'renewals' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<BookCopy, $this>
     */
    public function copy(): BelongsTo
    {
        return $this->belongsTo(BookCopy::class, 'book_copy_id');
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function isOpen(): bool
    {
        return $this->returned_at === null;
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function isOverdue(): Attribute
    {
        return Attribute::get(fn (): bool => $this->isOpen() && $this->due_at->lt(today()));
    }

    /**
     * @return Attribute<int, never>
     */
    protected function daysOverdue(): Attribute
    {
        return Attribute::get(fn (): int => $this->is_overdue
            ? (int) $this->due_at->diffInDays(today())
            : 0);
    }

    /**
     * @param  Builder<Loan>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereNull('returned_at');
    }

    /**
     * @param  Builder<Loan>  $query
     */
    #[Scope]
    protected function returned(Builder $query): void
    {
        $query->whereNotNull('returned_at');
    }

    /**
     * Open loans whose due date has passed.
     *
     * @param  Builder<Loan>  $query
     */
    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->whereNull('returned_at')->whereDate('due_at', '<', today());
    }

    /**
     * Search by book title, inventory number, member name or card number.
     *
     * @param  Builder<Loan>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $like = '%'.$term.'%';

        $query->where(fn (Builder $q) => $q
            ->whereHas('copy', fn (Builder $c) => $c
                ->where('inventory_no', 'like', $like)
                ->orWhereHas('book', fn (Builder $b) => $b
                    ->where('title', 'like', $like)
                    ->orWhere('author', 'like', $like)))
            ->orWhereHas('member', fn (Builder $m) => $m
                ->where('name', 'like', $like)
                ->orWhere('member_no', 'like', $like)
                ->orWhere('phone', 'like', $like)));
    }
}
