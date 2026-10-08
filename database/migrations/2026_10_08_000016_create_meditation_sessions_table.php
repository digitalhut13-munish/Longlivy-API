<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meditation_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('meditation_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('type', 50);

            $table->string('status', 20)->default('active');

            $table->unsignedSmallInteger('planned_minutes');

            $table->unsignedSmallInteger('actual_minutes')->nullable();

            $table->dateTime('started_at');

            $table->dateTime('ended_at')->nullable();

            $table->dateTime('paused_at')->nullable();

            $table->unsignedInteger('paused_seconds')->default(0);

            $table->date('date');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);

            $table->index(['user_id', 'date']);

            $table->index(['user_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meditation_sessions');
    }
};
