<?php

namespace Modules\Pengaduan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Modules\Auth\Models\AuthUser;
// use Modules\Pengaduan\Database\Factories\PengaduanFactory;

class Pengaduan extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'pengaduans';

    /**
     * Indicates if the model's ID is auto-incrementing.
     */
    public $incrementing = false;

    

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'user_id',
        'title',
        'content',
        'location',
        'category',
        'evidence',
        'status',
        'response',
        'response_by',
        'response_date',
    ];

    

    // protected static function newFactory(): PengaduanFactory
    // {
    //     // return PengaduanFactory::new();
    // }

    public function user()
    {
        return $this->belongsTo(AuthUser::class, 'user_id');
    }

    public function responseBy()
    {
        return $this->belongsTo(AuthUser::class, 'response_by');
    }

    public function getRouteKeyName()
    {
        return 'id';
    }
}
