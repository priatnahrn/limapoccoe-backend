<?php

namespace Modules\Profile\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Modules\Auth\Models\AuthUser;
// use Modules\Auth\Database\Factories\AuthUserFactory;
// use Modules\Profile\Database\Factories\ProfileStaffFactory;

class ProfileStaff extends Model
{
     use HasFactory, HasUuids;

    protected $table = 'profile_staff';

    protected $fillable = [
        'user_id',
        'jabatan',
        'nip',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'alamat',
        'no_telepon',
        'pendidikan_terakhir',
    ];

    public function user()
    {
        return $this->belongsTo(\Modules\Auth\Models\AuthUser::class, 'user_id');
    }

}
