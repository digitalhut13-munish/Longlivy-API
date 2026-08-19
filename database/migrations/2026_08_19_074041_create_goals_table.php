<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('goal_type', 100);

            $table->string('name');

            $table->text('description')->nullable();

            $table->decimal('target_value', 10, 2);

            $table->string('unit', 50);

            $table->string('period', 50);

            $table->date('start_date');

            $table->date('end_date')->nullable();

            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->index([
                'user_id',
                'goal_type',
                'active',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};