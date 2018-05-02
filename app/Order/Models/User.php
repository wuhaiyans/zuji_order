<?php

namespace App\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Auth;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{

    use Notifiable;
    protected $table = 'users';
    protected $primaryKey = 'id';
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
    /**
     * @return mixed
     */
    // Rest omitted for brevity

       public function getJWTIdentifier()
    {
        return $this->getKey();
    }

        public function getJWTCustomClaims()
    {
        return [];
    }
}