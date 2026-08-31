<?php

use App\Http\Controllers\CetakDisposisiController;
use App\Http\Controllers\CetakFormulirEvaluasiController;
use App\Http\Controllers\CetakRingkasanController;
use App\Http\Controllers\CetakSuratKeputusanController;
use App\Http\Controllers\PrivateDocumentController;
use App\Http\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Route;

Route::get('/health/ready', HealthCheckController::class)->middleware('throttle:health')->name('health.ready');

// Beri nama 'login' agar Laravel tidak error saat melakukan redirect
Route::get('/login', function () {
    return redirect()->route('filament.peserta.auth.login');
})->name('login');

// Halaman utama (/) menampilkan landing page informasi magang
Route::get('/', function () {
    return view('landing');
});

Route::get('/pengajuan/{pengajuan}/cetak-ringkasan', [CetakRingkasanController::class, 'show'])
    ->middleware(['auth', 'throttle:generated-reports'])
    ->name('pengajuan.cetak-ringkasan');

Route::get('/disposisi/{disposisi}/cetak', [CetakDisposisiController::class, 'show'])
    ->middleware(['auth', 'throttle:generated-reports'])
    ->name('disposisi.cetak');

Route::get('/pengajuan/{pengajuan}/cetak-formulir-evaluasi', [CetakFormulirEvaluasiController::class, 'show'])
    ->middleware(['auth', 'throttle:generated-reports'])
    ->name('pengajuan.cetak-formulir-evaluasi');

Route::get('/pengajuan/{pengajuan}/surat-keterangan-selesai', [CetakSuratKeputusanController::class, 'suratKeteranganSelesai'])
    ->middleware(['auth', 'throttle:generated-reports'])
    ->name('pengajuan.surat-keterangan-selesai');

Route::get('/perpanjangan/{perpanjangan}/surat-perpanjangan', [CetakSuratKeputusanController::class, 'suratPerpanjangan'])
    ->middleware(['auth', 'throttle:generated-reports'])
    ->name('perpanjangan.surat-perpanjangan');

Route::middleware(['auth', 'throttle:private-documents'])->prefix('documents')->name('documents.')->group(function () {
    Route::get('/pengajuan/{pengajuan}/{field}', [PrivateDocumentController::class, 'pengajuan'])->name('pengajuan');
    Route::get('/persyaratan/{dokumen}', [PrivateDocumentController::class, 'persyaratan'])->name('persyaratan');
    Route::get('/disposisi/{disposisi}', [PrivateDocumentController::class, 'disposisi'])->name('disposisi');
    Route::get('/surat-balasan/{surat}', [PrivateDocumentController::class, 'suratBalasan'])->name('surat-balasan');
    Route::get('/surat-keterangan/{surat}', [PrivateDocumentController::class, 'suratKeterangan'])->name('surat-keterangan');
    Route::get('/penilaian/{penilaian}', [PrivateDocumentController::class, 'penilaian'])->name('penilaian');
    Route::get('/evaluasi/{evaluasi}', [PrivateDocumentController::class, 'evaluasi'])->name('evaluasi');
    Route::get('/perpanjangan/{perpanjangan}', [PrivateDocumentController::class, 'perpanjangan'])->name('perpanjangan');
});
