<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens; 

class BankUser extends Model
{
    use HasApiTokens,HasFactory;
    protected $table = 'bank_users';

    protected $fillable = [
        'bank_name',
        'bank_password',
    ];

    public static function create(array $data)
    {
        $b = new BankUser();

        $b->bank_name = $data['bank_name'];
        $b->bank_password = $data['bank_password'];
        $b->bank_ip = $data['bank_ip'];

        $b->save();

        return $b;
    }
}
