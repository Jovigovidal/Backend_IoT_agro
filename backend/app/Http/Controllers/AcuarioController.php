<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Medicion;
use App\Models\SistemaEstado;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AcuarioController extends Controller
{
    // ============================================================
    // 1. HISTORIAL (GET /api/mediciones)
    // ============================================================
    public function index()
    {
        return Medicion::orderBy('created_at', 'desc')->take(100)->get();
    }

    // ============================================================
    // 2. DASHBOARD EN VIVO (GET /api/dashboard)
    // ============================================================
    public function dashboard()
    {
        return response()->json([
            'ultima_medicion' => Medicion::latest()->first(),
            'estado_actual'   => SistemaEstado::first()
        ]);
    }

    // ============================================================
    // 3. CEREBRO ESP32 (POST /api/mediciones)
    // ============================================================
    public function store(Request $request)
    {
        // 1. GUARDAR HISTORIAL EXACTO (Respetando tu migración de medicions)
        try {
            Medicion::create([
                'temp_aire' => $request->input('temp_aire', 0), // Nombre correcto
                'hum_aire'  => $request->input('hum_aire', 0),  // Nombre correcto
                'presion'   => $request->input('presion', 0),
                'temp_agua' => $request->input('temp_agua', 0),
                'ph'        => $request->input('ph', 0),
                'tds'       => $request->input('tds', 0),
                'box_temp'  => $request->input('box_temp', 0), // Guardar temp caja en historial
                'llenando'  => $request->input('llenando', false),
                'volumen_actual_ml' => $request->input('volumen_actual_ml', 0)
            ]);
        } catch (\Exception $e) {
            Log::error("Error guardando medición: " . $e->getMessage());
        }

        // 2. ACTUALIZAR ESTADO ACTUAL (Botones y Caja)
        $estado = SistemaEstado::first(); 
        if (!$estado) {
            $estado = SistemaEstado::create(['modo' => 'AUTO']);
        }

       // 🟢 NUEVO: Solo apagamos el botón si el ESP32 avisa explícitamente que ya terminó la meta
        if ($request->input('termino_llenado') === true) {
            $estado->iniciar_llenado = false;
        }

        // Guardar estado de la caja
        $estado->box_temp = $request->input('box_temp', 0);
        $estado->box_hum  = $request->input('box_hum', 0);
        $estado->save();

        // 3. RESPONDER AL ESP32
        return response()->json([
            'modo'             => $estado->modo,
            'iniciar_llenado'  => (bool)$estado->iniciar_llenado,
            'meta_litros'      => (float)$estado->meta_litros,
            'prog_hora'        => (int)$estado->prog_hora,
            'prog_min'         => (int)$estado->prog_min,
            'prog_meta_litros' => (float)$estado->prog_meta_litros,
            'r1' => (bool)$estado->r1,
            'r2' => (bool)$estado->r2,
            'r3' => (bool)$estado->r3,
            'r4' => (bool)$estado->r4,
            'r1_en' => (bool)$estado->r1_en,
            'r2_en' => (bool)$estado->r2_en,
            'r3_en' => (bool)$estado->r3_en,
            'r4_en' => (bool)$estado->r4_en,
            'fan_cmd' => (int)$estado->fan_cmd
        ]);
    }

    // ============================================================
    // 4. CONTROL MANUAL (POST /api/control)
    // 👇 ESTO FALTABA PARA QUE LOS BOTONES DE ANGULAR FUNCIONEN 👇
    // ============================================================
    public function updateState(Request $request)
    {
        $estado = SistemaEstado::first();
        
        // Si el frontend envía un comando para el ventilador
        if ($request->has('fan_state')) {
            $estado->fan_cmd = $request->fan_state ? 1 : 0; 
        } else {
            // Actualizar relés u otros parámetros
            $estado->update($request->all());
        }
        $estado->save();

        return response()->json(['message' => 'Comando actualizado', 'estado' => $estado]);
    }
}