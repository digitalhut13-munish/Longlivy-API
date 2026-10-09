<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('goal')->nullable()->after('activity_level');
            $table->decimal('weight_change_pace_kg_per_week', 4, 2)
                ->nullable()
                ->after('goal');
            $table->smallInteger('training_frequency')
                ->unsigned()
                ->nullable()
                ->after('weight_change_pace_kg_per_week');
            $table->string('training_volume')
                ->nullable()
                ->after('training_frequency');
            $table->string('preferred_fasting_method')
                ->nullable()
                ->after('training_volume');
            $table->json('micronutrient_focus')
                ->nullable()
                ->after('preferred_fasting_method');
            $table->string('avatar_id')
                ->nullable()
                ->after('micronutrient_focus');
            $table->string('language')
                ->nullable()
                ->after('avatar_id');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'goal',
                'weight_change_pace_kg_per_week',
                'training_frequency',
                'training_volume',
                'preferred_fasting_method',
                'micronutrient_focus',
                'avatar_id',
                'language',
            ]);
        });
    }
};