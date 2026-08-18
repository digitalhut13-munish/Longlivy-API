<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('first_name');
            $table->string('last_name');

            $table->date('date_of_birth')->nullable();

            $table->string('gender')->nullable();

            $table->decimal('height', 8, 2)->nullable();
            $table->string('height_unit', 10)->default('cm');

            $table->decimal('current_weight', 8, 2)->nullable();
            $table->string('weight_unit', 10)->default('kg');

            $table->text('address')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};