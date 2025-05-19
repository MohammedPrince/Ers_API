<?php

namespace App\Http\Controllers;

use App\Services\ErsMainService;
use App\Http\Requests\Api\StudentInquiryRequest;
use App\Http\Requests\Api\StudentPaymentRequest;

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
}

