<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name', 191);

            $table->text('notes')->nullable();

            $table->unsignedInteger('servings')->default(1);

            $table->decimal('total_calories', 10, 2)->default(0);

            $table->decimal('total_protein', 10, 2)->default(0);

            $table->decimal('total_carbohydrates', 10, 2)->default(0);

            $table->decimal('total_fat', 10, 2)->default(0);

            $table->decimal('total_fiber', 10, 2)->default(0);

            $table->timestamps();
        });

        Schema::create('recipe_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('recipe_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('food_id')
                ->nullable()
                ->constrained('foods')
                ->nullOnDelete();

            $table->string('name', 191);

            $table->decimal('quantity', 10, 2);

            $table->string('unit', 10);

            $table->decimal('calories', 10, 2)->default(0);

            $table->decimal('protein', 10, 2)->default(0);

            $table->decimal('carbohydrates', 10, 2)->default(0);

            $table->decimal('fat', 10, 2)->default(0);

            $table->decimal('fiber', 10, 2)->nullable();

            $table->unsignedInteger('position')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_items');
        Schema::dropIfExists('recipes');
    }
};
