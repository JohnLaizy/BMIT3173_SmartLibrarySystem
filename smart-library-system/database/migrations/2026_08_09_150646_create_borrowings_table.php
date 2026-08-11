<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('borrowings', function (Blueprint $table) {
            $table->id();

            // The student and physical book copy involved
            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('book_id')
                ->constrained()
                ->restrictOnDelete();

            // State Pattern state
            $table->string('status', 32)
                ->default('borrowed');

            // Borrowing period
            $table->timestamp('borrowed_at');
            $table->timestamp('due_at');
            $table->timestamp('returned_at')->nullable();

            // Overdue payment information
            $table->unsignedInteger('overdue_fee_cents')
                ->default(0);

            $table->string('payment_reference', 100)
                ->nullable();

            $table->timestamp('payment_submitted_at')
                ->nullable();

            $table->timestamp('payment_approved_at')
                ->nullable();

            $table->foreignId('payment_approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Improve rule checking and automatic overdue searches
            $table->index(['user_id', 'status']);
            $table->index(['status', 'due_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrowings');
    }
};
