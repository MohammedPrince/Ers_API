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
Route::get('/getData', [App\Http\Controllers\ApiController::class, 'getData'])->middleware(['api', 'JsonRes']);
Route::get('/fetchData', [App\Http\Controllers\ApiController::class, 'fetchFromLive'])->middleware(['api', 'JsonRes']);
Route::get('/fetchDataFromLocal', [App\Http\Controllers\ApiController::class, 'fetchDataFromLocal'])->middleware(['api', 'JsonRes']);
//Public Routes
Route::post('/login', [App\Http\Controllers\API\AuthController::class, 'login'])->middleware(['api', 'JsonRes']);

//Protecting Routes
Route::group(['middleware' => ['auth:sanctum','JsonRes']], function (){

    Route::post('/inquiry', [App\Http\Controllers\ApiController::class, 'studentInquiry']);
    Route::post('/payment', [App\Http\Controllers\ApiController::class, 'studentPayment']);
    // Route::post('/logout', [AuthController::class, 'logout']);
});
