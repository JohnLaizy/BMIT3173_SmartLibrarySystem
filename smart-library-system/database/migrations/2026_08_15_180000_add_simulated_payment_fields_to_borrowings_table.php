<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store only simulation metadata. Bank credentials and bank transaction
     * information are deliberately never stored by this application.
     */
    public function up(): void
    {
        Schema::table('borrowings', function (Blueprint $table): void {
            $table->string('payment_method', 32)->nullable();
            $table->timestamp('payment_started_at')->nullable();

            $table->index([
                'payment_method',
                'payment_submitted_at',
            ]);
        });
    }

    /**
     * Reverse the simulation metadata without touching the existing
     * payment reference and approval fields used by the borrowing module.
     */
    public function down(): void
    {
        Schema::table('borrowings', function (Blueprint $table): void {
            $table->dropIndex([
                'payment_method',
                'payment_submitted_at',
            ]);

            $table->dropColumn([
                'payment_method',
                'payment_started_at',
            ]);
        });
    }
};
