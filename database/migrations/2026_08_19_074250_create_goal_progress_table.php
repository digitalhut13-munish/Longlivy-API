<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goal_progress', function (Blueprint $table) {
            $table->id();

            $table->foreignId('goal_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('date');

            $table->decimal('value', 10, 2)->default(0);

            $table->boolean('completed')->default(false);

            $table->timestamps();

            $table->unique([
                'goal_id',
                'date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_progress');
    }
};