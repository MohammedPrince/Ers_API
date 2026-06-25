<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\ReconcileRequest;
use App\Http\Requests\Api\StudentInquiryRequest;
use App\Http\Requests\Api\StudentPaymentRequest;
use App\Services\ErsMainService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;


class ApiController extends Controller
{

    protected $ersMainService;
    public function __construct(ErsMainService $ersMainService = null)
    {
        $this->ersMainService = $ersMainService;
    }

    public function studentInquiry(StudentInquiryRequest $request)
    {
        $data = $request->validated();
        $result = $this->ersMainService->studentInquiry($data);
        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'] ?? Null,
                'data' => [
                    'studentData' => $result['studentData'] ?? [],
                ]
            ], $result['code']);
        } else {
            return response()->json([
                'status' => 'error',
                'code' => $result['code'],
                'error' => $result['message'],
            ], $result['code'], );
        }
    }

    public function studentPayment(StudentPaymentRequest $request)
    {
        $data = $request->validated();
        $result = $this->ersMainService->studentPayment($data);
        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'] ?? Null,
                // 'data' => [
                //     'paymentDetails' => $result['paymentDetails'] ?? [],
                // ]
            ], $result['code']);
        } else {
            return response()->json([
                'status' => 'error',
                'code' => $result['code'],
                'error' => $result['message'],
            ], $result['code'], );
        }
    }
    public function reconcilePayment(ReconcileRequest $request)
    {
        $data = $request->validated();
        $result = $this->ersMainService->reconcilePayment($data);
        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'] ?? Null,
            ], $result['code']);
        } else {
            return response()->json([
                'status' => 'error',
                'code' => $result['code'],
                'error' => $result['message'] ?? null,
            ], $result['code'], );
        }
    }

    public function fetchFromLive()
    {
        $response = Http::get('https://api.fu.edu.sd/api/getData');

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Failed to fetch data'], 500);
    }

    public function fetchDataFromLocal(Request $request)
    {

        $serverAddress = $this->getServerAddress();

        $validator = Validator::make($request->all(), [
            'faculty_code' => 'required|integer',
            'major_code' => 'required|integer',
            'batch' => 'required|',
            'semester' => 'required|integer|between:1,10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $faculty_code = $request->faculty_code;
        $major_code = $request->major_code;
        $batch = $request->batch;
        $semester = $request->semester;

        // dd($faculty_code, $major_code, $batch, $semester);

        // $url = "http://127.0.0.1:8000/ers/api/index.php?faculty_code={$faculty_code}&major_code={$major_code}&batch={$batch}&semester={$semester}";
        //$url = "http://196.1.204.142/ers/api/index.php?faculty_code={$faculty_code}&major_code={$major_code}&batch={$batch}&semester={$semester}";
        //$url = "http://156.204.9.217/api/index.php?faculty_code={$faculty_code}&major_code={$major_code}&batch={$batch}&semester={$semester}";

        $url = "http://{$serverAddress}/api/index.php?faculty_code={$faculty_code}&major_code={$major_code}&batch={$batch}&semester={$semester}";



        $response = Http::get($url);

        $result = $this->ersMainService->saveLocalServerData($response);

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'] ?? Null,
                'LocalServerData' => $result['LocalServerData'] ?? [],
            ], $result['code']);
        } else {
            return response()->json([
                'status' => 'error',
                'code' => $result['code'],
                'error' => $result['message'],
            ], $result['code'], );
        }
    }

    public function pushToLocalERS()
    {
        try {

            $payments = DB::table('fu_student_fee_payment_fib')->where('remark', 'BOK')->get();
            $flags = DB::table('fu_student_fee_fib_flag_local')->where('viewData', 2)->where('update_flag', 1)->get();
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Data fetched successfully',
                'LiveServerData' => [

                    'fu_student_fee_payment_fib' => $payments,
                    'fu_student_fee_fib_flag_local' => $flags,

                ]
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getServerAddress()
    {
        $file = storage_path('app/settings/server_ip.txt');
        if (!file_exists($file)) {
            return '127.0.0.1:8001';
        }
        return trim(file_get_contents($file));
    }

}

