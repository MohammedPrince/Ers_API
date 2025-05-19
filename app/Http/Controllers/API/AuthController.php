<?php
namespace App\Http\Controllers\API;

use App\Models\FibFlag;
use App\Models\StudentFib;
use Auth;
use App\Models\User;
use App\Models\User_Token;
use Illuminate\Http\Request;
use App\Services\ErsMainService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BankAccessRequest;

class AuthController extends Controller
{
    protected $ersMainService;

    public function __construct(ErsMainService $ersMainService)
    {
        $this->ersMainService = $ersMainService;
    }

    public function login(BankAccessRequest $request)
    {

        $data = $request->validated();
        $result = $this->ersMainService->bankLogin($data);

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
                'token' => $result['token'] ?? Null,

            ], $result['code']);
        } else {
            return response()->json([
                'status' => 'error',
                'code' => $result['code'],
                'error' => $result['message'],
            ], $result['code'], );
        }
    }

    // method for user logout and delete token
    public function logout()
    {
        auth()->user()->tokens()->delete();

        return [
            'message' => 'You have successfully logged out and the token was successfully deleted'
        ];
    }
}
