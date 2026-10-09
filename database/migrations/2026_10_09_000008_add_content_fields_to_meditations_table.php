<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meditations', function (Blueprint $table) {
            $table->string('sound_category', 30)->nullable();

            $table->unsignedInteger('duration_seconds')->nullable();

            $table->string('thumbnail_path', 500)->nullable();

            $table->string('availability', 20)->default('available');

            $table->boolean('is_premium')->default(false);

            $table->json('breathing_pattern')->nullable();

            $table->index('availability');
        });
    }

    public function down(): void
    {
        Schema::table('meditations', function (Blueprint $table) {
            $table->dropIndex(['availability']);

            $table->dropColumn([
                'sound_category',
                'duration_seconds',
                'thumbnail_path',
                'availability',
                'is_premium',
                'breathing_pattern',
            ]);
        });
    }
};