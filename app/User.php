<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

use Illuminate\Support\Facades\Hash;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'email', 'password', 'address', 'gender', 'birthday', 'phone', 'avg_hiker_vote', 'avg_driver_vote'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at', 'delete_at', 'password','api_token', 'role'
    ];

    /**
     * @param $email
     * @param $password
     * @return bool|mixed
     */
    static function verify($phone, $password){
        $user = new User();
        $userVerify = $user->where('phone', $phone)->first();
        if($userVerify && $password == $userVerify->password){
            return $userVerify->id;
        }
        return false;
    }
}
