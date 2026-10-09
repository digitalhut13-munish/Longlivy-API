<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('token', 255)->unique();

            $table->string('provider', 20)->default('expo');

            $table->string('platform', 10)->nullable();

            $table->string('app_version', 30)->nullable();

            $table->string('locale', 10)->nullable();

            $table->string('timezone', 64)->nullable();

            $table->timestamp('last_seen_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};