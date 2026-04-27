<?php

namespace App\Http\Controllers;

use App\Http\Requests\MemberRequest;
use App\Models\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'status' => $request->string('status')->toString(),
        ];

        $members = Member::query()
            ->withCount([
                'openLoans as open_loans_count',
                'openLoans as overdue_loans_count' => fn (Builder $q) => $q->whereDate('due_at', '<', today()),
            ])
            ->search($filters['search'])
            ->when($filters['status'] === 'active', fn (Builder $q) => $q->where('is_active', true))
            ->when($filters['status'] === 'inactive', fn (Builder $q) => $q->where('is_active', false))
            ->when($filters['status'] === 'borrowing', fn (Builder $q) => $q->has('openLoans'))
            ->when($filters['status'] === 'overdue', fn (Builder $q) => $q->whereHas('openLoans', fn (Builder $l) => $l->whereDate('due_at', '<', today())))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('members/Index', [
            'members' => $members,
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('members/Form', ['member' => null]);
    }

    public function store(MemberRequest $request): RedirectResponse
    {
        $member = Member::query()->create($request->validated());

        $this->toast("{$member->name} уншигч бүртгэгдлээ.");

        return to_route('members.show', $member);
    }

    public function show(Member $member): Response
    {
        return Inertia::render('members/Show', [
            'member' => $member,
            'openLoans' => $member->openLoans()
                ->with(['copy:id,book_id,inventory_no', 'copy.book:id,title,author'])
                ->orderBy('due_at')
                ->get(),
            'history' => $member->loans()
                ->returned()
                ->with(['copy:id,book_id,inventory_no', 'copy.book:id,title,author'])
                ->latest('returned_at')
                ->limit(50)
                ->get(),
            'maxActiveLoans' => (int) config('library.max_active_loans'),
        ]);
    }

    public function edit(Member $member): Response
    {
        return Inertia::render('members/Form', ['member' => $member]);
    }

    public function update(MemberRequest $request, Member $member): RedirectResponse
    {
        $member->update($request->validated());

        $this->toast('Уншигчийн мэдээлэл хадгалагдлаа.');

        return to_route('members.show', $member);
    }

    public function destroy(Member $member): RedirectResponse
    {
        if ($member->loans()->exists()) {
            $this->toast('Зээлийн түүхтэй уншигчийг устгах боломжгүй. Оронд нь идэвхгүй болгоно уу.', 'error');

            return back();
        }

        $name = $member->name;
        $member->delete();

        $this->toast("{$name} уншигч устгагдлаа.");

        return to_route('members.index');
    }
}
