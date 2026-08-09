<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('rooms')
            ->where('status', 'occupied')
            ->update([
                'status' => 'unavailable',
            ]);
    }

    public function down(): void
    {
        DB::table('rooms')
            ->where('status', 'unavailable')
            ->update([
                'status' => 'occupied',
            ]);
    }
};
