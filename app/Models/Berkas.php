<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Berkas extends Model
{
    use HasFactory;

    protected $table = 'tb_berkas';
    protected $primaryKey = 'id_berkas';

    protected $fillable = [
        'id_user',
        'file_ijazah_skl',
        'file_kk',
        'file_aktd_kelahiran',
        'file_pasfoto',
        'status_verifikasi',
        'catatan_admin'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}