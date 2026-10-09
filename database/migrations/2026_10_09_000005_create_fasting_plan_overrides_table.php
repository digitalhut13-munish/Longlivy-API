<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fasting_plan_overrides', function (Blueprint $table) {
            $table->id();

            $table->foreignId('plan_id')
                ->constrained('fasting_plans')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('date');

            $table->string('action', 20);

            $table->string('start_time', 5)->nullable();

            $table->string('end_time', 5)->nullable();

            $table->timestamps();

            $table->unique(['plan_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fasting_plan_overrides');
    }
};