<?php

use Illuminate\Http\Request;
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

//Public Routes
Route::post('/login', [AuthController::class, 'login'])->middleware(['api', 'JsonRes']);

//Protecting Routes
Route::group(['middleware' => ['auth:sanctum']], function (){

    Route::post('/inquiry', [ApiController::class, 'studentInquiry']);
    Route::post('/payment', [ApiController::class, 'studentPayment']);
    // Route::post('/logout', [AuthController::class, 'logout']);
});
