<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_copy_id')->constrained()->restrictOnDelete();
            $table->foreignId('member_id')->constrained()->restrictOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('borrowed_at');
            $table->date('due_at');
            $table->dateTime('returned_at')->nullable();
            $table->unsignedSmallInteger('renewals')->default(0);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index('due_at');
            $table->index('returned_at');
        });

        // A copy can have at most one open loan. The generated column is the
        // copy id while the loan is open and NULL once it is returned, and
        // MySQL allows any number of NULLs in a unique index.
        Schema::table('loans', function (Blueprint $table) {
            $table->unsignedBigInteger('open_copy_id')
                ->nullable()
                ->storedAs('IF(`returned_at` IS NULL, `book_copy_id`, NULL)')
                ->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
