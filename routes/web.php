<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\BookCopyController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\MemberController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Catalogue
    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('books', BookController::class);
    Route::post('books/{book}/copies', [BookCopyController::class, 'store'])->name('books.copies.store');
    Route::put('copies/{copy}', [BookCopyController::class, 'update'])->name('copies.update');
    Route::delete('copies/{copy}', [BookCopyController::class, 'destroy'])->name('copies.destroy');

    // Members
    Route::resource('members', MemberController::class);

    // Loans
    Route::get('loans/history', [LoanController::class, 'history'])->name('loans.history');
    Route::resource('loans', LoanController::class)->only(['index', 'create', 'store']);
    Route::post('loans/{loan}/return', [LoanController::class, 'returnCopy'])->name('loans.return-copy');
    Route::post('loans/{loan}/renew', [LoanController::class, 'renew'])->name('loans.renew');

    // JSON search for the loan form
    Route::get('lookup/members', [LookupController::class, 'members'])->name('lookup.members');
    Route::get('lookup/copies', [LookupController::class, 'copies'])->name('lookup.copies');
});

require __DIR__.'/settings.php';
