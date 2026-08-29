<?php

namespace App\Repositories;

use App\Models\BankUser;
use App\Models\CertificatePayment;
use App\Models\ERSUser;
use App\Models\FibFlag;
use App\Models\PaymentFib;
use App\Models\StudentFib;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class ErsMainRepository
{

    public function login($data)
    {
        $user = ERSUser::where(
            'user_login_name',
            $data->input('username')
        )->first();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid username'
            ];
        }

        if ($user->password != $data->input('password')) {
            return [
                'success' => false,
                'message' => 'Invalid password'
            ];
        }

        Auth::guard('web')->login($user);

        $data->session()->regenerate();

        return [
            'success' => true,
            'message' => 'Login successful'
        ];
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

        //View Data: 0 :: Offline Cannot pay.
        //View Data: 1 :: Online Can pay.
        //View Data: 2 :: Already Paid.

        if (!Auth::check() || !Auth::user()) {
            return ['success' => false, 'code' => 401, 'message' => 'Unauthorized access, token missmatch'];
        }

        $studentData = [];
        $stud_id = trim($data['stud_id']);
        $start_date = null;
        $end_date = null;
        $start_date_admission = null;
        $total_bank_fee_admission = null;
        $current_date = Carbon::now()->format('Y-m-d');
        $academic_year = '2026';

        //Certificate Payment: Check and fetch student data from certificates_payments table if stud_id starts with 6 or contains 6.

        if (str_starts_with($stud_id, '5') || str_starts_with($stud_id, '6')) {

            $student_data_CERT = CertificatePayment::where('bill_id', $stud_id)->with(['studentsDetails'])->first();

            if (!$student_data_CERT) {
                return [
                    'success' => false,
                    'code' => 400,
                    'message' => 'Student not found.',
                ];
            }
            if ($student_data_CERT) {

                if ($current_date > $student_data_CERT->due_date) {
                    return ['success' => false, 'code' => 403, 'message' => 'Registration closed',];
                }

                if ($student_data_CERT->total_amount == 0) {
                    return ['success' => false, 'code' => 400, 'message' => 'Fees not available',];
                }

                $studentData = [
                    'student_index_no' => $student_data_CERT->student_index_no,
                    'student_name' => trim($student_data_CERT->studentsDetails->student_name_en),
                    'faculty' => $student_data_CERT->studentsDetails->faculty->faculty_desc_e,
                    'major' => trim($student_data_CERT->studentsDetails->major->major_desc_e),
                    'dept' => trim($student_data_CERT->studentsDetails->dept),
                    'batch' => trim($student_data_CERT->batch),
                    'semester' => $student_data_CERT->semester,
                    'total_fee' => $student_data_CERT->total_amount,
                ];

                return ['success' => true, 'code' => 200, 'message' => 'Student data successfully fetched', 'studentData' => $studentData,];
            }
        }



        $student_data = StudentFib::where('student_index_no', $stud_id)->where('academic_year', $academic_year)->with(['registrationDetails', 'faculty', 'major'])->first();

        if (!$student_data) {
            return [
                'success' => false,
                'code' => 400,
                'message' => 'Student not found.',
            ];
        }

        if (!$student_data->registrationDetails) {
            return [
                'success' => false,
                'code' => 400,
                'message' => 'Registration details not found'
            ];
        }

        // Total fees check
        if ($student_data->total_fee != $student_data->registrationDetails->total_fee_bank) {

            return [
                'success' => false,
                'code' => 400,
                'message' => 'Fee information is incorrect. Check with the faculty',
            ];
        }

        if ($student_data) {

            $start_date = $student_data->registrationDetails->start_date;
            $end_date = $student_data->registrationDetails->end_date;
            $viewData = $student_data->registrationDetails->viewData ?? 0;
            $totalBankFee = $student_data->registrationDetails->total_fee_bank ?? 0;
            $offLine = $student_data->registrationDetails->viewData ?? 0;

            // Check if stud_id starts with XX-
            if (preg_match('/^(\d{2})-/', $stud_id, $matches)) {

                $batch = '20' . $matches[1]; // 23 -> 2023, 24 -> 2024

                $start_date = DB::table('admission_register_setup')->where('batch', $batch)->value('start_date');
                $totalBankFee = $student_data->total_fee;
                $viewData = 1;
                $offLine = 1;

            }

            $studentData = [
                'student_index_no' => $student_data->student_index_no,
                'student_name' => trim($student_data->student_name_en),
                'faculty' => trim($student_data->faculty->faculty_desc_e),
                'major' => trim($student_data->major->major_desc_e),
                'dept' => trim($student_data->dept),
                'batch' => trim($student_data->batch),
                'semester' => $student_data->semester,
                'total_fee' => $totalBankFee,
            ];

            if ($offLine == 0) {
                return ['success' => false, 'code' => 400, 'message' => 'Student offline',];
            }

            if ($totalBankFee == 0) {
                return ['success' => false, 'code' => 400, 'message' => 'Fees not available',];
            }

            if ($current_date > $end_date) {
                return ['success' => false, 'code' => 403, 'message' => 'Registration closed',];
            }

            if ($viewData === 2) {
                return ['success' => false, 'code' => 409, 'message' => 'Student already paid',];
            }

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

        $stud_id = trim($data['stud_id']);
        $amount = trim($data['amount']);
        $bank_code = 2;
        $branch_code = 1;
        $voucher = trim($data['voucher']);
        $transcation_no = trim($data['transcation_no']);
        $date = $data['date'];

        $student_name_en = null;
        $faculty = null;
        $major = null;
        $dept = null;
        $batch = null;
        $semester = null;
        $total_fee = null;
        $viewData = null;
        $start_date = null;
        $end_date = null;
        $academic_year = '2026';

        $current_date = Carbon::now()->format('Y-m-d');

        //Certificate Payment: Check and fetch student data from certificates_payments table if stud_id starts with 6 or contains 6.

        if (str_starts_with($stud_id, '5') || str_starts_with($stud_id, '6')) {

            $student_data_CERT = CertificatePayment::where('bill_id', $stud_id)->with(['studentsDetails'])->first();

            if (!$student_data_CERT) {
                return [
                    'success' => false,
                    'code' => 400,
                    'message' => 'Student not found.',
                ];
            }

            if ($student_data_CERT) {

                if ($current_date > $student_data_CERT->due_date) {
                    return ['success' => false, 'code' => 403, 'message' => 'Registration closed',];
                }

                if ($student_data_CERT->total_amount == 0) {
                    return ['success' => false, 'code' => 400, 'message' => 'Fees not available',];
                }

                if ($student_data_CERT->total_amount != $amount) {
                    return ['success' => false, 'code' => 400, 'message' => 'Amount not correct'];
                }

                if ($current_date > $date) {
                    return ['success' => false, 'code' => 400, 'message' => 'Invalid Date'];
                }

                if ($student_data_CERT->voucher == $voucher) {
                    return ['success' => false, 'code' => 409, 'message' => 'Student already paid'];
                }

                $certData = [
                    'paid' => 1,
                    'voucher' => $voucher,
                    'transcation_no' => $transcation_no,
                    'ip_address' => $bank_ip,
                    'payment_date' => Carbon::parse($date)->format('Y-m-d'),
                    'updated_at' => Carbon::now(),
                ];

                $updateCertPayment = $student_data_CERT->update($certData);
                if ($updateCertPayment) {
                    return ['success' => true, 'code' => 200, 'message' => 'Student payment successfully done'];
                }
            }
        }

        $student_data = StudentFib::where('student_index_no', $stud_id)->where('academic_year', $academic_year)->with(['registrationDetails', 'faculty', 'major'])->first();

        if ($student_data) {

            // $start_date = $student_data->registrationDetails->start_date;
            // $viewData = $student_data->registrationDetails->viewData;

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
            $discount = $student_data->discount;
            $faculty_code = $student_data->faculty_code;
            $major_code = $student_data->major_code;
            $currency = $student_data->currency;
            $start_date = $student_data->registrationDetails->start_date ?? null;
            $end_date = $student_data->registrationDetails->start_date ?? null;
            $end_date = $student_data->registrationDetails->end_date ?? null;
            $viewData = $student_data->registrationDetails->viewData ?? null;

            // Check if stud_id starts with XX-
            if (preg_match('/^(\d{2})-/', $stud_id, $matches)) {
                $batch = '20' . $matches[1]; // 23 -> 2023, 24 -> 2024
                $start_date = DB::table('admission_register_setup')->where('batch', $batch)->value('start_date');
                $end_date = DB::table('admission_register_setup')->where('batch', $batch)->value('end_date');
                $total_fee = $student_data->total_fee;
                $viewData = 1;
            }

            if ($amount != $total_fee) {
                return ['success' => false, 'code' => 400, 'message' => 'Amount not correct'];
            }

            if ($current_date > $end_date) {
                return ['success' => false, 'code' => 400, 'message' => 'Registration closed'];
            }

            if ($current_date > $date) {
                return ['success' => false, 'code' => 400, 'message' => 'Invalid Date'];
            }

            $paymentCheck = PaymentFib::where('voucher', $voucher)->first();
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
                    //Set remark
                    'remark' => $bank_name
                ];

                $addPayment = PaymentFib::create($paymentData);
                $fibFlag = FibFlag::find($student_index_no);

                if ($addPayment) {
                    //Create row in fu_student_fee_fib_flag_local if not exist
                    if (!$fibFlag) {

                        $fibFlag = FibFlag::create([
                            'student_index_no' => $stud_id,
                            'update_flag' => 1,
                            'date' => Carbon::now()->toDateString(), // 2026-06-24
                            'total_fee_bank' => $total_fee,
                            'viewData' => 2,
                            'start_date' => $start_date,
                            'end_date' => $end_date,
                            'user_id' => 0,
                            'del' => 0,
                        ]);
                    }
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

    public function reconcilePayment($data)
    {
        $transaction_id = $data['transaction_id'];

        $paymentCheck = PaymentFib::where('voucher', $transaction_id)->first();
        if ($paymentCheck) {
            return ['success' => true, 'code' => 200, 'message' => 'Transaction ID exists, Student already paid!'];
        } elseif (!$paymentCheck) {
            return ['success' => false, 'code' => 404, 'message' => 'Transaction ID not exists!'];
        } else {
            return ['success' => true, 'code' => 400, 'message' => 'Error in transaction check!'];
        }
    }


    public function saveLocalServerData($response): array
    {

        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        $startTime = microtime(true);
        $currentStep = 'Initialization';

        try {

            if (!$response->successful()) {

                return [
                    'success' => false,
                    'code' => $response->status(),
                    'failed_step' => 'Remote Request',
                    'message' => 'Remote server returned HTTP ' . $response->status(),
                ];
            }

            $raw = $response->body();

            $data = json_decode($raw, true);

            if (json_last_error() !== JSON_ERROR_NONE) {

                Log::error(
                    'JSON Decode Error: ' . json_last_error_msg()
                );

                Log::debug(
                    'Response Preview',
                    [
                        'raw' => substr($raw, 0, 1000)
                    ]
                );

                return [
                    'success' => false,
                    'code' => 500,
                    'failed_step' => 'JSON Decode',
                    'message' => 'Invalid JSON response received.',
                ];
            }

            $studentDetails =
                $data['LocalServerData']['studentDetails']
                ?? [];

            $batchControlDetails =
                $data['LocalServerData']['batchControlDetails']
                ?? [];

            $localFlagDetails =
                $data['LocalServerData']['studentFlagDetails']
                ?? [];

            $semRegistrationDetails =
                $data['LocalServerData']['semesterRegisterDetails']
                ?? [];

            $studentFeeDetails =
                $data['LocalServerData']['studentFeeDetails']
                ?? [];


            $studentFeeFUDetails =
                $data['LocalServerData']['studentFeeFUDetails']
                ?? [];

            $studentDiscountDetails =
                $data['LocalServerData']['studentDiscountDetails']
                ?? [];

            $certificatePayments =
                $data['LocalServerData']['certificatePayments']
                ?? [];

            Log::channel('ersLogs')->info(
                'Synchronization Started',
                [
                    'students' => count($studentDetails),
                    'batch_control' => count($batchControlDetails),
                    'semester_register' => count($semRegistrationDetails),
                    'local_flags' => count($localFlagDetails),
                    'student_fee' => count($studentFeeDetails),
                    'student_fee_fu' => count($studentFeeFUDetails),
                    'student_discount' => count($studentDiscountDetails),
                    'certificate_payments' => count($certificatePayments),
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Certificate Payments
            |--------------------------------------------------------------------------
            */

            if (!empty($certificatePayments)) {

                $currentStep = 'Certificate Payments';

                DB::beginTransaction();

                Log::channel('ersLogs')->info(
                    'Certificate Payments Sync Started',
                    [
                        'count' => count($certificatePayments)
                    ]
                );

                $this->upsertCertificatePayments(
                    $certificatePayments
                );

                DB::commit();

                Log::channel('ersLogs')->info(
                    'Certificate Payments  Sync Completed',
                    [
                        'count' => count($certificatePayments)
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Batch Control
            |--------------------------------------------------------------------------
            */

            if (!empty($batchControlDetails)) {

                $currentStep = 'Batch Control';

                DB::beginTransaction();

                Log::channel('ersLogs')->info(
                    'Batch Control Sync Started',
                    [
                        'count' => count($batchControlDetails)
                    ]
                );

                $this->upsertBatchControl(
                    $batchControlDetails
                );

                DB::commit();

                Log::channel('ersLogs')->info(
                    'Batch Control Sync Completed',
                    [
                        'count' => count($batchControlDetails)
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Student Details
            |--------------------------------------------------------------------------
            */

            if (!empty($studentDetails)) {

                $currentStep = 'Student Details';

                DB::beginTransaction();

                Log::channel('ersLogs')->info(
                    'Student Sync Started',
                    [
                        'count' => count($studentDetails)
                    ]
                );

                $this->upsertStudentFeeLatest(
                    $studentDetails
                );

                DB::commit();

                Log::channel('ersLogs')->info(
                    'Student Sync Completed',
                    [
                        'count' => count($studentDetails)
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Semester Registration
            |--------------------------------------------------------------------------
            */

            if (!empty($semRegistrationDetails)) {

                $currentStep = 'Semester Registration';

                DB::beginTransaction();

                Log::channel('ersLogs')->info(
                    'Semester Registration Sync Started',
                    [
                        'count' => count($semRegistrationDetails)
                    ]
                );

                $this->upsertSemRegistration(
                    $semRegistrationDetails
                );

                DB::commit();

                Log::channel('ersLogs')->info(
                    'Semester Registration Sync Completed',
                    [
                        'count' => count($semRegistrationDetails)
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Student Fee
            |--------------------------------------------------------------------------
            */

            if (!empty($studentFeeDetails)) {

                $currentStep = 'Student Fee';

                DB::beginTransaction();

                Log::channel('ersLogs')->info(
                    'Student Fee Sync Started',
                    [
                        'count' => count($studentFeeDetails)
                    ]
                );

                $this->upsertStudentFee(
                    $studentFeeDetails
                );

                DB::commit();

                Log::channel('ersLogs')->info(
                    'Student Fee Sync Completed',
                    [
                        'count' => count($studentFeeDetails)
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Local Flags
            |--------------------------------------------------------------------------
            */

            if (!empty($localFlagDetails)) {

                $currentStep = 'Local Flag';

                DB::beginTransaction();

                Log::channel('ersLogs')->info(
                    'Local Flag Sync Started',
                    [
                        'count' => count($localFlagDetails)
                    ]
                );

                $this->upsertLocalFlag(
                    $localFlagDetails
                );

                DB::commit();

                Log::channel('ersLogs')->info(
                    'Local Flag Sync Completed',
                    [
                        'count' => count($localFlagDetails)
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Student Fee FU
            |--------------------------------------------------------------------------
            */

            if (!empty($studentFeeFUDetails)) {

                $currentStep = 'Student Fee Fu';

                DB::beginTransaction();

                Log::channel('ersLogs')->info(
                    'Student Fee Fu Sync Started',
                    [
                        'count' => count($studentFeeFUDetails)
                    ]
                );

                $this->upsertStudFeeFUDetails(
                    $studentFeeFUDetails
                );

                DB::commit();

                Log::channel('ersLogs')->info(
                    'Student Fee Fu Sync Completed',
                    [
                        'count' => count($studentFeeFUDetails)
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Student Discount
            |--------------------------------------------------------------------------
            */

            if (!empty($studentDiscountDetails)) {

                $currentStep = 'Student Discount';

                DB::beginTransaction();

                Log::channel('ersLogs')->info(
                    'Student Discount Sync Started',
                    [
                        'count' => count($studentDiscountDetails)
                    ]
                );


                $this->upsertStudentDiscountDetails(
                    $studentDiscountDetails
                );

                DB::commit();

                Log::channel('ersLogs')->info(
                    'Student Discount Sync Completed',
                    [
                        'count' => count($studentDiscountDetails)
                    ]
                );
            }


            $duration = round(
                microtime(true) - $startTime,
                2
            );

            Log::channel('ersLogs')->info(
                'Synchronization Completed',
                [
                    'duration' => $duration . ' sec'
                ]
            );

            return [
                'success' => true,
                'code' => 200,
                'message' => 'Synchronization completed successfully.',
                'duration' => $duration . ' sec',
                'students_count' => count($studentDetails),
                'batch_control_count' => count($batchControlDetails),
                'semester_registration_count' => count($semRegistrationDetails),
                'local_flags_count' => count($localFlagDetails),
                'student_fee' => count($studentFeeDetails),
                'student_fee_fu' => count($studentFeeFUDetails),
                'discounts_count' => count($studentDiscountDetails),
                'certificate_payments' => count($certificatePayments),
                'LocalServerData' => $data,
            ];

        } catch (\Throwable $e) {

            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::channel('ersLogs')->error(
                'Synchronization Failed',
                [
                    'step' => $currentStep,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return [
                'success' => false,
                'code' => 500,
                'failed_step' => $currentStep,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function upsertCertificatePayments(array $certificatePayments): void
    {
        foreach ($certificatePayments as $certificatePayment) {

            $exists = DB::table('certificates_payments')->where('id', $certificatePayment['id'])->exists();

            if (!$exists) {

                DB::table('certificates_payments')->insert([

                    'id' => $certificatePayment['id'],
                    'student_index_no' => $certificatePayment['student_index_no'],
                    'student_name' => $certificatePayment['student_name'],
                    'faculty_code' => $certificatePayment['faculty_code'],
                    'major_code' => $certificatePayment['major_code'],
                    'semester' => $certificatePayment['semester'],
                    'batch' => $certificatePayment['batch'],
                    'payment_type' => $certificatePayment['payment_type'],
                    'bill_id' => $certificatePayment['bill_id'],
                    'ticket_number' => $certificatePayment['ticket_number'],
                    'courses' => $certificatePayment['courses'],
                    'certificate_number' => $certificatePayment['certificate_number'],
                    'certificate_type' => $certificatePayment['certificate_type'],
                    'price' => $certificatePayment['price'],
                    'total_amount' => $certificatePayment['total_amount'],
                    'due_date' => $certificatePayment['due_date'],
                    'paid' => $certificatePayment['paid'],
                    'voucher' => $certificatePayment['voucher'],
                    'transcation_no' => $certificatePayment['transcation_no'],
                    'payment_date' => $certificatePayment['payment_date'],
                    'ip_address' => $certificatePayment['ip_address'],
                    'created_by' => $certificatePayment['created_by'],
                    'created_at' => $certificatePayment['created_at'],
                    'updated_at' => $certificatePayment['updated_at'] ?? now(),
                ]);
            }
        }
    }

    private function upsertStudentFeeLatest(array $students): void
    {
        $now = now();

        foreach ($students as $index => $student) {

            if ($index % 100 === 0) {

                Log::channel('ersLogs')->info('Student sync progress', [
                    'current' => $index,
                    'total' => count($students)
                ]);
            }

            DB::table('fu_student_fee_fib_latest')->updateOrInsert(
                [
                    'student_index_no' => $student['student_index_no'],
                    'batch' => $student['batch'],
                    'semester' => $student['semester'],
                    'academic_year' => $student['academic_year'],
                    'faculty_code' => $student['faculty_code'],
                    'major_code' => $student['major_code'],
                ],
                [
                    'student_name_en' => $student['student_name_en'],
                    'dept' => $student['dept'],
                    'cty_description' => $student['cty_description'],
                    'fee_year' => $student['fee_year'],
                    'fee_semester' => $student['fee_semester'],
                    'discount' => $student['discount'] ?? 0,
                    'remarks' => $student['remarks'] ?? null,
                    'current_fee' => $student['current_fee'] ?? null,
                    'total_fee' => $student['total_fee'],
                    'currency' => $student['currency'],
                    'date' => $student['date'],
                    'fee_type' => $student['fee_type'] ?? null,
                    'status' => $student['status'] ?? null,
                    'allow_register' => $student['allow_register'] ?? null,
                    'cgpa' => $student['cgpa'] ?? null,
                    'repeater' => $student['repeater'] ?? null,
                    'fee_late_reg' => $student['fee_late_reg'] ?? 0,
                    'nationality' => $student['nationality'] ?? null,
                    'allow_late_register' => $student['allow_late_register'] ?? 0,
                    'user_name' => $student['user_name'] ?? null,
                    'student_del' => $student['student_del'] ?? 0,
                    'date_time' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function upsertBatchControl(array $batchControlDetails): void
    {
        $now = now();
        foreach ($batchControlDetails as $batchControl) {
            DB::table('batch_control')->updateOrInsert(
                [
                    'dept_batch_id' => $batchControl['dept_batch_id'],
                ],
                [
                    'dept_code' => $batchControl['dept_code'],
                    'dept_name' => $batchControl['dept_name'],
                    'batch' => $batchControl['batch'],
                    'created_by' => $batchControl['created_by'],
                    'creation_date' => (
                        empty($batchControl['creation_date']) ||
                        $batchControl['creation_date'] === '0000-00-00'
                    )
                        ? date('Y-m-d')
                        : date('Y-m-d', strtotime($batchControl['creation_date'])),
                    'last_update_date' => (
                        empty($batchControl['last_update_date']) ||
                        $batchControl['last_update_date'] === '0000-00-00'
                    )
                        ? date('Y-m-d')
                        : date('Y-m-d', strtotime($batchControl['last_update_date'])),
                ]
            );
        }
    }

    private function upsertSemRegistration(array $semRegistrationDetails)
    {

        foreach ($semRegistrationDetails as $semRegistration) {
            DB::table('sem_registration_setup')->updateOrInsert(
                [
                    'sem_reg_id' => $semRegistration['sem_reg_id'],
                ],
                [
                    'faculty_code' => $semRegistration['faculty_code'],
                    'batch' => $semRegistration['Batch'],
                    'CurrentSem' => $semRegistration['CurrentSem'],
                    'registration_date_from' => $semRegistration['registration_date_from'],
                    'registration_date_to' => $semRegistration['registration_date_to'],
                    'reg_type' => $semRegistration['reg_type'],
                    'created_by' => $semRegistration['created_by'],
                    'creation_date' => $semRegistration['creation_date'],
                    'last_update_date' => $semRegistration['last_update_date'],
                ]
            );
        }
    }

    // private function upsertLocalFlag(array $localFlagDetails)
    // {
    //     foreach ($localFlagDetails as $localFlag) {

    //         // Check if student flag already exists
    //         $exists = DB::table('fu_student_fee_fib_flag_local')->where('student_index_no', $localFlag['student_index_no'])->exists();

    //         // If exists, don't insert
    //         if ($exists) {
    //             continue;
    //         }

    //         // Insert only if it doesn't exist
    //         DB::table('fu_student_fee_fib_flag_local')->insert([
    //             'student_index_no' => $localFlag['student_index_no'],
    //             'update_flag' => $localFlag['update_flag'],
    //             'date' => $localFlag['date'],
    //             'total_fee_bank' => $localFlag['total_fee_bank'],
    //             'viewData' => $localFlag['viewData'],
    //             'start_date' => $localFlag['start_date'],
    //             'end_date' => $localFlag['end_date'],
    //             'user_id' => $localFlag['user_id'],
    //             'del' => 0,
    //         ]);
    //     }
    // }

    private function upsertLocalFlag(array $localFlagDetails)
    {
        $now = now();
        foreach ($localFlagDetails as $localFlag) {
            DB::table('fu_student_fee_fib_flag_local')->updateOrInsert(
                [
                    'student_index_no' => $localFlag['student_index_no'],
                ],
                [
                    'update_flag' => $localFlag['update_flag'],
                    'date' => $localFlag['date'],
                    'total_fee_bank' => $localFlag['total_fee_bank'],
                    'viewData' => $localFlag['viewData'],
                    'start_date' => $localFlag['start_date'],
                    'end_date' => $localFlag['end_date'],
                    'user_id' => $localFlag['user_id'],
                    'del' => 0,
                ]
            );
        }
    }

    private function upsertStudentFee(array $studentFeeDetails)
    {
        $now = now();
        foreach ($studentFeeDetails as $studentFee) {
            DB::table('fu_student_fee_fib')->updateOrInsert(
                [
                    'student_fee_id' => $studentFee['student_fee_id'],
                ],
                [
                    'student_index_no' => $studentFee['student_index_no'],
                    'student_name_en' => $studentFee['student_name_en'],
                    'dept' => $studentFee['dept'],
                    'batch' => $studentFee['batch'],
                    'semester' => $studentFee['semester'],
                    'academic_year' => $studentFee['academic_year'],
                    'cty_description' => $studentFee['cty_description'],
                    'fee_year' => $studentFee['fee_year'],
                    'fee_semester' => $studentFee['fee_semester'],
                    'discount' => $studentFee['discount'],
                    'remarks' => $studentFee['remarks'],
                    'current_fee' => $studentFee['current_fee'],
                    'total_fee' => $studentFee['total_fee'],
                    'currency' => $studentFee['currency'],
                    'date' => $studentFee['date'],
                    'fee_type' => $studentFee['fee_type'],
                    'status' => $studentFee['status'],
                    'allow_register' => $studentFee['allow_register'],
                    'cgpa' => $studentFee['cgpa'],
                    'repeater' => $studentFee['repeater'],
                    'fee_late_reg' => $studentFee['fee_late_reg'],
                    'nationality' => $studentFee['nationality'],
                    'allow_late_register' => $studentFee['allow_late_register'],
                    'faculty_code' => $studentFee['faculty_code'],
                    'major_code' => $studentFee['major_code'],
                    'user_name' => $studentFee['user_name'],
                    'date_time' => now(),
                ]
            );
        }
    }

    private function upsertStudFeeFUDetails(array $studentFeeFUDetails)
    {
        $now = now();
        foreach ($studentFeeFUDetails as $studentFeeFU) {
            DB::table('student_fee_fu')->updateOrInsert(
                [
                    'StudentFeeId' => $studentFeeFU['StudentFeeId'],
                ],
                [
                    'faculty_code' => $studentFeeFU['faculty_code'],
                    'major_code' => $studentFeeFU['major_code'],
                    'Batch' => $studentFeeFU['Batch'],
                    'AcademicYear' => $studentFeeFU['AcademicYear'],
                    'Dept' => $studentFeeFU['Dept'],

                    'StdIndexNo' => $studentFeeFU['StdIndexNo'],
                    'StdNameEn' => $studentFeeFU['StdNameEn'],
                    'StdNameAr' => $studentFeeFU['StdNameAr'],
                    'CurrentSem' => $studentFeeFU['CurrentSem'],

                    'icdl_fees' => $studentFeeFU['icdl_fees'],
                    'TotalAmount' => $studentFeeFU['TotalAmount'],
                    'PayedAmount' => $studentFeeFU['PayedAmount'],
                    'InvoiceNo' => $studentFeeFU['InvoiceNo'],

                    'Currency' => $studentFeeFU['Currency'],
                    'RegistrationType' => $studentFeeFU['RegistrationType'],
                    'Remark' => $studentFeeFU['Remark'],
                    'Nationality' => $studentFeeFU['Nationality'],
                    'CTYDescription' => $studentFeeFU['CTYDescription'],
                    'ScriptDateTime' => $studentFeeFU['ScriptDateTime'],
                    'status' => $studentFeeFU['status'],
                    'cgpa' => $studentFeeFU['cgpa'],
                    'date' => $studentFeeFU['date'],

                    'repeater' => $studentFeeFU['repeater'],
                    'allow_late_register' => $studentFeeFU['allow_late_register'],
                    'StdIndexNoTemp' => $studentFeeFU['StdIndexNoTemp'],

                    'deleted' => $studentFeeFU['deleted'],
                    'Student_Group' => $studentFeeFU['Student_Group'],
                    'user_name' => $studentFeeFU['user_name'] ?? null,
                    'date_time' => $now,
                ]
            );
        }
    }


    private function upsertStudentDiscountDetails(array $studentDiscountDetails)
    {
        $now = now();
        foreach ($studentDiscountDetails as $studentDiscount) {
            DB::table('student_fee_discount')->updateOrInsert(
                [
                    'fee_discount_id' => $studentDiscount['fee_discount_id'],
                ],
                [
                    'student_index_no' => $studentDiscount['student_index_no'],
                    'dept_code' => $studentDiscount['dept_code'],
                    'batch' => $studentDiscount['batch'],
                    'academic_year' => $studentDiscount['academic_year'],
                    'semester' => $studentDiscount['semester'],
                    'discount_per' => $studentDiscount['discount_per'],
                    'remarks' => $studentDiscount['remarks'],
                    'created_by' => $studentDiscount['created_by'],
                    'creation_date' => $studentDiscount['creation_date'],
                    'last_update_date' => $now->format('Y-m-d'),
                    'faculty_code' => $studentDiscount['faculty_code'],
                    'major_code' => $studentDiscount['major_code'],
                ]
            );
        }
    }
}
