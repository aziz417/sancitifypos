<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\User\CustomerController;
use App\Http\Controllers\User\RoleController;
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

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('register', [AuthController::class, 'store']);
});


Route::prefix('auth')->group(function () {
    // login a user
    Route::post('login', [AuthController::class, 'login']);

    // // check if email or mobile exist
    Route::get('check', [AuthController::class, 'checkIsEmailMobileExist']);
    // // get token
    Route::post('token', [AuthController::class, 'token']);

    // // reset password
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

    // // auth protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // logout auth user
        Route::post('logout', [AuthController::class, 'logout']);
        // get auth user
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    // user routes
    Route::post('update-user-avatar/{user}', [AdminController::class, 'updateAdminAvatar']);
    Route::patch('update-user-info/{user}', [AdminController::class, 'updateAdminInfo']);
    Route::patch('update-user-password/{user}', [AdminController::class, 'updateAdminPassword']);
    Route::resource('user', AdminController::class);
    // customer routes
    Route::apiResource('customer', CustomerController::class);
    // role routes
    Route::patch('add-permission/{role}', [RoleController::class, 'addPermissions']);
    Route::get('get-permission/{role}', [RoleController::class, 'getPermissionsByRole']);
    Route::get('permission', [RoleController::class, 'getPermissions']);
    Route::get('role-all', [RoleController::class, 'getRoles']);
    Route::apiResource('role', RoleController::class);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('activity-log', [ActivityController::class, 'index']);
    Route::delete('activity-log/{activity}', [ActivityController::class, 'destroy']);
    Route::delete('activity-log', [ActivityController::class, 'destroyAll']);
});
