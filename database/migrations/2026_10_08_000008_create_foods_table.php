<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foods', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name', 191);

            $table->string('brand', 191)->nullable();

            $table->foreignId('category_id')
                ->nullable()
                ->references('id')
                ->on('food_categories')
                ->nullOnDelete();

            $table->string('barcode', 64)->nullable();

            // Nutrient values are stored per base_amount of base_unit.
            $table->string('base_unit', 10)->default('g');

            $table->decimal('base_amount', 10, 2)->default(100);

            $table->decimal('calories', 10, 2)->default(0);

            $table->decimal('protein', 10, 2)->default(0);

            $table->decimal('carbohydrates', 10, 2)->default(0);

            $table->decimal('fat', 10, 2)->default(0);

            $table->decimal('fiber', 10, 2)->nullable();

            $table->decimal('sugar', 10, 2)->nullable();

            $table->decimal('saturated_fat', 10, 2)->nullable();

            $table->decimal('sodium', 10, 2)->nullable();

            // Conversion helpers for piece / serving based entries.
            $table->decimal('grams_per_unit', 10, 2)->nullable();

            $table->decimal('ml_per_unit', 10, 2)->nullable();

            $table->decimal('serving_amount', 10, 2)->nullable();

            $table->string('source', 40)->default('manual');

            $table->boolean('verified')->default(false);

            $table->boolean('is_custom')->default(false);

            $table->string('external_id', 191)->nullable();

            $table->timestamps();

            $table->index('barcode');

            $table->index('name');

            $table->unique([
                'user_id',
                'source',
                'external_id',
            ], 'foods_user_source_external_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foods');
    }
};
