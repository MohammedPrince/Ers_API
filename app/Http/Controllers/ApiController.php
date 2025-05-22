<?php

namespace App\Http\Controllers;

use App\Services\ErsMainService;
use App\Http\Requests\Api\StudentInquiryRequest;
use App\Http\Requests\Api\StudentPaymentRequest;

use Illuminate\Support\Facades\Http;

class ApiController extends Controller
{

    protected $ersMainService;

    public function __construct(ErsMainService $ersMainService)
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

    public function fetchFromLive()
    {
        $response = Http::get('https://api.fu.edu.sd/api/getData');

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Failed to fetch data'], 500);
    }

    public function fetchDataFromLocal()
    {
        $response = Http::get('http://196.1.204.142:8010/api/index.php');

        if ($response->successful()) {

            $content = $response->body();
            $decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return response()->json($decoded);
            }

            return response()->json([
                'status' => 'success',
                'message' => $content,
            ]);
        }

        return response()->json(['error' => 'Failed to fetch data'], 500);
    }


    public function getData()
    {
        $var = 'hello World from fu.edu.sd';
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => $var,
        ], 200);
    }

}

