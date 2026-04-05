<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('master_kalender', function (Blueprint $table) {
            $table->unsignedInteger('total_peserta')->default(0)->after('tahun_kalender');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_kalender', function (Blueprint $table) {
            $table->dropColumn('total_peserta');
        });
    }
};
