<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_energy_balances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->date('date');

            // Components are stored separately and merged here so a
            // double count between activity factor and imported
            // activities stays visible and debuggable.
            $table->decimal('bmr', 10, 2)->default(0);
            $table->decimal('everyday_activity', 10, 2)->default(0);
            $table->decimal('sport_activity', 10, 2)->default(0);
            $table->decimal('imported_activity', 10, 2)->default(0);
            $table->decimal('manual_activity', 10, 2)->default(0);

            $table->decimal('total_consumption', 10, 2)->default(0);
            $table->decimal('intake', 10, 2)->default(0);

            $table->decimal('calorie_goal', 10, 2)->nullable();

            // intake - total_consumption (negative means a deficit)
            $table->decimal('difference', 10, 2)->default(0);

            // goal - intake; null whenever the goal is exceeded
            $table->decimal('remaining', 10, 2)->nullable();
            $table->decimal('over_by', 10, 2)->nullable();

            // below | met | exceeded | no_goal
            $table->string('status', 20)->default('no_goal');

            $table->timestamp('computed_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_energy_balances');
    }
};
