<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_Token extends Model
{
    use HasFactory;
 protected $table = 'personal_access_tokens';
}
