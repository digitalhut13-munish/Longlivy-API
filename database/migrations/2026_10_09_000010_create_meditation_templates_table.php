<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meditation_templates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name', 60);

            $table->unsignedInteger('duration_seconds');

            $table->string('type', 20);

            $table->boolean('breathing_enabled')->default(false);

            $table->boolean('closing_sound_enabled')->default(true);

            $table->string('background_sound', 100)->nullable();

            $table->foreignId('meditation_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meditation_templates');
    }
};