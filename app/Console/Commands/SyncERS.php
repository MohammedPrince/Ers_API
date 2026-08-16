<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\ApiController;
use App\Services\ErsMainService;

class SyncERS extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sync:ers';

    /**
     * The console command description.
     */
    protected $description = 'Synchronize ERS data from the configured faculty/major/batch/semester list';

    protected $ersMainService;

    public function __construct(ErsMainService $ersMainService)
    {
        parent::__construct();

        $this->ersMainService = $ersMainService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('========================================');
        $this->info('AUTO ERS Synchronization Started');
        $this->info('Time: ' . now());
        $this->info('========================================');

        $file = storage_path('app/ers_sync.txt');

        if (!file_exists($file)) {

            $this->error("Sync file not found: {$file}");

            $this->ersMainService->writeLog(
                "AUTO ERS Sync failed: sync file not found - {$file}"
            );

            return Command::FAILURE;
        }

        $apiController = new ApiController();
        $serverAddress = $apiController->getServerAddress();

        $lines = file(
            $file,
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
        );

        $total = count($lines);

        $successCount = 0;
        $failedCount = 0;
        $skippedCount = 0;

        /*
        |--------------------------------------------------------------------------
        | Prepare configurations
        |--------------------------------------------------------------------------
        */

        $configurations = [];

        foreach ($lines as $lineNumber => $line) {

            $lineNumber++;

            $line = trim($line);

            // Ignore comments
            if (str_starts_with($line, '#')) {
                $skippedCount++;
                continue;
            }

            // Split CSV
            $parts = array_map(
                'trim',
                explode(',', $line)
            );

            // Must have exactly 4 values
            if (count($parts) !== 4) {

                $this->warn(
                    "Line {$lineNumber}: Invalid format - {$line}"
                );

                $this->ersMainService->writeLog(
                    "ERS Sync skipped line {$lineNumber}: Invalid format - {$line}"
                );

                $skippedCount++;

                continue;
            }

            [
                $faculty_code,
                $major_code,
                $batch,
                $semester
            ] = $parts;

            // Validate
            if (
                !is_numeric($faculty_code) ||
                !is_numeric($major_code) ||
                !is_numeric($semester) ||
                !in_array((int) $semester, range(1, 10))
            ) {

                $this->warn(
                    "Line {$lineNumber}: Invalid parameters - {$line}"
                );

                $this->ersMainService->writeLog(
                    "ERS Sync skipped line {$lineNumber}: Invalid parameters - {$line}"
                );

                $skippedCount++;

                continue;
            }

            $configurations[] = [
                'line' => $lineNumber,
                'faculty_code' => $faculty_code,
                'major_code' => $major_code,
                'batch' => $batch,
                'semester' => $semester,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Group configurations by Faculty
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | Faculty 1 -> several majors
        | Faculty 2 -> several majors
        | Faculty 3 -> several majors
        |
        */

        $facultyGroups = [];

        foreach ($configurations as $config) {

            $faculty = $config['faculty_code'];

            if (!isset($facultyGroups[$faculty])) {
                $facultyGroups[$faculty] = [];
            }

            $facultyGroups[$faculty][] = $config;
        }

        /*
        |--------------------------------------------------------------------------
        | Split into batches of 3 FACULTIES
        |--------------------------------------------------------------------------
        */

        $facultyChunks = array_chunk(
            $facultyGroups,
            3,
            true
        );

        $totalBatches = count($facultyChunks);

        $this->info(
            "Total configuration lines: {$total}"
        );

        $this->info(
            "Unique faculties: " . count($facultyGroups)
        );

        $this->info(
            "Total batches: {$totalBatches}"
        );

        /*
        |--------------------------------------------------------------------------
        | Process batches
        |--------------------------------------------------------------------------
        */

        $batchNumber = 0;

        foreach ($facultyChunks as $facultyBatch) {

            $batchNumber++;

            $facultyList = implode(
                ', ',
                array_keys($facultyBatch)
            );

            $this->info('');
            $this->info('----------------------------------------');
            $this->info(
                "STARTING BATCH {$batchNumber}/{$totalBatches}"
            );
            $this->info(
                "Faculties: {$facultyList}"
            );
            $this->info('----------------------------------------');

            $this->ersMainService->writeLog(
                "ERS Batch {$batchNumber}/{$totalBatches} Started. " .
                "Faculties={$facultyList}"
            );

            /*
            |--------------------------------------------------------------------------
            | Process each faculty inside this batch
            |--------------------------------------------------------------------------
            */

            foreach ($facultyBatch as $faculty_code => $facultyConfigurations) {

                $this->info('');
                $this->info(
                    "Processing Faculty {$faculty_code}"
                );

                /*
                |--------------------------------------------------------------------------
                | Process all majors/configurations of this faculty
                |--------------------------------------------------------------------------
                */

                foreach ($facultyConfigurations as $config) {

                    $lineNumber = $config['line'];
                    $major_code = $config['major_code'];
                    $batch = $config['batch'];
                    $semester = $config['semester'];

                    $this->info(
                        "[Line {$lineNumber}] " .
                        "Faculty={$faculty_code}, " .
                        "Major={$major_code}, " .
                        "Batch={$batch}, " .
                        "Semester={$semester}"
                    );

                    try {

                        $url = "http://{$serverAddress}/ers/api/index.php?" .
                            http_build_query([
                                'faculty_code' => $faculty_code,
                                'major_code' => $major_code,
                                'batch' => $batch,
                                'semester' => $semester,
                            ]);

                        $this->ersMainService->writeLog(
                            "Auto Synchronization Started: " .
                            "Faculty={$faculty_code}, " .
                            "Major={$major_code}, " .
                            "Batch={$batch}, " .
                            "Semester={$semester}"
                        );

                        /*
                        |--------------------------------------------------------------------------
                        | HTTP Request
                        |--------------------------------------------------------------------------
                        */

                        // $response = Http::timeout(300)
                        //     ->connectTimeout(30)
                        //     ->get($url);

                        $response = Http::timeout(300)->get($url);

                        if (!$response->successful()) {

                            $failedCount++;

                            $this->error(
                                "FAILED: HTTP {$response->status()} - " .
                                "Faculty={$faculty_code}, " .
                                "Major={$major_code}, " .
                                "Batch={$batch}, " .
                                "Semester={$semester}"
                            );

                            $this->ersMainService->writeLog(
                                "Auto Synchronization FAILED: " .
                                "HTTP {$response->status()} - " .
                                "Faculty={$faculty_code}, " .
                                "Major={$major_code}, " .
                                "Batch={$batch}, " .
                                "Semester={$semester}"
                            );

                            continue;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Save Local Server Data
                        |--------------------------------------------------------------------------
                        */

                        $result =
                            $this->ersMainService
                                ->saveLocalServerData($response);

                        if ($result['success']) {

                            $successCount++;

                            $students =
                                $result['students_count'] ?? 0;

                            $this->info(
                                "SUCCESS: Faculty={$faculty_code}, " .
                                "Major={$major_code}, " .
                                "Batch={$batch}, " .
                                "Semester={$semester}"
                            );

                            $this->info(
                                "Students: {$students}"
                            );

                            $this->ersMainService->writeLog(
                                "Auto Synchronization SUCCESS: " .
                                "Faculty={$faculty_code}, " .
                                "Major={$major_code}, " .
                                "Batch={$batch}, " .
                                "Semester={$semester}"
                            );

                        } else {

                            $failedCount++;

                            $message =
                                $result['message']
                                ?? 'Auto Synchronization failed.';

                            $this->error(
                                "FAILED: {$message}"
                            );

                            $this->ersMainService->writeLog(
                                "Auto Synchronization FAILED: " .
                                "Faculty={$faculty_code}, " .
                                "Major={$major_code}, " .
                                "Batch={$batch}, " .
                                "Semester={$semester}. " .
                                $message
                            );
                        }

                    } catch (\Throwable $e) {

                        $failedCount++;

                        $this->error(
                            "EXCEPTION: " . $e->getMessage()
                        );

                        $this->ersMainService->writeLog(
                            "Auto Synchronization EXCEPTION: " .
                            "Faculty={$faculty_code}, " .
                            "Major={$major_code}, " .
                            "Batch={$batch}, " .
                            "Semester={$semester}. " .
                            $e->getMessage()
                        );

                        /*
                        |--------------------------------------------------------------------------
                        | Continue with next configuration
                        |--------------------------------------------------------------------------
                        */

                        continue;
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Batch Finished
            |--------------------------------------------------------------------------
            */

            $this->info('');
            $this->info(
                "BATCH {$batchNumber}/{$totalBatches} FINISHED"
            );

            $this->ersMainService->writeLog(
                "ERS Batch {$batchNumber}/{$totalBatches} Finished. " .
                "Faculties={$facultyList}"
            );

            /*
            |--------------------------------------------------------------------------
            | Optional small pause between batches
            |--------------------------------------------------------------------------
            |
            | Helps reduce server/database pressure.
            |
            */

            if ($batchNumber < $totalBatches) {

                $this->info(
                    "Waiting 10 seconds before next batch..."
                );

                sleep(10);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Final Result
        |--------------------------------------------------------------------------
        */

        $this->info('');
        $this->info('========================================');
        $this->info('AUTO ERS Synchronization Finished');
        $this->info('Successful: ' . $successCount);
        $this->info('Failed: ' . $failedCount);
        $this->info('Skipped: ' . $skippedCount);
        $this->info('Finished: ' . now());
        $this->info('========================================');

        $this->ersMainService->writeLog(
            "AUTO ERS Synchronization Finished. " .
            "Successful={$successCount}, " .
            "Failed={$failedCount}, " .
            "Skipped={$skippedCount}"
        );

        return $failedCount > 0
            ? Command::FAILURE
            : Command::SUCCESS;
    }
}