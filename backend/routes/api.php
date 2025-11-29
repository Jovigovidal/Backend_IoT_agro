<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MedicionController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::get('/mediciones', [MedicionController::class, 'index']);
Route::post('/mediciones', [MedicionController::class, 'store']);
Route::post('/configurar', function (Request $request) {
    $config = App\Models\Configuracion::first();
    $config->modo = $request->modo;
    $config->relay1_status = $request->relay1_status;
    $config->relay2_status = $request->relay2_status;

if($request->has('relay1_enabled')) $config->relay1_enabled = $request->relay1_enabled;
if($request->has('relay2_enabled')) $config->relay2_enabled = $request->relay2_enabled;

    $config->save();
    return response()->json(['msg' => 'Config actualizada']);
});

// Ruta para que Angular lea el estado actual al abrir la app
Route::get('/estado-actual', function () {
    return App\Models\Configuracion::first();
});

