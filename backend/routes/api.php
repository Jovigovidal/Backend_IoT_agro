<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AcuarioController;
use App\Http\Controllers\BitacoraController;

// =============================================================
// RUTAS ACUARIO IOT
// =============================================================

// 1. HISTORIAL (GET) - Para tu Tabla y Gráficos en Angular
// Angular: this.http.get('/api/mediciones')
Route::get('/mediciones', [AcuarioController::class, 'index']);
// 2. ESP32 (POST) - El cerebro
// ESP32: http.POST('/api/mediciones', json)
Route::post('/mediciones', [AcuarioController::class, 'store']);
// 3. DASHBOARD (GET) - Estado Actual en Vivo
// Angular: this.http.get('/api/dashboard')
Route::get('/dashboard', [AcuarioController::class, 'dashboard']);
// 4. CONTROL (POST) - Botones y Comandos
// Angular: this.http.post('/api/control', { r1: true })
Route::post('/control', [AcuarioController::class, 'updateState']);
// 5. BITÁCORA (Opcional)
Route::post('/bitacora', [BitacoraController::class, 'store']);
Route::get('/bitacora', [BitacoraController::class, 'index']);