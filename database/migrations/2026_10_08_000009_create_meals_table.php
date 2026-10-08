<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('meal_type', 30);

            $table->string('name', 191)->nullable();

            $table->dateTime('logged_at');

            $table->date('date');

            $table->decimal('total_calories', 10, 2)->default(0);

            $table->decimal('total_protein', 10, 2)->default(0);

            $table->decimal('total_carbohydrates', 10, 2)->default(0);

            $table->decimal('total_fat', 10, 2)->default(0);

            $table->decimal('total_fiber', 10, 2)->default(0);

            $table->decimal('total_sodium', 10, 2)->default(0);

            $table->text('notes')->nullable();

            $table->string('source', 40)->default('manual');

            $table->timestamps();

            $table->index(['user_id', 'date']);

            $table->index(['user_id', 'date', 'meal_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meals');
    }
};
