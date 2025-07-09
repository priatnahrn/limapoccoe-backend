<?php

namespace Modules\PengajuanSurat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Modules\Auth\Models\AuthUser;
use Modules\PengajuanSurat\Models\Ajuan;

// use Modules\PengajuanSurat\Database\Factories\TandaTanganFactory;

class TandaTangan extends Model
{
    use HasFactory, HasUuids;
    /**
     * The table associated with the model.
     */
    protected $table = 'tanda_tangans';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * Indicates if the model's ID is auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'ajuan_id',
        'signed_by',
        'signature',
        'signature_data',
        'signed_at',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'signed_at' => 'datetime',
    ];

    // protected static function newFactory(): TandaTanganFactory
    // {
    //     // return TandaTanganFactory::new();
    // }

    public function ajuan()
    {
        return $this->belongsTo(Ajuan::class, 'ajuan_id');
    }

    public function signedBy()
    {
        return $this->belongsTo(AuthUser::class, 'signed_by');
    }
}
