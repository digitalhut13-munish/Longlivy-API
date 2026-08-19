<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_streaks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('type', 100);

            $table->unsignedInteger('current_streak')->default(0);

            $table->unsignedInteger('longest_streak')->default(0);

            $table->date('current_streak_started_at')->nullable();

            $table->date('last_completed_date')->nullable();

            $table->timestamps();

            $table->unique([
                'user_id',
                'type',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_streaks');
    }
};