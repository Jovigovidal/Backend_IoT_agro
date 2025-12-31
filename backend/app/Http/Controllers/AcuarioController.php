<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Medicion;
use App\Models\SistemaEstado;
use Illuminate\Support\Facades\Log;

class AcuarioController extends Controller
{
    // ============================================================
    // 1. HISTORIAL (GET /api/mediciones)
    // Para tu tabla y gráficos en Angular
    // ============================================================
    public function index()
    {
        // Devolvemos las últimas 50 o 100 mediciones ordenadas
        // Puedes usar paginación: return Medicion::orderBy('id', 'desc')->paginate(20);
        return Medicion::orderBy('created_at', 'desc')->take(100)->get();
    }

    // ============================================================
    // 2. DASHBOARD EN VIVO (GET /api/dashboard)
    // Para las tarjetas de "Estado Actual" en Angular
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
    // Recibe datos del sensor y responde con comandos
    // ============================================================
    public function store(Request $request)
    {
        try {
            Medicion::create($request->all());
        } catch (\Exception $e) {
            // Ahora sí funcionará esto sin marcar error
            Log::error("Error guardando medición: " . $e->getMessage());
        }

        // B. Leer configuración
        $estado = SistemaEstado::first(); 
        if (!$estado) $estado = SistemaEstado::create(['modo' => 'AUTO']);

        // C. Responder al ESP32
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
    // ============================================================
    public function updateState(Request $request)
    {
        $estado = SistemaEstado::first();
        
        if ($request->has('fan_state')) {
            $estado->fan_cmd = $request->fan_state ? 1 : 0; 
        } else {
            $estado->update($request->all());
        }
        $estado->save();

        return response()->json(['message' => 'Comando actualizado', 'estado' => $estado]);
    }
}