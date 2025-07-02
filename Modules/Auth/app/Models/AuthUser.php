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
    /**
     * The guard name for the model.
     *
     * @var string
     */
    protected $guard_name = 'api';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
   
    /**
     * The table associated with the model.
     */
    protected $table = 'auth_users';
    
    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'id';
    
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;
    
    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'string';
    
     /**
     * Indicates if the model should be guarded.
     *
     * @var bool
     */
    protected $guarded = false;

    /**
     * The attributes that are mass assignable.
     */
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

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'id' => 'string',
        'role_id' => 'string',
        'is_verified' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the identifier that will be stored in the JWT.
     *
     * @return string
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
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



   

    // protected static function newFactory(): AuthUserFactory
    // {
    //     // return AuthUserFactory::new();
    // }
}
