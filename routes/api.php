<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::post('/register/send-otp', [AuthController::class, 'registerSendOtp']);
Route::post('/register/verify', [AuthController::class, 'registerVerify']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/password/forgot', [AuthController::class, 'forgotPasswordSendOtp']);
Route::post('/password/verify-otp', [AuthController::class, 'verifyForgotOtp']);
Route::post('/password/reset', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('role:siswa')->group(function () {
        // Modul Siswa (Biodata & Berkas)
    });

    Route::middleware('role:panitia,admin')->group(function () {
        // Modul Panitia & Admin (Verifikasi Pendaftar)
    });

    Route::middleware('role:admin')->group(function () {
        // Modul Admin Utama (Master Data Jurusan)
    });
});