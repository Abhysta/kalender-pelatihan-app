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
        Schema::create('master_kalender', function (Blueprint $table) {
            $table->id('id_kalender');
            $table->string('nama_kalender');
            $table->unsignedSmallInteger('tahun_kalender');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_kalender');
    }
};
