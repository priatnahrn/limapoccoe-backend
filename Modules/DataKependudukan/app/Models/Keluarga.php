<?php

namespace Modules\DataKependudukan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Modules\DataKependudukan\Models\Penduduk;
use Modules\DataKependudukan\Models\Rumah;

// use Modules\DataKependudukan\Database\Factories\KeluargaFactory;

class Keluarga extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'keluargas';
    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'id';  
    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nomor_kk',
        'rumah_id', // Foreign key to Rumah
    ];

    /**
     * Relasi: 1 Keluarga punya banyak Penduduk
     */
    public function penduduks()
    {
        return $this->hasMany(Penduduk::class); // pastikan namespace Penduduk benar
    }

    /**
     * Relasi: 1 Keluarga belongs to 1 Rumah
     */
    public function rumah()
    {
        return $this->belongsTo(Rumah::class, 'rumah_id'); //
    }
    // protected static function newFactory(): KeluargaFactory
    // {
    //     // return KeluargaFactory::new();
    // }

}
