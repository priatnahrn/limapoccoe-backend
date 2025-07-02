<?php

namespace Modules\Profile\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Modules\Auth\Models\AuthUser;
// use Modules\Profile\Database\Factories\ProfileMasyarakatFactory;

class ProfileMasyarakat extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'profile_masyarakats';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'golongan_darah',
        'alamat',
        'rt_rw',
        'dusun',
        'kelurahan',
        'kecamatan',
        'kabupaten_kota',
        'provinsi',
        'agama',
        'status_perkawinan',
        'pekerjaan',
        'kewarganegaraan',
        'pendidikan_terakhir',
    ];

    public function user()
    {
        return $this->belongsTo(AuthUser::class, 'user_id');
    }


}
