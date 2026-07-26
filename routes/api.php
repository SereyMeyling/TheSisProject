<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ------------------ User & Role/Permission API Routes --------------------
Route::middleware(['auth:sanctum', 'role_or_permission:admin|manage users'])->group(function () {
    // User endpoints
    Route::get('/users', [\App\Http\Controllers\User\UserController::class, 'index']);
    Route::post('/users', [\App\Http\Controllers\User\UserController::class, 'store']);
    Route::put('/users/{user}/role', [\App\Http\Controllers\User\UserController::class, 'updateRole']);
    Route::delete('/users/{user}', [\App\Http\Controllers\User\UserController::class, 'destroy']);

    // Role & Permission endpoints
    Route::get('/roles', [\App\Http\Controllers\RolePermissionController::class, 'index']);
    Route::post('/roles', [\App\Http\Controllers\RolePermissionController::class, 'storeRole']);
    Route::post('/permissions', [\App\Http\Controllers\RolePermissionController::class, 'storePermission']);
    Route::post('/roles/{role}/permissions', [\App\Http\Controllers\RolePermissionController::class, 'assignPermissionsToRole']);
});
