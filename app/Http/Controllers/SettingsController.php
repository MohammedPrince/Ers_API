<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Services\ErsMainService;


class SettingsController extends Controller
{
    protected $ersMainService;
    public function __construct(ErsMainService $ersMainService = null)
    {
        $this->ersMainService = $ersMainService;
    }

    private $file = 'settings/server_ip.txt';

    public function index()
    {
        $apiController = new ApiController();

        $faculties = Faculty::where('deleted', 0)->orderBy('faculty_code')->get();

        $batches = DB::table('batch_control')
            ->select('batch')
            ->distinct()
            ->orderBy('batch', 'desc')
            ->get();

        return view('dashboard', [
            'ip' => $apiController->getServerAddress(),
            'faculties' => $faculties,
            'batches' => $batches,
        ]);
    }

    public function updateIp(Request $request)
    {
        $address = trim($request->server_ip);

        if (!preg_match('/^((\d{1,3}\.){3}\d{1,3})(:\d{1,5})?$/', $address)) {

            return redirect()
                ->route('dashboard')
                ->with('server_error', 'Invalid IP address.');
        }

        Storage::put(
            $this->file,
            trim($request->server_ip)
        );

        return redirect()
            ->route('dashboard')
            ->with('server_success', 'Server configuration saved successfully.');
    }

    public function startSync(Request $request)
    {
        $apiController = new ApiController();
        $serverAddress = $apiController->getServerAddress();

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

        $url = "http://{$serverAddress}/ers/api/index.php?faculty_code={$faculty_code}&major_code={$major_code}&batch={$batch}&semester={$semester}";

        $response = Http::get($url);

        $this->ersMainService->writeLog('Synchronization Started');

        $result = $this->ersMainService->saveLocalServerData($response);

        if ($result['success']) {

            return redirect()
                ->route('dashboard')
                ->withInput()
                ->with([
                    'sync_success' => $result['message'],
                    'students_count' => $result['students_count'] ?? 0,
                    'duration' => $result['duration'] ?? '0 sec',
                    'sync_details' => [
                        'Batch Control' => $result['batch_control_count'] ?? 0,
                        'Student Details' => $result['students_count'] ?? 0,
                        'Semester Registration' => $result['semester_registration_count'] ?? 0,
                        'Student Fee' => $result['student_fee'] ?? 0,
                        'Student Fee FU' => $result['student_fee_fu'] ?? 0,
                        'Local Discounts' => $result['discounts_count'] ?? 0,
                        'Local Flags' => $result['local_flags_count'] ?? 0,
                    ],
                ]);
        }

        return redirect()
            ->route('dashboard')
            ->withInput()
            ->with([
                'sync_error' => $result['message'] ?? 'Synchronization failed.',
                'failed_step' => $result['failed_step'] ?? null,
            ]);
    }

    public function fetchDataFromLocal(Request $request)
    {


    }
}