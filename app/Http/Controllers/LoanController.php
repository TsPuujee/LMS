<?php

namespace App\Http\Controllers;

use App\Enums\CopyStatus;
use App\Exceptions\LoanException;
use App\Http\Requests\IssueLoanRequest;
use App\Http\Requests\RenewLoanRequest;
use App\Http\Requests\ReturnLoanRequest;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\Member;
use App\Services\LoanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoanController extends Controller
{
    private const RELATIONS = [
        'copy:id,book_id,inventory_no,status',
        'copy.book:id,title,author',
        'member:id,member_no,name,phone',
    ];

    public function __construct(private readonly LoanService $loans) {}

    /**
     * Who has which book right now: every open loan.
     */
    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'status' => $request->string('status')->toString(),
        ];

        $loans = Loan::query()
            ->open()
            ->with(self::RELATIONS)
            ->search($filters['search'])
            ->when($filters['status'] === 'overdue', fn (Builder $q) => $q->whereDate('due_at', '<', today()))
            ->when($filters['status'] === 'due_soon', fn (Builder $q) => $q->whereBetween('due_at', [today(), today()->addDays(3)]))
            ->orderBy('due_at')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('loans/Index', [
            'loans' => $loans,
            'filters' => $filters,
            'counts' => [
                'open' => Loan::query()->open()->count(),
                'overdue' => Loan::query()->overdue()->count(),
            ],
            'renewDays' => (int) config('library.renew_days'),
            'returnStatusOptions' => CopyStatus::options(manualOnly: true),
        ]);
    }

    /**
     * Every loan, open or returned.
     */
    public function history(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'status' => $request->string('status')->toString(),
            'from' => $request->date('from')?->toDateString(),
            'to' => $request->date('to')?->toDateString(),
        ];

        $loans = Loan::query()
            ->with([...self::RELATIONS, 'issuer:id,name', 'receiver:id,name'])
            ->search($filters['search'])
            ->when($filters['status'] === 'open', fn (Builder $q) => $q->whereNull('returned_at'))
            ->when($filters['status'] === 'returned', fn (Builder $q) => $q->whereNotNull('returned_at'))
            ->when($filters['status'] === 'overdue', fn (Builder $q) => $q->whereNull('returned_at')->whereDate('due_at', '<', today()))
            ->when($filters['from'], fn (Builder $q, string $from) => $q->whereDate('borrowed_at', '>=', $from))
            ->when($filters['to'], fn (Builder $q, string $to) => $q->whereDate('borrowed_at', '<=', $to))
            ->latest('borrowed_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('loans/History', [
            'loans' => $loans,
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): Response
    {
        $member = $request->integer('member')
            ? Member::query()->withCount('openLoans as open_loans_count')->find($request->integer('member'))
            : null;

        $copy = $request->integer('copy')
            ? BookCopy::query()->with('book:id,title,author')->find($request->integer('copy'))
            : null;

        return Inertia::render('loans/Create', [
            'member' => $member,
            'copy' => $copy,
            'defaultDueAt' => today()->addDays((int) config('library.loan_days'))->toDateString(),
            'loanDays' => (int) config('library.loan_days'),
            'maxActiveLoans' => (int) config('library.max_active_loans'),
        ]);
    }

    public function store(IssueLoanRequest $request): RedirectResponse
    {
        $copy = BookCopy::query()->with('book:id,title')->findOrFail($request->integer('book_copy_id'));
        $member = Member::query()->findOrFail($request->integer('member_id'));
        $dueAt = $request->filled('due_at') ? Carbon::parse($request->string('due_at')->toString()) : null;

        try {
            $loan = $this->loans->issue($copy, $member, $dueAt, $request->user(), $request->validated('note'));
        } catch (LoanException $e) {
            throw ValidationException::withMessages([$e->field => $e->getMessage()]);
        }

        $this->toast("«{$copy->book->title}» номыг {$member->name}-д зээлүүллээ. Буцаах хугацаа: {$loan->due_at->format('Y-m-d')}.");

        return $request->boolean('another')
            ? to_route('loans.create', ['member' => $member->id])
            : to_route('loans.index');
    }

    public function returnCopy(ReturnLoanRequest $request, Loan $loan): RedirectResponse
    {
        $status = $request->enum('status', CopyStatus::class) ?? CopyStatus::Available;

        try {
            $this->loans->return($loan, $request->user(), $status, $request->validated('note'));
        } catch (LoanException $e) {
            throw ValidationException::withMessages([$e->field => $e->getMessage()]);
        }

        $loan->load('copy.book:id,title');

        $this->toast(match ($status) {
            CopyStatus::Available => "«{$loan->copy->book->title}» ном буцаагдлаа.",
            default => "«{$loan->copy->book->title}» ном буцаагдлаа. Хувийн төлөв: {$status->label()}.",
        });

        return back();
    }

    public function renew(RenewLoanRequest $request, Loan $loan): RedirectResponse
    {
        try {
            $loan = $this->loans->renew($loan, $request->validated('days') !== null ? (int) $request->validated('days') : null);
        } catch (LoanException $e) {
            throw ValidationException::withMessages([$e->field => $e->getMessage()]);
        }

        $this->toast("Зээлийн хугацаа {$loan->due_at->format('Y-m-d')} хүртэл сунгагдлаа.");

        return back();
    }
}
