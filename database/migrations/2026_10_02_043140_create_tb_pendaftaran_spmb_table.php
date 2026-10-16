<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_pendaftaran_spmb', function (Blueprint $table) {
            $table->id('id_pendaftaran');
            $table->foreignId('id_user')->unique()->constrained('tb_users', 'id_user')->onDelete('cascade');
            
            // Tambahkan 2 baris ini:
            $table->foreignId('id_biodata')->nullable()->constrained('tb_biodata', 'id_biodata')->onDelete('set null');
            $table->foreignId('id_berkas')->nullable()->constrained('tb_berkas', 'id_berkas')->onDelete('set null');

            $table->foreignId('id_jurusan_pilihan1')->nullable()->constrained('tb_jurusan', 'id_jurusan')->onDelete('set null');
            $table->foreignId('id_jurusan_pilihan2')->nullable()->constrained('tb_jurusan', 'id_jurusan')->onDelete('set null');
            $table->foreignId('jurusan_diterima')->nullable()->constrained('tb_jurusan', 'id_jurusan')->onDelete('set null');
            $table->string('no_pendaftaran')->unique()->nullable();
            $table->decimal('nilai_tes_masuk', 5, 2)->nullable();
            $table->enum('status_kelulusan', ['proses', 'lulus', 'tidak_lulus', 'cadangan'])->default('proses');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_pendaftaran_spmb');
    }
};