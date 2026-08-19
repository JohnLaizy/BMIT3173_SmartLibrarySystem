<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The original books-table migration in this project now creates the
     * copy-count columns directly. Keep this migration safe for a fresh test
     * database as well as older databases that still have `is_available`.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('books', 'total_copies')) {
            Schema::table('books', function (Blueprint $table): void {
                $table->unsignedInteger('total_copies')->default(1);
            });
        }

        if (! Schema::hasColumn('books', 'available_copies')) {
            Schema::table('books', function (Blueprint $table) {
                $table->unsignedInteger('available_copies')
                    ->default(1);
            });
        }

        /*
         * Supports databases created using the older Book schema.
         * Unavailable books become one-copy books with no available copy.
         */
        if (Schema::hasColumn('books', 'is_available')) {
            DB::table('books')
                ->where('is_available', false)
                ->update(['available_copies' => 0]);

            Schema::table('books', function (Blueprint $table) {
                $table->dropColumn('is_available');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('books', 'is_available')) {
            Schema::table('books', function (Blueprint $table): void {
                $table->boolean('is_available')->default(true);
            });
        }

        $columnsToDrop = array_values(array_filter([
            Schema::hasColumn('books', 'total_copies')
                ? 'total_copies'
                : null,
            Schema::hasColumn('books', 'available_copies')
                ? 'available_copies'
                : null,
        ]));

        if ($columnsToDrop !== []) {
            Schema::table('books', function (Blueprint $table) use ($columnsToDrop): void {
                $table->dropColumn($columnsToDrop);
            });
        }
        /*
         * No-op intentionally. Copy counts are now part of the
         * canonical Book Management schema and must not be removed.
         */
    }
};