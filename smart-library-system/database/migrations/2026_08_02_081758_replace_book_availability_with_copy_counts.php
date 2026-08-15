<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('books', 'total_copies')) {
            Schema::table('books', function (Blueprint $table) {
                $table->unsignedInteger('total_copies')
                    ->default(1);
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
        /*
         * No-op intentionally. Copy counts are now part of the
         * canonical Book Management schema and must not be removed.
         */
    }
};