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

});

Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/login', [Auth\LoginController::class, 'showLoginForm'])->name('login');
Route::get('/register', [Auth\RegisterController::class, 'showRegistrationForm'])->name('register');