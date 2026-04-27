<?php

namespace App\Services;

use App\Enums\CopyStatus;
use App\Exceptions\LoanException;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * The only place that opens and closes loans, so a copy's status and its
 * open loan never disagree.
 */
class LoanService
{
    /**
     * Lend a copy to a member.
     *
     * @throws LoanException
     */
    public function issue(
        BookCopy $copy,
        Member $member,
        ?CarbonInterface $dueAt = null,
        ?User $by = null,
        ?string $note = null,
    ): Loan {
        return DB::transaction(function () use ($copy, $member, $dueAt, $by, $note): Loan {
            // Lock both rows so two librarians cannot lend the same copy, or
            // push the same member over the limit, at the same moment.
            $copy = BookCopy::query()->lockForUpdate()->findOrFail($copy->id);
            $member = Member::query()->lockForUpdate()->findOrFail($member->id);

            if (! $copy->isAvailable()) {
                throw LoanException::copyNotAvailable($copy);
            }

            if (! $member->is_active) {
                throw LoanException::memberInactive($member);
            }

            $limit = (int) config('library.max_active_loans');

            if ($limit > 0 && $member->openLoans()->count() >= $limit) {
                throw LoanException::tooManyLoans($member, $limit);
            }

            $loan = $copy->loans()->create([
                'member_id' => $member->id,
                'issued_by' => $by?->id,
                'borrowed_at' => now(),
                'due_at' => ($dueAt ?? today()->addDays((int) config('library.loan_days')))->startOfDay(),
                'note' => $note,
            ]);

            $copy->update(['status' => CopyStatus::Borrowed]);

            return $loan;
        });
    }

    /**
     * Take a copy back. The copy may come back damaged or be reported lost.
     *
     * @throws LoanException
     */
    public function return(
        Loan $loan,
        ?User $by = null,
        CopyStatus $copyStatus = CopyStatus::Available,
        ?string $note = null,
    ): Loan {
        if ($copyStatus === CopyStatus::Borrowed) {
            throw LoanException::invalidReturnStatus();
        }

        return DB::transaction(function () use ($loan, $by, $copyStatus, $note): Loan {
            $loan = Loan::query()->lockForUpdate()->findOrFail($loan->id);

            if (! $loan->isOpen()) {
                throw LoanException::alreadyReturned();
            }

            $loan->update([
                'returned_at' => now(),
                'returned_by' => $by?->id,
                'note' => $note !== null && $note !== '' ? $note : $loan->note,
            ]);

            $loan->copy->update(['status' => $copyStatus]);

            return $loan;
        });
    }

    /**
     * Extend an open loan. Overdue loans are extended from today.
     *
     * @throws LoanException
     */
    public function renew(Loan $loan, ?int $days = null): Loan
    {
        return DB::transaction(function () use ($loan, $days): Loan {
            $loan = Loan::query()->lockForUpdate()->findOrFail($loan->id);

            if (! $loan->isOpen()) {
                throw LoanException::alreadyReturned();
            }

            $from = $loan->due_at->lt(today()) ? today() : $loan->due_at;

            $loan->update([
                'due_at' => $from->addDays($days ?? (int) config('library.renew_days')),
                'renewals' => $loan->renewals + 1,
            ]);

            return $loan;
        });
    }
}
