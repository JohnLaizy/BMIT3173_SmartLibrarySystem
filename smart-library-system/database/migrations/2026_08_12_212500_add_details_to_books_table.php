<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            if (!Schema::hasColumn('books', 'category')) {
                $table->string('category')->default('General')->after('author');
            }
            if (!Schema::hasColumn('books', 'type')) {
                $table->enum('type', ['physical', 'ebook'])->default('physical')->after('category');
            }
            if (!Schema::hasColumn('books', 'cover_image_path')) {
                $table->string('cover_image_path')->nullable()->after('available_copies');
            }
            if (!Schema::hasColumn('books', 'file_path')) {
                $table->string('file_path')->nullable()->after('cover_image_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn(['category', 'type', 'cover_image_path', 'file_path']);
        });
    }
};