<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            Schema::table('books', function (Blueprint $table): void {
                $table->unsignedInteger('available_copies')->default(1);
            });
        }

        if (Schema::hasColumn('books', 'is_available')) {
            Schema::table('books', function (Blueprint $table): void {
                $table->dropColumn('is_available');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
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
    }
};
