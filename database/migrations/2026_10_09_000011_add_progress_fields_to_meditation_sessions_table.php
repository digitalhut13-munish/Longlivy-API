<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meditation_sessions', function (Blueprint $table) {
            $table->unsignedInteger('position_seconds')->default(0);

            $table->unsignedInteger('active_seconds')->nullable();

            $table->dateTime('progress_updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('meditation_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'position_seconds',
                'active_seconds',
                'progress_updated_at',
            ]);
        });
    }
};