<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/clear', function () {

    Artisan::call('optimize');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    return 'Caching, routes, and configuration cleared successfully.';
})->name('clear-all');

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest:web')->group(function () {

    Route::get('/', [AuthController::class, 'loginForm'])->name('login');

    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('admin')->group(function () {

    Route::get('/dashboard', [SettingsController::class, 'index'])->name('dashboard');

    Route::post('/settings/ip', [SettingsController::class, 'updateIp'])->name('settings.ip.update');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::post('/sync/start', [SettingsController::class, 'startSync'])->name('sync.start');

    Route::get('/get-majors/{faculty}', function ($faculty) {

        return \App\Models\Major::where('faculty_code', $faculty)->orderBy('major_code')
            ->get([
                'major_code',
                'major_desc_e'
            ]);

    });
});



/*
|--------------------------------------------------------------------------
| Admin Only
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:web', 'admin'])->group(function () {

    Route::get('/users', function () {
        return 'Admin Area';
    });

});



