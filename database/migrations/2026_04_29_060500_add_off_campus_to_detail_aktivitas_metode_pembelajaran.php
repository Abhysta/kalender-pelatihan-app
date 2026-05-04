<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE detail_aktivitas
            MODIFY metode_pembelajaran
            ENUM('klasikal', 'e-learning', 'mooc', 'cop', 'zoom', 'off-campus')
            NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE detail_aktivitas
            MODIFY metode_pembelajaran
            ENUM('klasikal', 'e-learning', 'mooc', 'cop', 'zoom')
            NOT NULL
        ");
    }
};
