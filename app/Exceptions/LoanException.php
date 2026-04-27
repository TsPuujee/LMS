<?php

namespace App\Exceptions;

use App\Models\BookCopy;
use App\Models\Member;
use RuntimeException;

/**
 * A loan rule was broken. The message is shown to the librarian as-is and
 * $field says which form field it belongs to.
 */
class LoanException extends RuntimeException
{
    public function __construct(string $message, public readonly string $field = 'loan')
    {
        parent::__construct($message);
    }

    public static function copyNotAvailable(BookCopy $copy): self
    {
        return new self(
            "«{$copy->inventory_no}» хувь одоогоор {$copy->status->label()} төлөвтэй тул зээлүүлэх боломжгүй.",
            'book_copy_id',
        );
    }

    public static function memberInactive(Member $member): self
    {
        return new self("{$member->name} уншигчийн эрх идэвхгүй байна.", 'member_id');
    }

    public static function tooManyLoans(Member $member, int $limit): self
    {
        return new self(
            "{$member->name} уншигч аль хэдийн {$limit} ном зээлсэн байна. Нэгийг нь буцаасны дараа дахин зээлүүлнэ үү.",
            'member_id',
        );
    }

    public static function alreadyReturned(): self
    {
        return new self('Энэ зээл аль хэдийн хаагдсан байна.');
    }

    public static function invalidReturnStatus(): self
    {
        return new self('Буцаасан хувийн төлөв «Зээлсэн» байж болохгүй.', 'status');
    }
}
