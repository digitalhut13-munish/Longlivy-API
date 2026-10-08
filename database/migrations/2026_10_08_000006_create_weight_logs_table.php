<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weight_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('weight', 8, 2);

            $table->string('unit', 10)->default('kg');

            $table->dateTime('logged_at');

            $table->date('date');

            $table->string('source', 30)->default('manual');

            $table->string('external_id', 191)->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'date']);

            $table->unique([
                'user_id',
                'source',
                'external_id',
            ], 'weight_logs_user_source_external_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weight_logs');
    }
};
