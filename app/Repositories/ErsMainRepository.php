<?php

namespace App\Repositories;

use Carbon\Carbon;
use App\Models\FibFlag;
use App\Models\BankUser;
use App\Models\stud_fib;
use App\Models\PaymentFib;
use App\Models\StudentFib;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Api\StudentPaymentRequest;

class ErsMainRepository
{
    public function __construct()
    {
        //
    }

    public function bankLogin($data)
    {
        $bank_name = trim($data['bank_name']);
        $bank_password = trim($data['bank_password']);
        $bank_ip = trim($data['bank_ip']);

        $bank = BankUser::where('bank_name', $bank_name)->where('bank_ip', $bank_ip)->first();
        if ($bank) {
            if ($bank && Hash::check($bank_password, $bank->bank_password)) {
                $tokenResult = $bank->createToken('auth_token');
                $plainTextToken = $tokenResult->plainTextToken;
                return [
                    'success' => true,
                    'code' => 200,
                    'message' => 'Authorized access',
                    'token' => $plainTextToken,
                ];
            } else {
                return ['success' => false, 'code' => 401, 'message' => 'Unauthorized access',];
            }
        } else {
            return ['success' => false, 'code' => 401, 'message' => 'Unauthorized access',];
        }
    }

    public function studentInquiry($data)
    {

        if (!Auth::check() || !Auth::user()) {
            return ['success' => false, 'code' => 401, 'message' => 'Unauthorized access, token missmatch'];
        }

        $studentData = [];
        $stud_id = trim($data['stud_id']);
        $start_date = null;
        $current_date = Carbon::now()->format('Y-m-d');

        $student_data = StudentFib::where('student_index_no', $stud_id)->with(['registrationDetails', 'faculty', 'major'])->first();

        if ($student_data) {

            $start_date = $student_data->registrationDetails->start_date;
            $viewData = $student_data->registrationDetails->viewData;

            $studentData = [
                'student_index_no' => $student_data->student_index_no,
                'student_name' => $student_data->student_name_en,
                'faculty' => $student_data->faculty->faculty_desc_e,
                'major' => $student_data->major->major_desc_e,
                'dept' => $student_data->dept,
                'batch' => $student_data->batch,
                'semester' => $student_data->semester,
                'total_fee' => $student_data->total_fee,
                // 'start_date' => $student_data->registrationDetails->start_date,
                // 'end_date' => $student_data->registrationDetails->end_date,
                // 'viewData' => $student_data->registrationDetails->viewData,
                // 'current_date' => $current_date,
            ];

            if ($current_date > $start_date) {
                return ['success' => false, 'code' => 403, 'message' => 'Registration closed',];
            }

            // if ($viewData === 2) {
            //     return ['success' => false, 'code' => 409, 'message' => 'Student already paid',];
            // }

        } else {
            return ['success' => false, 'code' => 400, 'message' => 'Student not exist',];
        }

        return ['success' => true, 'code' => 200, 'message' => 'Student data successfully fetched', 'studentData' => $studentData,];
    }

    public function studentPayment($data)
    {

        if (!Auth::check() || !Auth::user()) {
            return ['success' => false, 'code' => 401, 'message' => 'Unauthorized access, token missmatch'];
        }

        $bank = Auth::user();
        $bank_name = $bank->bank_name;
        $bank_ip = $bank->bank_ip;

        $stud_id = $data['stud_id'];
        $amount = $data['amount'];
        $bank_code = 2;
        $branch_code = 1;
        $voucher = $data['voucher'];
        $transcation_no = $data['transcation_no'];
        $date = $data['date'];

        $student_name_en = null;
        $faculty = null;
        $major = null;
        $dept = null;
        $batch = null;
        $semester = null;
        $total_fee = null;
        $viewData = null;

        $current_date = Carbon::now()->format('Y-m-d');

        $student_data = StudentFib::where('student_index_no', $stud_id)->with(['registrationDetails', 'faculty', 'major'])->first();

        if ($student_data) {

            $start_date = $student_data->registrationDetails->start_date;
            $viewData = $student_data->registrationDetails->viewData;

            $student_index_no = $student_data->student_index_no;
            $student_name_en = $student_data->student_name_en;
            $faculty = $student_data->faculty->faculty_desc_e;
            $major = $student_data->major->major_desc_e;
            $dept = $student_data->dept;
            $batch = $student_data->batch;
            $semester = $student_data->semester;
            $cty_description = $student_data->cty_description;
            $academic_year = $student_data->academic_year;
            $fee_semester = $student_data->fee_semester;
            $fee_year = $student_data->fee_year;
            $total_fee = $student_data->total_fee;
            $fee_late_registration = $student_data->fee_late_registration;
            $fee_type = $student_data->fee_type;
            $fee_late_reg = $student_data->fee_late_reg;
            $current_fee = $student_data->current_fee;
            $total_fee = $student_data->total_fee;
            $discount = $student_data->discount;
            $faculty_code = $student_data->faculty_code;
            $major_code = $student_data->major_code;
            $currency = $student_data->currency;
            $start_date = $student_data->registrationDetails->start_date;
            $end_date = $student_data->registrationDetails->end_date;
            $viewData = $student_data->registrationDetails->viewData;

            // if ($current_date > $start_date) {
            //     return ['success' => false, 'code' => 400, 'message' => 'Registration closed',];
            // }

            $paymentCheck = PaymentFib::where('student_index_no', $stud_id)->where('voucher', $voucher)->first();
            if ($paymentCheck || $viewData === 2) {
                return ['success' => false, 'code' => 409, 'message' => 'Student already paid'];
            } else {
                //Do the insert to fu_student_fee_payment_fib
                $paymentData = [
                    'student_index_no' => $student_index_no,
                    'student_name_en' => $student_name_en,
                    'batch' => $batch,
                    'department' => $dept,
                    'academic_year' => $academic_year,
                    'semester' => $semester,
                    'cty_description' => $cty_description,
                    'fee_year' => (double) $fee_year,
                    'fee_semester' => (double) $fee_semester,
                    'fee_late_registration' => $fee_late_registration,
                    'fee_type' => $fee_type,
                    'current_fee' => (double) $current_fee,
                    'total_fee' => (double) $total_fee,
                    //Bank request start
                    'total_fee_paid' => (double) $amount,
                    'date' => Carbon::parse($date)->format('Y-m-d'),
                    'branch_code' => $branch_code,
                    'voucher' => $voucher,
                    'transcation_no' => $transcation_no,
                    //Bank request end
                    'bank_code' => $bank_code,
                    'ip_address' => $bank_ip,
                    'discount' => $discount,
                    'fee_late_reg' => $fee_late_reg,
                    'currency' => $currency,
                    'faculty_code' => $faculty_code,
                    'major_code' => $major_code,
                ];

                $addPayment = PaymentFib::create($paymentData);
                $fibFlag = FibFlag::find($student_index_no);

                if ($addPayment) {
                    //Update fu_student_fee_fib_flag_local set viewData = 2 (means student paid).
                    $fibFlag->viewData = 2;
                    if ($fibFlag->save()) {
                        //Delete token after payment successfully done, uncomment if needed.
                        // $bank->tokens()->where('name', 'auth_token')->delete();
                        return ['success' => true, 'code' => 200, 'message' => 'Student payment successfully done'];
                        // return ['success' => true, 'code' => 200, 'message' => 'Student payment successfully inserted', 'paymentDetails' => $paymentData];
                    }

                } else {
                    return ['success' => false, 'code' => 400, 'message' => 'Erorr in add payment'];
                }
            }

        } else {
            return ['success' => false, 'code' => 400, 'message' => 'Student not exist'];
        }

    }


}
