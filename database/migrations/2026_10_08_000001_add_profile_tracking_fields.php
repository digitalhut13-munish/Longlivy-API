<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('timezone', 64)
                ->default('UTC')
                ->after('address');

            $table->string('activity_level', 20)
                ->default('moderate')
                ->after('timezone');

            $table->decimal('body_fat_percentage', 5, 2)
                ->nullable()
                ->after('activity_level');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'timezone',
                'activity_level',
                'body_fat_percentage',
            ]);
        });
    }
};
