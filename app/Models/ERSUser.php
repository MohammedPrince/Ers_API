<?php

namespace App\Models;


use Illuminate\Foundation\Auth\User as Authenticatable;

class ERSUser extends Authenticatable
{
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $timestamps = false;

    protected $fillable = [
        'user_login_name',
        'password',
    ];

    public function getAuthIdentifierName()
    {
        return 'user_login_name';
    }
}