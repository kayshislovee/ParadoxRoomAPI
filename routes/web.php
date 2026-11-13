<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Upload aset dari halaman admin Filament (icon, avatar, gambar hotspot, dll).
// Cek auth dilakukan di dalam controller supaya guest mendapat 401 JSON, bukan redirect ke route login.
Route::post('/admin/uploads', [\App\Http\Controllers\Admin\UploadController::class, 'store'])
    ->name('admin.uploads.store');
