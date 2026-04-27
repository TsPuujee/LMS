<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\Member;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $with = ['copy:id,book_id,inventory_no', 'copy.book:id,title,author', 'member:id,member_no,name,phone'];

        return Inertia::render('Dashboard', [
            'stats' => [
                'books' => Book::query()->count(),
                'copies' => BookCopy::query()->count(),
                'available' => BookCopy::query()->available()->count(),
                'onLoan' => Loan::query()->open()->count(),
                'overdue' => Loan::query()->overdue()->count(),
                'members' => Member::query()->where('is_active', true)->count(),
            ],
            'overdueLoans' => Loan::query()->overdue()->with($with)->orderBy('due_at')->limit(10)->get(),
            'dueSoon' => Loan::query()->open()
                ->whereBetween('due_at', [today(), today()->addDays(3)])
                ->with($with)->orderBy('due_at')->limit(10)->get(),
            'recentLoans' => Loan::query()->with($with)->latest('borrowed_at')->latest('id')->limit(8)->get(),
        ]);
    }
}
