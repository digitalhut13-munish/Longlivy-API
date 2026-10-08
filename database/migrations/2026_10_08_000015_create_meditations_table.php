<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meditations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('meditation_categories')
                ->nullOnDelete();

            $table->string('type', 50);

            $table->string('title');

            $table->text('description')->nullable();

            $table->unsignedSmallInteger('duration_minutes');

            $table->string('audio_url', 500)->nullable();

            $table->string('background_audio_url', 500)->nullable();

            $table->string('background_type', 50)->default('none');

            $table->string('language', 10)->default('en');

            $table->string('status', 20)->default('design');

            $table->date('released_at')->nullable();

            $table->unsignedInteger('version')->default(1);

            $table->string('source', 255)->nullable();

            $table->string('rights_holder', 255)->nullable();

            $table->string('license_type', 100)->nullable();

            $table->string('license_url', 500)->nullable();

            $table->string('license_status', 50)->default('unaudited');

            $table->boolean('attribution_required')->default(false);

            $table->boolean('commercial_use_allowed')->default(false);

            $table->timestamps();

            $table->index(['status', 'type']);

            $table->index(['category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meditations');
    }
};
