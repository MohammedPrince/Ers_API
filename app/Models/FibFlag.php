<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FibFlag extends Model
{
    use HasFactory;

    protected $table = 'fu_student_fee_fib_flag_local';

    protected $primaryKey = 'student_index_no';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'viewData'
    ];
}
