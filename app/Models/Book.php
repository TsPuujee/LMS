<?php

namespace App\Models;

use App\Enums\CopyStatus;
use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $category_id
 * @property string $title
 * @property string $author
 * @property string|null $isbn
 * @property string|null $publisher
 * @property int|null $published_year
 * @property string|null $language
 * @property int|null $pages
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Category $category
 */
#[Fillable([
    'category_id', 'title', 'author', 'isbn', 'publisher',
    'published_year', 'language', 'pages', 'description',
])]
class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'published_year' => 'integer',
            'pages' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<BookCopy, $this>
     */
    public function copies(): HasMany
    {
        return $this->hasMany(BookCopy::class);
    }

    /**
     * @return HasManyThrough<Loan, BookCopy, $this>
     */
    public function loans(): HasManyThrough
    {
        return $this->hasManyThrough(Loan::class, BookCopy::class);
    }

    /**
     * Adds total_copies_count and available_copies_count to the query.
     *
     * @param  Builder<Book>  $query
     */
    #[Scope]
    protected function withCopyCounts(Builder $query): void
    {
        $query->withCount([
            'copies as total_copies_count',
            'copies as available_copies_count' => fn (Builder $q) => $q->where('status', CopyStatus::Available),
        ]);
    }

    /**
     * @param  Builder<Book>  $query
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
            ->where('title', 'like', $like)
            ->orWhere('author', 'like', $like)
            ->orWhere('isbn', 'like', $like)
            ->orWhereHas('copies', fn (Builder $c) => $c->where('inventory_no', 'like', $like)));
    }
}
