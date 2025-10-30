<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notifiable;
use App\Consts;

class Admin extends Authenticatable
{
    use Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name','email', 'password'
    ];

    /**
     * The attributes that should be hidde n for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token'
    ];

    public function isPasswordChanged($newPassword)
    {
        return $this->password !== bcrypt($newPassword) || $newPassword !== Consts::CHAR_PASSWORD_HIDDEN;
    }
}
