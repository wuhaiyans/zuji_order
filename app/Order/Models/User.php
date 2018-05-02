<?php

namespace App\Order\Models;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject as AuthenticatableUserContract;
class User extends Authenticatable implements AuthenticatableUserContract
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
    public function getJWTIdentifier()
    {
        return $this->getKey(); // Eloquent model method
    }
    /**
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}