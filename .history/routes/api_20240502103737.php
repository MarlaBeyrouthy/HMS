<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\WishListController;
use App\Http\Controllers\PasswordResetController;

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

    //wishlist api
    Route::get('wishlists/add', [WishlistController::class,'addToWishlist']);
    Route::delete('wishlist/{roomId}', [WishlistController::class,'removeFromWishlist']);
    Route::get('wishlist', [WishlistController::class,'getWishlist']);
    Route::get('wishlist/ID', [WishlistController::class,'getIDs']);

    //Booking api
    Route::post('/booking', [BookingController::class,'makeBooking'])->name('api.booking.make');
    Route::post('/payment/success', [BookingController::class,'handlePaymentSuccess'])->name('booking.success');
    Route::post('/payment/cancel', [BookingController::class,'cancelBooking'])->name('booking.cancel');
    Route::post('/payment/complete', [BookingController::class,'completePayment'])->name('payment.complete');
});

//reports api
Route::post('/reports', [ReportController::class, 'create_report']);
Route::get('/reports', [ReportController::class, 'my_reports']);

//Room api
Route::get('getAllRooms', [RoomController::class,"getAllRooms"])->name('get_all_rooms');
Route::post('searchRooms', [RoomController::class, 'searchRooms']);
Route::post('rooms/filter', [RoomController::class, 'filterRooms']);
Route::get('getRoomDetails/{room_id}', [RoomController::class,"getRoomDetails"]);
