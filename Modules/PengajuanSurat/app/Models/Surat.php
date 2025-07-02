<?php

namespace Modules\PengajuanSurat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Modules\Auth\Models\AuthUser;
use Modules\PengajuanSurat\Models\AjuanSurat;
// use Modules\PengajuanSurat\Models\TandaTangan;
// use Modules\PengajuanSurat\Database\Factories\SuratFactory;

class Surat extends Model
{
    use HasUuids, HasFactory;

    protected $table = 'surats';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $primaryKey = 'id';

    protected $fillable = [
        'kode_surat',
        'nama_surat',
        'slug',
        'deskripsi',
        'syarat_ketentuan',
    ];

    
    public function ajuan_surat()
    {
        return $this->hasMany(AjuanSurat::class, 'surat_id');
    }
    public function getRouteKeyName()
    {
        return 'slug';
    }

    protected static function booted()
    {
        static::creating(function ($surat) {
            if (empty($surat->slug)) {
                $cleanedName = \Str::of($surat->nama_surat)
                    ->lower()
                    ->replaceFirst('surat ', '')
                    ->slug('-');

                $surat->slug = 'surat-' . $cleanedName;
            }
        });
    }


}
