<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
<<<<<<< HEAD

Route::get('/login', function () {
    // منطق تسجيل الدخول هنا
})->name('login');

Route::get('/register', function () {
    // منطق التسجيل هنا
})->name('register');
=======
>>>>>>> 20cdfdd24b2a61e5d8e6d2f3de6a947af2dd92dc
