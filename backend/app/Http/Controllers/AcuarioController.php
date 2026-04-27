<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Medicion;
use App\Models\MedicionLive;
use App\Models\SistemaEstado;
use App\Models\Bitacora;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AcuarioController extends Controller
{
    public function index()
    {
        return Medicion::orderBy('created_at', 'desc')->take(100)->get();
    }

    public function dashboard()
    {
        $ultima = MedicionLive::latest()->first();
        // Mapeo para que Angular vea temp_aire aunque la tabla diga temperatura
        if ($ultima) {
            $ultima->temp_aire = $ultima->temperatura;
            $ultima->hum_aire  = $ultima->humedad;
        }
        return response()->json([
            'ultima_medicion' => $ultima,
            'estado_actual'   => SistemaEstado::first()
        ]);
    }

    public function store(Request $request)
    {
        try {
            $evento = $request->input('evento');

            // 1. MODO HISTÓRICO / CAJA NEGRA (Viene de enviarALaravelHistorico en el ESP32)
            if ($evento) {
                $his = new Medicion();
                $his->temp_aire = $request->input('temp_aire', 0);
                $his->hum_aire  = $request->input('hum_aire', 0);
                $his->presion   = $request->input('presion', 0);
                $his->temp_agua = $request->input('temp_agua', 0);
                $his->ph        = $request->input('ph', 0);
                $his->tds       = $request->input('tds', 0);
                $his->save();

                return response()->json(['status' => 'Histórico/Caja Negra guardado', 'evento' => $evento]);
            }

            // 2. MODO EN VIVO (Viene de sincronizarLaravel en el ESP32)
            $live = new MedicionLive();
            $live->temperatura = $request->input('temp_aire', 0);
            $live->humedad     = $request->input('hum_aire', 0);
            $live->presion     = $request->input('presion', 0);
            $live->temp_agua   = $request->input('temp_agua', 0);
            $live->ph          = $request->input('ph', 0);
            $live->tds         = $request->input('tds', 0);
            $live->save();

            if (MedicionLive::count() > 100) {
                MedicionLive::orderBy('id', 'asc')->limit(10)->delete();
            }

            // 3. Lógica de Relés Dinámicos
            $estado = SistemaEstado::first();
            if (!$estado) {
                $estado = new SistemaEstado();
                $estado->modo = 'AUTO';
            } // Fix: Previene error si la tabla está vacía

            // 4. Registrar en Bitácora si es un reinicio o hubo desconexión prolongada
            if ($estado && $estado->updated_at) {
                $minutosInactivo = now()->diffInMinutes($estado->updated_at);
                $motivo = $request->input('motivo_reinicio');
                $esReinicio = $request->input('reinicio') == true || !empty($motivo);

                if ($minutosInactivo >= 5 || $esReinicio) {
                    $detalleMotivo = !empty($motivo) ? " Motivo: {$motivo}." : "";
                    Bitacora::create([
                        'evento'      => 'SISTEMA',
                        'descripcion' => "Microcontrolador reconectado/reiniciado. Tiempo inactivo: {$minutosInactivo} min.{$detalleMotivo}"
                    ]);
                }
            }

            if ($estado && $estado->modo === 'AUTO') {
                // Lógica R1
                $valR1 = $request->input($estado->r1_sensor, 0);
                $estado->r1 = ($valR1 < $estado->r1_min || $valR1 > $estado->r1_max);

                // Lógica R2
                $valR2 = $request->input($estado->r2_sensor, 0);
                $estado->r2 = ($valR2 < $estado->r2_min || $valR2 > $estado->r2_max);
            }

            $estado->box_temp = $request->input('box_temp', 0);
            $estado->box_hum  = $request->input('box_hum', 0);
            $estado->save();

            return response()->json([
                'modo' => $estado->modo,
                'r1' => (bool)$estado->r1,
                'r2' => (bool)$estado->r2,
                'r3' => (bool)$estado->r3,
                'r4' => (bool)$estado->r4,
                'r1_en' => (bool)$estado->r1_en,
                'fan_cmd' => (int)$estado->fan_cmd
            ]);
        } catch (\Exception $e) {
            Log::error("Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateState(Request $request)
    {
        // Validación de Laravel para evitar inyecciones incorrectas
        $request->validate([
            'r1_min' => 'nullable|numeric',
            'r1_max' => 'nullable|numeric',
            'r2_min' => 'nullable|numeric',
            'r2_max' => 'nullable|numeric',
        ]);

        $estado = SistemaEstado::first();
        if ($request->has('fan_state')) {
            $estado->fan_cmd = $request->fan_state ? 1 : 0;
        }

        if ($request->has('modo')) {
            $estado->modo = $request->modo;
        }

        // Guardar estado de los relés y habilitaciones
        foreach (['r1', 'r2', 'r3', 'r4'] as $r) {
            if ($request->has($r)) $estado->$r = filter_var($request->$r, FILTER_VALIDATE_BOOLEAN);
            if ($request->has("{$r}_en")) $estado->{"{$r}_en"} = filter_var($request->{"{$r}_en"}, FILTER_VALIDATE_BOOLEAN);
        }

        // Guardar configuración de Zonas de Confort (Sensores, Min y Max)
        foreach (['r1', 'r2'] as $r) {
            if ($request->has("{$r}_sensor")) $estado->{"{$r}_sensor"} = $request->{"{$r}_sensor"};
            if ($request->filled("{$r}_min")) $estado->{"{$r}_min"} = (float) $request->{"{$r}_min"};
            if ($request->filled("{$r}_max")) $estado->{"{$r}_max"} = (float) $request->{"{$r}_max"};
        }

        $estado->save();
        return response()->json(['status' => 'ok', 'config' => $estado]);
    }
}
