<?php

namespace Modules\DataKependudukan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\DataKependudukan\Models\Keluarga;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Rumah extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'rumahs';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'no_rumah',
        'rt_rw',
        'dusun',
    ];

    public function keluargas()
    {
        return $this->hasMany(Keluarga::class, 'rumah_id');
    }
}
