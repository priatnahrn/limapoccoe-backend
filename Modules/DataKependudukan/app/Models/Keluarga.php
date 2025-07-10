<?php
namespace Modules\DataKependudukan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Keluarga extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'keluargas';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'nomor_kk',
        'rumah_id',
    ];

    /**
     * Relasi: Satu Keluarga milik satu Rumah
     */
    public function rumah()
    {
        return $this->belongsTo(Rumah::class, 'rumah_id');
    }

    /**
     * Relasi: Satu Keluarga memiliki banyak Penduduk (anggota keluarga)
     */
    public function penduduks()
    {
        return $this->hasMany(Penduduk::class, 'keluarga_id');
    }

    // Alias semantik yang lebih jelas (opsional)
    public function anggota()
    {
        return $this->hasMany(Penduduk::class, 'keluarga_id');
    }
}
