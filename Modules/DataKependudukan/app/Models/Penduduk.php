<?php

namespace Modules\DataKependudukan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Modules\DataKependudukan\Models\Penduduk;
use Modules\DataKependudukan\Models\Rumah;

class Penduduk extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'penduduks';
    protected $primaryKey = 'id';
    public $incrementing = false;

    /**
     * Kolom-kolom yang boleh diisi dengan mass-assignment
     */
    protected $fillable = [
        'keluarga_id',
        'nik',
        'no_urut',
        'nama_lengkap',
        'hubungan',
        'tempat_lahir',
        'tgl_lahir',
        'jenis_kelamin',
        'status_perkawinan',
        'agama',
        'pendidikan',
        'pekerjaan',
        'no_bpjs',
        'nama_ayah',
        'nama_ibu',
    ];

    /**
     * Relasi: Penduduk milik satu Keluarga
     */
    public function keluarga()
    {
        return $this->belongsTo(Keluarga::class, 'keluarga_id');
    }
}
