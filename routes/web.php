<?php

use App\Http\Controllers\Department\DepartmentController;
use App\Http\Controllers\Support\SupportController;
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

Route::get('/', function () {
    return view('auth.login');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// ------------------ dashboard --------------------
// ------------------end dashboard--------------------


// ------------------ department --------------------
Route::group(['prefix' => 'department', 'middleware' => ['auth']], function () {
    Route::get('/', [DepartmentController::class, 'index'])->name('department.index');
    Route::post('/store', [DepartmentController::class, 'store'])->name('department.store');
    Route::get('/edit/{id}', [DepartmentController::class, 'edit'])->name('department.edit');
    Route::put('/update/{id}', [DepartmentController::class, 'update'])->name('department.update');
    Route::delete('/delete/{id}', [DepartmentController::class, 'destroy'])->name('department.destroy');
});

// ------------------end department --------------------

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
Route::group(['prefix' => 'support', 'middleware' => ['auth']], function () {
    Route::get('/', [SupportController::class, 'index'])->name('support.index');
});
// ------------------end support --------------------
