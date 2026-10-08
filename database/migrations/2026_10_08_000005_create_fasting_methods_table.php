<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fasting_methods', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100);

            $table->string('slug', 100)->unique();

            $table->unsignedSmallInteger('fasting_hours');

            $table->unsignedSmallInteger('eating_hours')->nullable();

            $table->string('category', 30);

            $table->unsignedInteger('sort_order')->default(0);

            $table->boolean('active')->default(true);

            $table->text('description')->nullable();

            $table->text('recommendation')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fasting_methods');
    }
};
