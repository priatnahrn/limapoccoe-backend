<?php

namespace Modules\Profile\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Modules\Auth\Models\AuthUser;
// use Modules\Auth\Database\Factories\AuthUserFactory;
// use Modules\Profile\Database\Factories\ProfileKepalaDesaFactory;

class ProfileKepalaDesa extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'profile_kepala_desas';

    protected $fillable = [
        'user_id',
        'nip',
        'tanggal_mulai_jabatan',
        'tanggal_akhir_jabatan',
        'alamat_kantor',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'no_telepon',
        'pendidikan_terakhir',
    ];

    public function user()
    {
        return $this->belongsTo(\Modules\Auth\Models\AuthUser::class, 'user_id');
    }
}
