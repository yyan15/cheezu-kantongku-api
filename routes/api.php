<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TransaksiController;
use App\Http\Controllers\Api\AuthController;

// ─── Auth SSO ───────────────────────────────────────────────
Route::post('/auth/google/verify', [AuthController::class, 'verifyGoogle']);

// ─── Protected Routes ───────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    // Transaksi CRUD
    Route::get('/transaksi',            [TransaksiController::class, 'index']);
    Route::get('/transaksi/bulan-ini',  [TransaksiController::class, 'bulanIni']);
    Route::get('/transaksi/statistik',  [TransaksiController::class, 'statistik']);
    Route::get('/transaksi/search',     [TransaksiController::class, 'search']);
    Route::post('/transaksi',           [TransaksiController::class, 'store']);
    Route::get('/transaksi/{id}',       [TransaksiController::class, 'show']);
    Route::put('/transaksi/{id}',       [TransaksiController::class, 'update']);
    Route::delete('/transaksi/{id}',    [TransaksiController::class, 'destroy']);

    // Auth Logout
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});
