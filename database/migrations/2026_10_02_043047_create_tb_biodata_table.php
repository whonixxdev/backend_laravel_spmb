<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_biodata', function (Blueprint $table) {
            $table->id('id_biodata');
            // Kunci UNIQUE agar 1 user hanya punya 1 row biodata
            $table->foreignId('id_user')->unique()->constrained('tb_users', 'id_user')->onDelete('cascade');
            $table->string('nama_lengkap');
            $table->string('nik', 16)->nullable();
            $table->string('nisn', 10)->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('no_hp_siswa', 20)->nullable();
            $table->string('no_hp_ortu', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->string('asal_smp')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_biodata');
    }
};