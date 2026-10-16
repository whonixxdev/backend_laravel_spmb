<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'tb_users';
    protected $primaryKey = 'id_user';

    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
        'id_panitia',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // public function biodata()
    // {
    //     return $this->hasOne(Biodata::class, 'id_user', 'id_user');
    // }

    // public function berkas()
    // {
    //     return $this->hasOne(Berkas::class, 'id_user', 'id_user');
    // }

    // public function pendaftaran()
    // {
    //     return $this->hasOne(PendaftaranSpmb::class, 'id_user', 'id_user');
    // }
}