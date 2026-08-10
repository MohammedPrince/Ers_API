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
        $this->info('ERS Synchronization Started');
        $this->info('Time: ' . now());
        $this->info('========================================');

        $file = storage_path('app/ers_sync.txt');

        if (!file_exists($file)) {
            $this->error("Sync file not found: {$file}");

            $this->ersMainService->writeLog(
                "ERS Sync failed: sync file not found - {$file}"
            );

            return Command::FAILURE;
        }

        $apiController = new ApiController();
        $serverAddress = $apiController->getServerAddress();

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $total = count($lines);
        $successCount = 0;
        $failedCount = 0;
        $skippedCount = 0;

        $this->info("Total configuration lines: {$total}");

        foreach ($lines as $lineNumber => $line) {

            $lineNumber++;

            $line = trim($line);

            // Ignore comments
            if (str_starts_with($line, '#')) {
                $skippedCount++;
                continue;
            }

            // Split CSV
            $parts = array_map('trim', explode(',', $line));

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

            // Validate values
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

            $this->info(
                "[$lineNumber/$total] Syncing Faculty={$faculty_code}, " .
                "Major={$major_code}, Batch={$batch}, Semester={$semester}"
            );

            try {

                $url = "http://{$serverAddress}/ers/api/index.php?" . http_build_query([
                    'faculty_code' => $faculty_code,
                    'major_code'   => $major_code,
                    'batch'        => $batch,
                    'semester'     => $semester,
                ]);

                $this->ersMainService->writeLog(
                    "Synchronization Started: Faculty={$faculty_code}, " .
                    "Major={$major_code}, Batch={$batch}, Semester={$semester}"
                );

                $response = Http::timeout(300)->get($url);

                if (!$response->successful()) {

                    $failedCount++;

                    $this->error(
                        "FAILED: HTTP {$response->status()} - {$line}"
                    );

                    $this->ersMainService->writeLog(
                        "Synchronization FAILED: HTTP {$response->status()} - {$line}"
                    );

                    // Continue with next configuration
                    continue;
                }

                $result = $this->ersMainService->saveLocalServerData($response);

                if ($result['success']) {

                    $successCount++;

                    $this->info(
                        "SUCCESS: " .
                        ($result['message'] ?? 'Synchronization completed.')
                    );

                    $this->info(
                        "Students: " .
                        ($result['students_count'] ?? 0)
                    );

                    $this->ersMainService->writeLog(
                        "Synchronization SUCCESS: Faculty={$faculty_code}, " .
                        "Major={$major_code}, Batch={$batch}, Semester={$semester}"
                    );

                } else {

                    $failedCount++;

                    $this->error(
                        "FAILED: " .
                        ($result['message'] ?? 'Synchronization failed.')
                    );

                    $this->ersMainService->writeLog(
                        "Synchronization FAILED: Faculty={$faculty_code}, " .
                        "Major={$major_code}, Batch={$batch}, Semester={$semester}. " .
                        ($result['message'] ?? '')
                    );
                }

            } catch (\Throwable $e) {

                $failedCount++;

                $this->error(
                    "EXCEPTION on line {$lineNumber}: " . $e->getMessage()
                );

                $this->ersMainService->writeLog(
                    "Synchronization EXCEPTION: Faculty={$faculty_code}, " .
                    "Major={$major_code}, Batch={$batch}, Semester={$semester}. " .
                    $e->getMessage()
                );

                // IMPORTANT:
                // Don't stop the whole synchronization.
                // Continue with the next line.
                continue;
            }
        }

        $this->info('');
        $this->info('========================================');
        $this->info('ERS Synchronization Finished');
        $this->info('Successful: ' . $successCount);
        $this->info('Failed: ' . $failedCount);
        $this->info('Skipped: ' . $skippedCount);
        $this->info('Finished: ' . now());
        $this->info('========================================');

        $this->ersMainService->writeLog(
            "ERS Synchronization Finished. " .
            "Successful={$successCount}, " .
            "Failed={$failedCount}, " .
            "Skipped={$skippedCount}"
        );

        return $failedCount > 0
            ? Command::FAILURE
            : Command::SUCCESS;
    }
}