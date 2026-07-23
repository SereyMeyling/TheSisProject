<?php

use App\Http\Controllers\Department\DepartmentController;
use App\Http\Controllers\Support\SupportController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\TwoFactorController;

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

Route::get('/', function () {
    return view('auth.login');
});

Auth::routes([
    'register' => false,
    'reset' => false,
    'verify' => false,
]);

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


// ------------------ 2FA (mandatory) --------------------
Route::middleware(['auth'])->group(function () {
    Route::get('/2fa/setup', [TwoFactorController::class, 'showSetupForm'])->name('2fa.setup');
    Route::post('/2fa/setup', [TwoFactorController::class, 'confirmSetup'])->name('2fa.setup.confirm')->middleware('throttle:5,1');

    Route::get('/2fa/verify', [TwoFactorController::class, 'showVerifyForm'])->name('2fa.verify');
    Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])->name('2fa.verify.submit')->middleware('throttle:5,1');
});
// ------------------end 2FA --------------------

// ------------------ dashboard --------------------
// ------------------end dashboard--------------------


// ------------------ department --------------------
Route::group(['prefix' => 'department', 'middleware' => ['auth','2fa']], function () {
    Route::get('/', [DepartmentController::class, 'index'])->name('department.index');
    Route::post('/store', [DepartmentController::class, 'store'])->name('department.store');
    Route::get('/edit/{id}', [DepartmentController::class, 'edit'])->name('department.edit');
    Route::put('/update/{id}', [DepartmentController::class, 'update'])->name('department.update');
    Route::delete('/delete/{id}', [DepartmentController::class, 'destroy'])->name('department.destroy');
});

// ------------------end department --------------------

// ------------------ user (user management) --------------------
Route::group(['prefix' => 'user', 'middleware' => ['auth','2fa']], function () {
    Route::get('/', [UserController::class, 'index'])->name('user.index');
    Route::post('/{id}/reset-2fa', [UserController::class, 'resetTwoFactor'])->name('user.reset2fa');
});
// ------------------end user --------------------


// ------------------ doctor --------------------
// ------------------end doctor --------------------

// ------------------ patient --------------------
// ------------------end patient --------------------

// ------------------ pharmacy --------------------
// ------------------end pharmacy --------------------

// ------------------ billing --------------------
// ------------------end billing--------------------

// ------------------ lab --------------------
// ------------------end lab--------------------

// ------------------ room --------------------
// ------------------end room --------------------

// ------------------ setting --------------------
// ------------------end setting --------------------

// ------------------ support --------------------
Route::group(['prefix' => 'support', 'middleware' => ['auth','2fa']], function () {
    Route::get('/', [SupportController::class, 'index'])->name('support.index');
});
// ------------------end support --------------------
