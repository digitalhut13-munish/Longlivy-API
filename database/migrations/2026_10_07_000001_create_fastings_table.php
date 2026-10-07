<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fastings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('fasting_type', 50);

            $table->unsignedSmallInteger('planned_hours');

            $table->dateTime('started_at');

            $table->dateTime('ended_at')->nullable();

            $table->decimal('actual_hours', 6, 2)->nullable();

            $table->string('status', 20)->default('ongoing');

            $table->date('date');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index([
                'user_id',
                'status',
            ]);

            $table->index([
                'user_id',
                'started_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fastings');
    }
};
