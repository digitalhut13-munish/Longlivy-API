<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('energy_expenditures', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->date('date');

            // bmr | everyday_activity | sport_activity
            // | imported_activity | manual_activity
            $table->string('component', 40);

            // longlivy_calculated | device_imported
            // | user_manual | wearable
            $table->string('source', 40);

            $table->decimal('calories', 10, 2);

            $table->string('calculation_method', 60)->nullable();
            $table->string('calculation_version', 20)->nullable();

            $table->string('provider', 60)->nullable();
            $table->string('external_id', 191)->default('');
            $table->timestamp('calculated_at')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->unique(
                ['user_id', 'date', 'component', 'source', 'external_id'],
                'energy_expenditures_identity_unique'
            );

            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('energy_expenditures');
    }
};
