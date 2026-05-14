<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\OpenApiController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    Route::resource('devices', DeviceController::class);
    Route::post('/devices/{device}/test', [DeviceController::class, 'testConnection'])->name('devices.test')->middleware('throttle:devices.test');
    Route::get('/devices/{device}/health-history', [DeviceController::class, 'healthHistory'])->name('devices.health-history');
    Route::get('/devices/export/frigate-config', [DeviceController::class, 'frigateConfig'])->name('devices.frigate-config');
    Route::get('/devices/export/frigate-config/download', [DeviceController::class, 'downloadFrigateConfig'])->name('devices.frigate-config.download');

    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::post('/alerts/{alert}/acknowledge', [AlertController::class, 'acknowledge'])->name('alerts.acknowledge');
    Route::get('/alerts/export', [AlertController::class, 'export'])->name('alerts.export');

    Route::get('/export/openapi', [OpenApiController::class, 'preview'])->name('export.openapi');
    Route::get('/export/openapi/download', [OpenApiController::class, 'download'])->name('export.openapi.download');

    Route::middleware('role:admin')->group(function () {
        Route::get('/configuration', [ConfigurationController::class, 'index'])->name('configuration.index');
        Route::post('/configuration', [ConfigurationController::class, 'update'])->name('configuration.update');
        Route::post('/configuration/test-telegram', [ConfigurationController::class, 'testTelegram'])->name('configuration.test-telegram');
        Route::post('/configuration/test-email', [ConfigurationController::class, 'testEmail'])->name('configuration.test-email');
    });
});

require __DIR__.'/auth.php';
