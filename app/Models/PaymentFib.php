<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentFib extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'fu_student_fee_payment_fib';
    protected $primaryKey = 'student_index_no';
    protected $fillable = [
        'student_index_no',
        'student_name_en',
        'batch',
        'status',
        'department',
        'academic_year',
        'semester',
        'cty_description',
        'fee_year',
        'fee_semester',
        'fee_late_registration',
        'fee_type',
        'current_fee',
        'total_fee',
        'total_fee_paid',
        'date',
        'branch_code',
        'voucher',
        'bank_code',
        'transcation_no',
        'ip_address',
        'discount',
        'fee_late_reg',
        'currency',
        'faculty_code',
        'major_code'
    ];

    public static function create(array $data)
    {

        $p = new PaymentFib();

        $p->student_index_no = $data['student_index_no'];
        $p->student_name_en = $data['student_name_en'];
        $p->batch = $data['batch'];
        $p->department = $data['department'];
        $p->academic_year = $data['academic_year'];
        $p->semester = $data['semester'];
        $p->cty_description = $data['cty_description'];
        $p->fee_year = $data['fee_year'];

        $p->fee_semester = $data['fee_semester'];
        $p->fee_late_registration = $data['fee_late_registration'] ?? 0;
        $p->fee_type = $data['fee_type'];
        $p->current_fee = $data['current_fee'];
        $p->total_fee = $data['total_fee'];
        $p->total_fee_paid = $data['total_fee_paid'];

        $p->date = $data['date'];
        $p->branch_code = $data['branch_code'];
        $p->voucher = $data['voucher'];
        $p->transcation_no = $data['transcation_no'];
        $p->bank_code = $data['bank_code'];
        $p->ip_address = $data['ip_address'];

        $p->discount = $data['discount'];
        $p->fee_late_reg = $data['fee_late_reg'];
        $p->currency = $data['currency'];
        $p->faculty_code = $data['faculty_code'];
        $p->major_code = $data['major_code'];

        $p->save();

        return $p;
    }
}
