<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FibFlag extends Model
{
    use HasFactory;

    protected $primaryKey = 'student_index_no';

    protected $table = 'fu_student_fee_fib_flag_local';
    public $timestamps = false;
    protected $fillable = ['viewData'];
}
