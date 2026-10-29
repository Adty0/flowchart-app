<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FlowchartController;
use App\Http\Controllers\GuruController;

// Rute Halaman Utama (Arahkan sesuai sesi login)
Route::get('/', function () {
    if (Auth::check()) {
        return Auth::user()->isGuru()
            ? redirect()->route('guru.dashboard')
            : redirect()->route('flowchart.index');
    }
    return redirect()->route('login');
});

// Autentikasi Pengguna (Tamu / Guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/login/demo', [AuthController::class, 'loginDemo'])->name('login.demo');
    Route::get('/login/simulasi', [AuthController::class, 'loginSimulasi'])->name('login.simulasi');

    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Logout (Pengguna Terotentikasi)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Ruang Praktikum Siswa (Memerlukan Login)
Route::middleware('auth')->group(function () {
    Route::get('/flowchart', [FlowchartController::class, 'index'])->name('flowchart.index');
    Route::post('/flowchart/check', [FlowchartController::class, 'check'])->name('flowchart.check');
    Route::post('/flowchart/store', [FlowchartController::class, 'store'])->name('flowchart.store');
});

// Portal Guru - Dashboard Rekapitulasi & Manajemen Studi Kasus (Khusus Role: Guru)
Route::middleware(['auth', 'role:guru'])->prefix('guru')->name('guru.')->group(function () {
    Route::get('/dashboard', [GuruController::class, 'dashboard'])->name('dashboard');
    Route::get('/export-excel', [GuruController::class, 'exportExcel'])->name('export_excel');
    Route::post('/submission/{id}/toggle-remedial', [GuruController::class, 'toggleRemedial'])->name('submission.toggle_remedial');
    Route::delete('/submission/{id}', [GuruController::class, 'destroy'])->name('submission.destroy');
    Route::post('/case-study', [GuruController::class, 'storeCaseStudy'])->name('case_study.store');
    Route::delete('/case-study/{id}', [GuruController::class, 'destroyCaseStudy'])->name('case_study.destroy');
});