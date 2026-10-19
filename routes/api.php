<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BiodataController;
use App\Http\Controllers\BerkasController;
use App\Http\Controllers\PendaftaranController;
use App\Http\Controllers\JurusanController;

Route::post('/register/send-otp', [AuthController::class, 'registerSendOtp']);
Route::post('/register/verify-otp', [AuthController::class, 'registerVerify']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/forgot-password/send-otp', [AuthController::class, 'forgotPasswordSendOtp']);
Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyForgotOtp']);
Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword']);

Route::get('/jurusan', [JurusanController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('ability:siswa')->group(function () {
        Route::get('/siswa/biodata', [BiodataController::class, 'show']);
        Route::put('/siswa/biodata', [BiodataController::class, 'update']);
        Route::post('/siswa/biodata', [BiodataController::class, 'update']);

        Route::get('/siswa/berkas', [BerkasController::class, 'show']);
        Route::post('/siswa/berkas', [BerkasController::class, 'upload']);
        Route::post('/berkas/upload', [BerkasController::class, 'upload']);

        Route::get('/siswa/pendaftaran', [PendaftaranController::class, 'statusSiswa']);
        Route::post('/siswa/pendaftaran', [PendaftaranController::class, 'pilihJurusan']);
        Route::post('/pendaftaran/pilih-jurusan', [PendaftaranController::class, 'pilihJurusan']);
        Route::post('/pembayaran/konfirmasi', [BerkasController::class, 'konfirmasiPembayaran']);
    });

    Route::middleware('ability:panitia,admin')->group(function () {
        Route::get('/admin/dashboard', [PendaftaranController::class, 'dashboardStats']);
        Route::get('/dashboard/stats', [PendaftaranController::class, 'dashboardStats']);

        Route::get('/admin/jurusan', [JurusanController::class, 'index']);
        Route::post('/admin/jurusan', [JurusanController::class, 'store']);
        Route::put('/admin/jurusan/{id}', [JurusanController::class, 'update']);
        Route::delete('/admin/jurusan/{id}', [JurusanController::class, 'destroy']);

        Route::get('/admin/pendaftaran', [PendaftaranController::class, 'index']);
        Route::get('/pendaftaran/recent', [PendaftaranController::class, 'index']);
        Route::get('/admin/pendaftaran/{id}', [PendaftaranController::class, 'show']);
        Route::put('/admin/verifikasi-berkas/{id}', [BerkasController::class, 'verifikasi']);
        Route::put('/admin/pendaftaran/{id}/kelulusan', [PendaftaranController::class, 'updateKelulusan']);
    });
    
    Route::middleware(['auth:sanctum', 'ability:panitia,admin'])->group(function () {
        Route::get('/admin/pendaftaran/export', [PendaftaranController::class, 'exportExcel']);
    });
});
