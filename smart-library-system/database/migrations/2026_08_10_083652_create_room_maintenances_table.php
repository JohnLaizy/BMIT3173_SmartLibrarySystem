<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_maintenances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title');

            $table->text('description')
                ->nullable();

            $table->dateTime('starts_at');

            $table->dateTime('ends_at');

            $table->string('status')
                ->default('scheduled');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index([
                'room_id',
                'starts_at',
                'ends_at',
            ]);

            $table->index([
                'status',
                'starts_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_maintenances');
    }
};
