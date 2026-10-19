<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // The app cannot always know the user's weight, so an
            // activity may be recorded without a calorie value.
            $table->unsignedInteger('calories_kcal')->nullable()->change();

            // The encoding the client sent is stored so a GPS route is
            // read back exactly as it was uploaded.
            $table->string('route_encoding', 20)->nullable();
        });
    }

    public function down(): void
    {
        DB::table('activities')->whereNull('calories_kcal')->update([
            'calories_kcal' => 0,
        ]);

        Schema::table('activities', function (Blueprint $table) {
            $table->unsignedInteger('calories_kcal')->nullable(false)->change();
            $table->dropColumn('route_encoding');
        });
    }
};
