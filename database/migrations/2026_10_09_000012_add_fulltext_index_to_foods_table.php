<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A MySQL FULLTEXT index powers the name/brand search over the
        // shared catalog. SQLite (used by the test suite) has no
        // FULLTEXT support, so the index is only added on MySQL.
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE foods ADD FULLTEXT INDEX '
                .'foods_name_brand_fulltext (name, brand)'
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE foods DROP INDEX foods_name_brand_fulltext'
            );
        }
    }
};