<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_berkas', function (Blueprint $table) {
            $table->id('id_berkas');
            // Kunci UNIQUE agar 1 user hanya punya 1 row berkas
            $table->foreignId('id_user')->unique()->constrained('tb_users', 'id_user')->onDelete('cascade');
            $table->string('file_ijazah_skl')->nullable();
            $table->string('file_kk')->nullable();
            $table->string('file_aktd_kelahiran')->nullable();
            $table->string('file_pasfoto')->nullable();
            $table->enum('status_verifikasi', ['belum_diunggah', 'pending', 'terverifikasi', 'ditolak'])->default('belum_diunggah');
            $table->text('catatan_admin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_berkas');
    }
};