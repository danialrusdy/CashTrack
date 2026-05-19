<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['income', 'expense']);
            $table->string('name', 255);
            $table->decimal('amount', 15, 2);
            $table->text('note')->nullable();
            $table->date('transaction_date');
            $table->enum('source', ['web', 'telegram'])->default('web');
            $table->timestamps();

            $table->index(['type', 'transaction_date']);
            $table->index('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
