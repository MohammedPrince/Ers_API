<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Api\AuthController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/fetchData', [App\Http\Controllers\ApiController::class, 'fetchFromLive'])->middleware(['api', 'JsonRes']);
Route::get('/fetchDataFromLocal', [ApiController::class, 'fetchDataFromLocal']);
Route::get('/push', [App\Http\Controllers\ApiController::class, 'pushToLocalERS'])->middleware(['api', 'JsonRes']);
//Public Routes
Route::post('/login', [App\Http\Controllers\API\AuthController::class, 'login'])->middleware(['api', 'JsonRes']);

//Protecting Routes
Route::group(['middleware' => ['auth:sanctum', 'JsonRes']], function () {

    // Route::post('/inquiry', [App\Http\Controllers\ApiController::class, 'studentInquiry']);
    // Route::post('/payment', [App\Http\Controllers\ApiController::class, 'studentPayment']);
    // Route::post('/reconcile', [App\Http\Controllers\ApiController::class, 'reconcilePayment']);
    // Route::post('/logout', [AuthController::class, 'logout']);

    //Offline Routes
    Route::post('/inquiry', function () {
        return response()->json([
            'success' => false,
            'code' => 403,
            'message' => 'Service is currently unavailable.',
        ], 403);
    });

    Route::post('/payment', function () {
        return response()->json([
            'success' => false,
            'code' => 403,
            'message' => 'Service is currently unavailable.',
        ], 403);
    });

    Route::post('/reconcile', function () {
        return response()->json([
            'success' => false,
            'code' => 403,
            'message' => 'Service is currently unavailable.',
        ], 403);
    });
});

Route::get('/server-test', function () {
    return php_sapi_name() . ' | ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown');
});
