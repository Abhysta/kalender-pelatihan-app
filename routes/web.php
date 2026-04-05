<?php

use App\Http\Controllers\MasterKalenderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MasterKalenderController::class, 'index'])->name('katalog.index');
Route::get('/dashboard', [MasterKalenderController::class, 'dashboard'])->name('dashboard.index');
Route::post('/master-kalender/upload', [MasterKalenderController::class, 'upload'])->name('master-kalender.upload');
Route::post('/master-waktu', [MasterKalenderController::class, 'storeWaktu'])->name('master-waktu.store');
Route::get('/katalog/{id}', [MasterKalenderController::class, 'detail'])->name('katalog.detail');
Route::post('/katalog/{id}/waktu', [MasterKalenderController::class, 'storeDetailWaktu'])->name('katalog.waktu.store');
Route::put('/katalog/{id}/waktu/{idWaktu}', [MasterKalenderController::class, 'updateDetailWaktu'])->name('katalog.waktu.update');
Route::post('/katalog/{id}/aktivitas', [MasterKalenderController::class, 'storeDetailAktivitas'])->name('katalog.aktivitas.store');
Route::put('/katalog/{id}/aktivitas/{idAktivitas}', [MasterKalenderController::class, 'updateDetailAktivitas'])->name('katalog.aktivitas.update');
Route::delete('/katalog/{id}/aktivitas/{idAktivitas}', [MasterKalenderController::class, 'destroyDetailAktivitas'])->name('katalog.aktivitas.destroy');

Route::get('/kalender', [MasterKalenderController::class, 'kalender'])->name('kalender.index');
