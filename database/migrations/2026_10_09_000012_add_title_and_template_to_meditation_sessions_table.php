<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meditation_sessions', function (Blueprint $table) {
            $table->foreignId('template_id')
                ->nullable()
                ->after('meditation_id')
                ->constrained('meditation_templates')
                ->nullOnDelete();

            $table->string('title')->nullable()->after('template_id');
        });
    }

    public function down(): void
    {
        Schema::table('meditation_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('template_id');

            $table->dropColumn('title');
        });
    }
};
