<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TransaksiController;

// ─── Transaksi CRUD ──────────────────────────────────────────
Route::get('/transaksi',            [TransaksiController::class, 'index']);
Route::get('/transaksi/bulan-ini',  [TransaksiController::class, 'bulanIni']);
Route::get('/transaksi/statistik',  [TransaksiController::class, 'statistik']);
Route::get('/transaksi/search',     [TransaksiController::class, 'search']);
Route::post('/transaksi',           [TransaksiController::class, 'store']);
Route::get('/transaksi/{id}',       [TransaksiController::class, 'show']);
Route::put('/transaksi/{id}',       [TransaksiController::class, 'update']);
Route::delete('/transaksi/{id}',    [TransaksiController::class, 'destroy']);
