<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;
use Modules\Auth\Models\AuthUser;

class LogActivity extends Model
{
    use HasUuids;
    protected $table = 'log_activities';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'activity_type',
        'description',
        'ip_address',
    ];

    public function user()
    {
        return $this->belongsTo(AuthUser::class, 'user_id');
    }

}
