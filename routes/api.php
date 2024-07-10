<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WishListController;

use App\Http\Controllers\AdminBookingController;
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
Route::post("dashboard/login",[AdminController::class,"login"]);


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
    Route::post('/payment/cancel', [BookingController::class,'cancelBooking'])->name('booking.cancel');
    Route::post('/payment/complete', [BookingController::class,'completePayment'])->name('payment.complete');
    Route::post('/viewInvoice', [BookingController::class, 'viewInvoice']);
    Route::post('/booking/update', [BookingController::class, 'updateBooking']);
    Route::get('/booking/{id}', [BookingController::class, 'showBookingDetails']);
    Route::get('/get/bookings', [BookingController::class, 'getUserBookings']);
    //service
    Route::post('/request/services', [ServiceController::class, 'requestService']);
    Route::get('/bookings/{booking_id}/services', [ServiceController::class, 'showBookingServices']);
    Route::post('/services/cancel', [ServiceController::class, 'cancelServiceRequest']);


});
//Services api
Route::get('/index/services',[ServiceController::class, 'showServices']);

//Review
Route::get('show/{room}/reviews', [ReviewController::class,"showRoomReviews"]);

//reports api
Route::post('/reports', [ReportController::class, 'create_report']);
Route::get('/reports', [ReportController::class, 'my_reports']);



//Room api
Route::get('getAllRooms', [RoomController::class,"getAllRooms"])->name('get_all_rooms');
Route::post('searchRooms', [RoomController::class, 'searchRooms']);
Route::post('filterRooms', [RoomController::class, 'filterRooms']);
Route::get('getRoomDetails/{room_id}', [RoomController::class,"getRoomDetails"]);

//Route::get('/invoices/download/{id}', [AdminController::class, 'downloadInvoice'])->name('invoices.download');



//dashboard api
Route::group(['prefix' => 'dashboard', 'middleware' => ['auth:api', 'Admin']], function () {
    //admin profile
    Route::post( "/profile", [ AdminController::class, "updateProfile" ] );
    Route::get( "/myProfile", [ AdminController::class, "myProfile" ] );

    // manage users
    Route::post("/create/user",[AdminController::class,"createUser"]);
    Route::get( "/getProfile/{id}", [ AdminController::class, "getProfile" ] );
    Route::put('/users/{id}/ban', [AdminController::class, 'BanUser']);
    Route::put('/users/{id}/unban', [AdminController::class, 'unBanUser']);
    Route::get('/users/{userId}', [AdminController::class, 'showUser']);
    Route::get('/users', [AdminController::class, 'indexUsers']);
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
    Route::get('/search', [AdminController::class, 'searchUsers']);

    //reports
    Route::get('/reports', [AdminController::class, 'showReports']);
    Route::get('/users/{userId}/reports', [AdminController::class, 'getUserReports']);
    Route::post('/reports/check', [AdminController::class, 'checkReports']);


    //invoice
    Route::get('/invoices/download/{id}', [AdminController::class, 'downloadInvoice'])->name('invoices.download');


    //Room
    Route::delete('/rooms/{id}', [AdminController::class, 'deleteRoom']);
    Route::post('/create/rooms', [AdminController::class, 'createRoom']);
    Route::post( "/update/room/{id}", [ AdminController::class, "updateRoom" ] );

    //Booking
    Route::get('/bookings', [AdminBookingController::class, 'index']);
    Route::post('/search/bookings', [AdminBookingController::class, 'searchBookings']);
    Route::get('/destroy/bookings/{id}', [AdminBookingController::class, 'destroy']);
    Route::get('/show/booking/Details/{id}', [AdminBookingController::class, 'showDetails']);
    Route::put('/bookings/{id}/payment-status', [AdminBookingController::class, 'updatePaymentStatus']);
    Route::post('/create/bookings', [AdminBookingController::class, 'createBooking']);
    Route::post('/create/forCustomer', [AdminBookingController::class, 'createBookingForCustomer']);
});

