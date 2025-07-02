<?php

namespace Modules\DataKependudukan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\DataKependudukan\Models\Keluarga;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Rumah extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'rumahs';

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     */
    protected $keyType = 'string';


    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'no_rmh',
        'rt_rw',
        'dusun',
    ];

    /**
     * Relasi: 1 Rumah punya banyak Keluarga
     */
    public function keluargas()
    {
        return $this->hasMany(Keluarga::class, 'keluarga_id'); // pastikan namespace Keluarga benar
    }
}
