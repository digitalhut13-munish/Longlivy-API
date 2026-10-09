<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meditation_audio', function (Blueprint $table) {
            $table->id();

            $table->foreignId('meditation_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('language', 10);

            $table->string('storage_path', 500);

            $table->string('format', 10);

            $table->string('mime_type', 100);

            $table->unsignedSmallInteger('bitrate_kbps')->nullable();

            $table->unsignedInteger('duration_seconds')->nullable();

            $table->unsignedBigInteger('size_bytes')->nullable();

            $table->timestamps();

            $table->unique(['meditation_id', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meditation_audio');
    }
};