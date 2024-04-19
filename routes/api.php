<?php

use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//user api
Route::middleware('api')->group(function () {
    Route::post("/register/email",[UserController::class,"sendVerificationCode"]);
    Route::post("/register/code",[UserController::class,"verifyCode"]);
    Route::post("/register/user",[UserController::class,"registerUser"]);
});

Route::post("login",[UserController::class,"login"]);

//Password reset api
Route::post('password/email', [PasswordResetController::class, 'sendResetCode']);
Route::post('password/reset', [PasswordResetController::class, 'resetPasswordWithCode']);


Route::group(["middleware"=>["auth:api"]],function () {
    //User api
    Route::get( "myProfile", [ UserController::class, "myProfile" ] );
    Route::get( "getProfile/{id}", [ UserController::class, "getProfile" ] );
    Route::post( "user/profile", [ UserController::class, "updateProfile" ] );
    Route::get( "logout", [ UserController::class, "logout" ] );
    Route::post( "checkPassword", [ UserController::class, "checkPassword" ] );
});
