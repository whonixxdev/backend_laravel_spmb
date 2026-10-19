<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendaftaranSpmb extends Model
{
    use HasFactory;

    protected $table = 'tb_pendaftaran_spmb';
    protected $primaryKey = 'id_pendaftaran';

    protected $fillable = [
        'no_pendaftaran',
        'id_user',
        'id_jurusan_pilihan1',
        'id_jurusan_pilihan2',
        'id_biodata',
        'id_berkas',
        'nilai_tes_masuk',
        'status_kelulusan',
        'jurusan_diterima'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function jurusan1()
    {
        return $this->belongsTo(Jurusan::class, 'id_jurusan_pilihan1', 'id_jurusan');
    }

    public function jurusan2()
    {
        return $this->belongsTo(Jurusan::class, 'id_jurusan_pilihan2', 'id_jurusan');
    }

    public function jurusanDiterima()
    {
        return $this->belongsTo(Jurusan::class, 'jurusan_diterima', 'id_jurusan');
    }

    public function biodata()
    {
        return $this->belongsTo(Biodata::class, 'id_biodata', 'id_biodata');
    }

    public function berkas()
    {
        return $this->belongsTo(Berkas::class, 'id_berkas', 'id_berkas');
    }
}