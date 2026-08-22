<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificatePayment extends Model
{
    use HasFactory;
    protected $table = 'certificates_payments';

      protected $fillable = [
        'paid',
        'voucher',
        'transcation_no',
        'payment_date',
        'ip_address'
    ];

    public function studentsDetails()
    {
        return $this->belongsTo(StudentFib::class, 'student_index_no', 'student_index_no');
    }
}
