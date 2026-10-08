<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('meal_id')
                ->constrained('meals')
                ->cascadeOnDelete();

            $table->foreignId('food_id')
                ->nullable()
                ->constrained('foods')
                ->nullOnDelete();

            // Snapshot values so later edits of the food database entry
            // never rewrite what was actually logged.
            $table->string('name', 191);

            $table->string('brand', 191)->nullable();

            $table->decimal('quantity', 10, 2);

            $table->string('unit', 10);

            $table->decimal('calories', 10, 2)->default(0);

            $table->decimal('protein', 10, 2)->default(0);

            $table->decimal('carbohydrates', 10, 2)->default(0);

            $table->decimal('fat', 10, 2)->default(0);

            $table->decimal('fiber', 10, 2)->nullable();

            $table->decimal('sugar', 10, 2)->nullable();

            $table->decimal('saturated_fat', 10, 2)->nullable();

            $table->decimal('sodium', 10, 2)->nullable();

            $table->unsignedInteger('position')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_items');
    }
};
