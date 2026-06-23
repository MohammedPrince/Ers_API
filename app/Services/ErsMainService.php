<?php

namespace App\Services;

use App\Repositories\ErsMainRepository;
use Illuminate\Support\Facades\Log;

class ErsMainService
{
    protected $ersMainRepository;

    public function __construct(ErsMainRepository $ersMainRepository)
    {
        $this->ersMainRepository = $ersMainRepository;
    }

    //Web Services
    public function login($data)
    {
        return $this->ersMainRepository->login($data);
    }

    //API Services
    public function bankLogin($data)
    {
        return $this->ersMainRepository->bankLogin($data);
    }

    // public function bankLoginStudValidation($data)
    // {
    //     return $this->ersMainRepository->bankLoginStudValidation($data);
    // }

    public function studentInquiry($data)
    {
        return $this->ersMainRepository->studentInquiry($data);
    }

    public function studentPayment($data)
    {
        return $this->ersMainRepository->studentPayment($data);
    }

    public function reconcilePayment($data)
    {
        return $this->ersMainRepository->reconcilePayment($data);
    }

    public function saveLocalServerData($response)
    {
        return $this->ersMainRepository->saveLocalServerData($response);
    }

    //Logs
    public function writeLog(string $message, array $context = []): void
    {
        Log::channel('ersLogs')->info($message, $context);
    }

    public function writeError(string $message, array $context = []): void
    {
        Log::channel('ersLogs')->error($message, $context);
    }
}
