<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 建立图书馆营业时间设置。
     */
    public function up(): void
    {
        Schema::create(
            'library_settings',
            function (Blueprint $table) {
                $table->id();

                /*
                 * 普通开放时间：
                 * 08:00 至 20:00。
                 */
                $table
                    ->unsignedTinyInteger(
                        'regular_opening_hour'
                    )
                    ->default(8);

                $table
                    ->unsignedTinyInteger(
                        'regular_closing_hour'
                    )
                    ->default(20);

                /*
                 * Exam Period 开放时间：
                 * 08:00 至第二天 01:00。
                 */
                $table
                    ->boolean('exam_period_enabled')
                    ->default(false);

                $table
                    ->unsignedTinyInteger(
                        'exam_opening_hour'
                    )
                    ->default(8);

                $table
                    ->unsignedTinyInteger(
                        'exam_closing_hour'
                    )
                    ->default(1);

                /*
                 * true 表示 Exam Period 的 01:00
                 * 属于第二天凌晨。
                 */
                $table
                    ->boolean('exam_closes_next_day')
                    ->default(true);

                /*
                 * 记录最后修改设置的 Librarian。
                 */
                $table
                    ->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();
            }
        );

        /*
         * 系统只需要一笔 Library Setting。
         * Migration 建立后自动加入默认设置。
         */
        DB::table('library_settings')->insert([
            'regular_opening_hour' => 8,
            'regular_closing_hour' => 20,

            'exam_period_enabled' => false,
            'exam_opening_hour' => 8,
            'exam_closing_hour' => 1,
            'exam_closes_next_day' => true,

            'updated_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * 删除图书馆设置资料表。
     */
    public function down(): void
    {
        Schema::dropIfExists('library_settings');
    }
};
