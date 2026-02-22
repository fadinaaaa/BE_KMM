<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\SkyliftController;
use App\Http\Controllers\KeluarMasukBarangController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PergantianAlatController;
use App\Http\Controllers\PeminjamanBarangController;



//Auth
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/validate-token', [AuthController::class, 'validateToken']);
});

// =============================
// ITEM ROUTE
// =============================
Route::get('items-export', [ItemController::class, 'export']);
Route::post('items-import', [ItemController::class, 'import']);
Route::apiResource('items', ItemController::class);
Route::put('/items/{id}', [ItemController::class, 'update']);
Route::delete('/items', [ItemController::class, 'destroy']);
Route::get('/items/{id}', [ItemController::class, 'show']);





// =============================
// SKYLIFT ROUTE
// =============================
Route::get('skylifts-export', [SkyliftController::class, 'export']);
Route::apiResource('skylifts', SkyliftController::class);
Route::post('skylifts-import', [SkyliftController::class, 'import']);



Route::get('/items/dropdown', [KeluarMasukBarangController::class, 'getItemsForDropdown']);
Route::get('/keluar-masuk', [KeluarMasukBarangController::class, 'index']);
Route::post('/keluar-masuk', [KeluarMasukBarangController::class, 'store']);
Route::get('/keluar-masuk-barang/export', [KeluarMasukBarangController::class, 'export']);
Route::post('/keluar-masuk-barang/import', [KeluarMasukBarangController::class, 'import']);
Route::get('/keluar-masuk-barang/template', [KeluarMasukBarangController::class, 'downloadTemplate']);
Route::put('/keluar-masuk/{id}', [KeluarMasukBarangController::class, 'update']);
Route::get('/keluar-masuk/{id}', [KeluarMasukBarangController::class, 'show']);
Route::delete('/keluar-masuk/{id}', [KeluarMasukBarangController::class, 'destroy']);

// =============================
// Pergantian ROUTE
// =============================

Route::get('/pergantian-alat-export', [PergantianAlatController::class, 'export']);
Route::get('/pergantian-alat', [PergantianAlatController::class, 'index']);
Route::post('/pergantian-alat', [PergantianAlatController::class, 'store']);
Route::get('/pergantian-alat/{id}', [PergantianAlatController::class, 'show']);
// ✅ update: support 2 cara
Route::put('/pergantian-alat/{id}', [PergantianAlatController::class, 'update']); 
Route::post('/pergantian-alat/{id}', [PergantianAlatController::class, 'update']); // untuk multipart + _method

Route::delete('/pergantian-alat/{id}', [PergantianAlatController::class, 'destroy']);

// =============================
// Peminjaman ROUTE
// =============================

Route::get('/peminjaman-barang-export', [PeminjamanBarangController::class, 'export']);
Route::get('/peminjaman-barang', [PeminjamanBarangController::class, 'index']);
Route::post('/peminjaman-barang', [PeminjamanBarangController::class, 'store']);
Route::get('/peminjaman-barang/{id}', [PeminjamanBarangController::class, 'show']);
Route::put('/peminjaman-barang/{id}', [PeminjamanBarangController::class, 'update']);
Route::delete('/peminjaman-barang/{id}', [PeminjamanBarangController::class, 'destroy']);

// ✅ kalau frontend kamu pakai POST + _method=PUT (multipart), Laravel tetap bisa.
// Tapi route PUT di atas tetap diperlukan agar method spoofing diarahkan benar.
