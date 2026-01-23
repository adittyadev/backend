<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ScanController;
use App\Http\Controllers\KasController;
use App\Http\Controllers\KirimUangController;
use App\Http\Controllers\MintaUangController;
use App\Http\Controllers\LaporanController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// routes that need auth token
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    Route::post('/scan', [ScanController::class, 'store']);
    Route::get('/scans', [ScanController::class, 'index']);
});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

route::get('data-pengguna', [AuthController::class, 'dataPengguna'])->middleware('auth:sanctum');
route::post('update-user', [AuthController::class, 'updateUser'])->middleware('auth:sanctum');
route::post('update-photo', [AuthController::class, 'updateUserPhoto'])->middleware('auth:sanctum');
route::middleware('auth:sanctum')->get('get-saldo', [AuthController::class, 'getSaldoUser']);

Route::middleware('auth:sanctum')->get('kas-masuk/data', [KasController::class, 'getDataKasMasuk']);
Route::middleware('auth:sanctum')->post('kas-masuk/save', [KasController::class, 'insertDataKasMasuk']);
Route::middleware('auth:sanctum')->get('kas-masuk/detail/{notrans}', [KasController::class, 'getDetailKasMasuk']);
Route::middleware('auth:sanctum')->delete('kas-masuk/delete/{notrans}', [KasController::class, 'deleteDataKasMasuk']);
Route::middleware('auth:sanctum')->put('kas-masuk/update/{notrans}', [KasController::class, 'updateDataKasMasuk']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/kas-keluar', [KasController::class, 'getDataKasKeluar']);
    Route::post('/kas-keluar', [KasController::class, 'insertDataKasKeluar']);
    Route::put('/kas-keluar/update/{notrans}', [KasController::class, 'updateDataKasKeluar']);
    Route::delete('/kas-keluar/delete/{notrans}', [KasController::class, 'deleteDataKasKeluar']);
    Route::get('/kas-keluar/detail/{notrans}', [KasController::class, 'getDetailKasKeluar']);
});

Route::middleware('auth:sanctum')->post(
    '/kirim-uang/save',
    [KirimUangController::class, 'insertDataKirimUang']
);

Route::middleware('auth:sanctum')->post('/minta-uang/save', [MintaUangController::class, 'insertDataMintaUang']);
Route::get('/minta-uang/detail/{noref}', [MintaUangController::class, 'getDataDetail'])->middleware('auth:sanctum');
Route::put('/minta-uang/proses-permintaan/{noref}', [MintaUangController::class, 'prosesPermintaan'])->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/laporan/uang-masuk', [LaporanController::class, 'uangMasuk']);
    Route::get('/laporan/uang-keluar', [LaporanController::class, 'uangKeluar']);
    Route::get('/laporan/kirim-uang', [KirimUangController::class, 'laporanKirimUang']);
    Route::get('/laporan/minta-uang', [MintaUangController::class, 'laporanMintaUang']);
});
