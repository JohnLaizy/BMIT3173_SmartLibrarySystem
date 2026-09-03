<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrowings', function (Blueprint $table): void {
            $table->string('renewal_status', 20)->nullable();
            $table->timestamp('renewal_requested_at')->nullable();
            $table->timestamp('renewal_reviewed_at')->nullable();

            $table->foreignId('renewal_reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('renewal_rejection_reason', 255)
                ->nullable();

            $table->unsignedInteger('renewal_count')
                ->default(0);

            $table->index([
                'renewal_status',
                'renewal_requested_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('borrowings', function (Blueprint $table): void {
            $table->dropIndex([
                'renewal_status',
                'renewal_requested_at',
            ]);

            $table->dropConstrainedForeignId(
                'renewal_reviewed_by'
            );

            $table->dropColumn([
                'renewal_status',
                'renewal_requested_at',
                'renewal_reviewed_at',
                'renewal_rejection_reason',
                'renewal_count',
            ]);
        });
    }
};