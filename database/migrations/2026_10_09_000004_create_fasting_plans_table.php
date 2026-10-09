<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fasting_plans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('method', 30);

            $table->string('category', 20);

            $table->boolean('recurring')->default(true);

            $table->string('start_time', 5);

            $table->string('end_time', 5);

            $table->json('weekdays');

            $table->date('start_date');

            $table->date('end_date')->nullable();

            $table->string('timezone', 64);

            $table->unsignedSmallInteger('fasting_hours');

            $table->unsignedSmallInteger('eating_hours');

            $table->boolean('active')->default(true);

            $table->json('notification_settings')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fasting_plans');
    }
};