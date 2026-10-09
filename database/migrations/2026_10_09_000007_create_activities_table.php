<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('client_id', 191);

            $table->string('type', 20);

            $table->string('source', 20);

            $table->dateTime('started_at');

            $table->dateTime('ended_at');

            $table->unsignedInteger('active_seconds')->default(0);

            $table->unsignedInteger('paused_seconds')->default(0);

            $table->decimal('distance_meters', 12, 2)->nullable();

            $table->unsignedInteger('calories_kcal');

            $table->string('calculation_method', 60)->nullable();

            $table->unsignedSmallInteger('avg_heart_rate')->nullable();

            $table->unsignedInteger('steps')->nullable();

            $table->mediumText('route_polyline')->nullable();

            $table->unsignedInteger('route_point_count')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'client_id']);

            $table->index(['user_id', 'started_at']);

            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};