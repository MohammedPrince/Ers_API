<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentFib extends Model
{
  use HasFactory;

  public $timestamps = false;
  protected $table = 'fu_student_fee_fib_latest';

  public function faculty()
  {
    return $this->belongsTo(Faculty::class, 'faculty_code', 'faculty_code');
  }

  public function major()
  {
    return $this->belongsTo(Major::class, 'major_code', 'major_code');
  }

  public function registrationDetails()
  {
    return $this->belongsTo(FibFlag::class, 'student_index_no', 'student_index_no');
  }
}
