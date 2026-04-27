<?php

namespace App\Models;

use App\Enums\CopyStatus;
use Database\Factories\BookCopyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $book_id
 * @property string|null $inventory_no
 * @property CopyStatus $status
 * @property string|null $shelf
 * @property Carbon|null $acquired_at
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Book $book
 * @property-read Loan|null $openLoan
 */
#[Fillable(['book_id', 'inventory_no', 'status', 'shelf', 'acquired_at', 'note'])]
class BookCopy extends Model
{
    /** @use HasFactory<BookCopyFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'available',
    ];

    protected function casts(): array
    {
        return [
            'status' => CopyStatus::class,
            'acquired_at' => 'date:Y-m-d',
        ];
    }

    protected static function booted(): void
    {
        // Give every copy an inventory number when none was typed in.
        static::created(function (BookCopy $copy): void {
            if (blank($copy->inventory_no)) {
                $copy->inventory_no = sprintf('INV-%06d', $copy->id);
                $copy->saveQuietly();
            }
        });
    }

    /**
     * @return BelongsTo<Book, $this>
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * @return HasMany<Loan, $this>
     */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * The loan that currently holds this copy, if any.
     *
     * @return HasOne<Loan, $this>
     */
    public function openLoan(): HasOne
    {
        return $this->hasOne(Loan::class)->whereNull('returned_at');
    }

    public function isAvailable(): bool
    {
        return $this->status === CopyStatus::Available;
    }

    /**
     * @param  Builder<BookCopy>  $query
     */
    #[Scope]
    protected function available(Builder $query): void
    {
        $query->where('status', CopyStatus::Available);
    }
}
