<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Traits\HasPermissions;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Modules\Auth\Database\Factories\AuthUserFactory;
use Modules\Profile\Models\ProfileMasyarakat;
use Modules\Profile\Models\ProfileStaff;

class AuthUser extends Authenticatable implements JWTSubject
{
    use HasFactory, HasUuids, HasRoles, HasPermissions;

    protected $guard_name = 'api';
    public $timestamps = true;
    protected $table = 'auth_users';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = ['id'];
    protected $fillable = [
        'id',
        'name',
        'email',
        'nik',
        'username',
        'no_whatsapp',
        'password',
        'role_id',
        'status',
        'is_verified',
        'remember_token',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function profileMasyarakat()
    {
        return $this->hasOne(ProfileMasyarakat::class, 'user_id');
    }

    public function profileStaff()
    {
        return $this->hasOne(ProfileStaff::class, 'user_id');
    }


}
