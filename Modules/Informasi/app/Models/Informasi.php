<?php

namespace Modules\Informasi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Informasi\Database\Factories\InformasiFactory;

class Informasi extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): InformasiFactory
    // {
    //     // return InformasiFactory::new();
    // }
}
