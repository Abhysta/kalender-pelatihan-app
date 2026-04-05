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
        Schema::create('detail_aktivitas', function (Blueprint $table) {
            $table->id('id_aktivitas');
            $table->unsignedBigInteger('id_kalender');
            $table->date('tanggal_aktivitas');
            $table->string('nama_kegiatan');
            $table->enum('metode_pembelajaran', ['klasikal', 'e-learning', 'mooc', 'cop']);
            $table->string('nama_pengajar');
            $table->timestamps();

            $table->foreign('id_kalender')
                ->references('id_kalender')
                ->on('master_kalender')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_aktivitas');
    }
};
