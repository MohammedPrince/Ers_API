<?php

namespace App\Services;

use App\Repositories\ErsMainRepository;

class ErsMainService
{
    protected $ersMainRepository;

    public function __construct(ErsMainRepository $ersMainRepository)
    {
        $this->ersMainRepository = $ersMainRepository;
    }

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
}
