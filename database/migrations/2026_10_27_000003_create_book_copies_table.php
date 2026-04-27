<?php

use App\Enums\CopyStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            // Accession / barcode number written on the physical copy.
            $table->string('inventory_no', 50)->nullable()->unique();
            $table->string('status', 20)->default(CopyStatus::Available->value)->index();
            $table->string('shelf', 50)->nullable();
            $table->date('acquired_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_copies');
    }
};
