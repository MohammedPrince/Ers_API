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

        $url = 'http://196.1.204.142/api/index.php';
        $data = [];
        $currentUrl = request()->url();

        if (str_contains($currentUrl, 'http://127.0.0.1:8001/')) {
            // Add port 8010
            $parsed = parse_url($url);
            $hostWithPort = $parsed['host'] . ':8010';
            $url = "{$parsed['scheme']}://{$hostWithPort}{$parsed['path']}";
        } elseif (str_contains($currentUrl, 'https://api.fu.edu.sd/')) {
            $parsed = parse_url($url);
            $url = "{$parsed['scheme']}://{$parsed['host']}{$parsed['path']}";
        }

        $response = Http::get($url);
        $raw = $response->body();

        preg_match_all('/\{.*?\}(?=\{|\z)/s', $raw, $matches);

        foreach ($matches[0] as $jsonPart) {
            $decoded = json_decode($jsonPart, true);
            if (is_array($decoded)) {
                $data = array_merge($data, $decoded);
            }
        }

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Data fetched successfully',
            'data' => $data
        ]);
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

