<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'library_settings',
            function (Blueprint $table): void {
                /*
                 * Exam Period 生效的第一天。
                 */
                $table->date(
                    'exam_period_starts_on'
                )->nullable();

                /*
                 * Exam Period 生效的最后一天。
                 */
                $table->date(
                    'exam_period_ends_on'
                )->nullable();
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'library_settings',
            function (Blueprint $table): void {
                $table->dropColumn([
                    'exam_period_starts_on',
                    'exam_period_ends_on',
                ]);
            }
        );
    }
};