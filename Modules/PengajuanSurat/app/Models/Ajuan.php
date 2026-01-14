<?php

namespace Modules\PengajuanSurat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Modules\Auth\Models\AuthUser;
use Modules\PengajuanSurat\Models\Surat;
use Modules\PengajuanSurat\Models\TandaTangan;
// use Modules\PengajuanSurat\Database\Factories\AjuanFactory;

class Ajuan extends Model
{
     use HasFactory, HasUuids;

    protected $table = 'ajuans';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $primaryKey = 'id';

    protected $casts = [
        'data_surat' => 'array',
        'lampiran' => 'array',
    ];

    protected $fillable = [
        'id',
        'user_id',
        'surat_id',
        'nomor_surat',
        'nomor_surat_tersimpan',
        'lampiran',
        'file',
        'data_surat',
        'qr_code_path',
        'status',
    ];

   
    public function user()
    {
        return $this->belongsTo(AuthUser::class, 'user_id');
    }

    public function surat()
    {
        return $this->belongsTo(Surat::class, 'surat_id');
    }

    public function tandaTangan()
    {
        return $this->hasOne(TandaTangan::class, 'ajuan_id');
    }

}
