<?php

use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\MasterKalenderController;
use App\Http\Middleware\AdminOnly;
use Illuminate\Support\Facades\Route;

Route::get('/', [AdminAuthController::class, 'create'])->name('login');
Route::get('/login', [AdminAuthController::class, 'create']);
Route::post('/login', [AdminAuthController::class, 'store'])->name('admin.login.store')->middleware('guest');
Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('admin.logout')->middleware('auth');

Route::get('/dashboard', [MasterKalenderController::class, 'dashboard'])->name('dashboard.index');
Route::get('/katalog', [MasterKalenderController::class, 'index'])->name('katalog.index');
Route::get('/katalog/{id}', [MasterKalenderController::class, 'detail'])->name('katalog.detail');
Route::get('/kalender', [MasterKalenderController::class, 'kalender'])->name('kalender.index');

Route::middleware(['auth', AdminOnly::class])->group(function () {
    Route::post('/master-kalender/upload', [MasterKalenderController::class, 'upload'])->name('master-kalender.upload');
    Route::post('/master-waktu', [MasterKalenderController::class, 'storeWaktu'])->name('master-waktu.store');
    Route::put('/katalog/{id}', [MasterKalenderController::class, 'update'])->name('katalog.update');
    Route::delete('/katalog/{id}', [MasterKalenderController::class, 'destroy'])->name('katalog.destroy');
    Route::post('/katalog/{id}/waktu', [MasterKalenderController::class, 'storeDetailWaktu'])->name('katalog.waktu.store');
    Route::put('/katalog/{id}/waktu/{idWaktu}', [MasterKalenderController::class, 'updateDetailWaktu'])->name('katalog.waktu.update');
    Route::post('/katalog/{id}/aktivitas', [MasterKalenderController::class, 'storeDetailAktivitas'])->name('katalog.aktivitas.store');
    Route::put('/katalog/{id}/aktivitas/{idAktivitas}', [MasterKalenderController::class, 'updateDetailAktivitas'])->name('katalog.aktivitas.update');
    Route::delete('/katalog/{id}/aktivitas/{idAktivitas}', [MasterKalenderController::class, 'destroyDetailAktivitas'])->name('katalog.aktivitas.destroy');
});
