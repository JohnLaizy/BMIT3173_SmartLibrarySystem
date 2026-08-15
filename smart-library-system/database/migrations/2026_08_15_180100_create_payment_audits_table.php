<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create an application-side audit trail for simulated payments.
     */
    public function up(): void
    {
        Schema::create('payment_audits', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('borrowing_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('actor_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('payment_reference', 100)->nullable();
            $table->string('event', 64);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['borrowing_id', 'created_at']);
            $table->index(['payment_reference', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_audits');
    }
};
