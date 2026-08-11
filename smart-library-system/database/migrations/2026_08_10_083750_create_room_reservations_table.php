<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_reservations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('purpose');

            $table->dateTime('starts_at');

            $table->dateTime('ends_at');

            $table->string('status')
                ->default('confirmed');

            $table->dateTime('cancelled_at')
                ->nullable();

            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index([
                'room_id',
                'status',
                'starts_at',
                'ends_at',
            ]);

            $table->index([
                'user_id',
                'status',
                'starts_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_reservations');
    }
};
