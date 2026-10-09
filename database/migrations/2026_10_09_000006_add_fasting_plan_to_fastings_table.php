<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fastings', function (Blueprint $table) {
            $table->foreignId('fasting_plan_id')
                ->nullable()
                ->after('user_id')
                ->constrained('fasting_plans')
                ->nullOnDelete();

            $table->unsignedSmallInteger('planned_minutes')
                ->nullable()
                ->after('planned_hours');
        });
    }

    public function down(): void
    {
        Schema::table('fastings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fasting_plan_id');
            $table->dropColumn('planned_minutes');
        });
    }
};