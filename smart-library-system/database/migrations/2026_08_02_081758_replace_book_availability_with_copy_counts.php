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
        Schema::table('books', function (Blueprint $table) {
            $table->unsignedInteger('total_copies')->default(1);
            $table->unsignedInteger('available_copies')->default(1);
        });

        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn('is_available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->boolean('is_available')->default(true);
        });

        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn([
                'total_copies',
                'available_copies',
            ]);
        });
    }
};
