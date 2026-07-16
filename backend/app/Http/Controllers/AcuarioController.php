<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Medicion;
use App\Models\MedicionLive;
use App\Models\SistemaEstado;
use App\Jobs\SincronizarGoogleSheets;
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

            // 1. MODO HISTÓRICO / CAJA NEGRA
            if ($evento) {
                $timestamp = $request->input('fecha_hora');
                $yaGuardado = false;
                
                if ($timestamp) {
                    // Si el ESP32 ya fue actualizado y envía la hora real
                    $fecha_lectura = Carbon::createFromTimestamp($timestamp)->timezone('America/Lima');
                    $yaGuardado = Medicion::whereBetween('created_at', [
                        $fecha_lectura->copy()->startOfHour(),
                        $fecha_lectura->copy()->endOfHour()
                    ])->exists();
                } else {
                    // Si el ESP32 NO ha sido actualizado. Usamos la hora de llegada.
                    $fecha_lectura = Carbon::now();
                    
                    // Candado flexible: Aceptamos la lectura siempre y cuando hayan pasado 
                    // al menos 2 horas desde el último registro. Absorbe el desfase de 8:59 vs 9:00.
                    $ultimaMedicion = Medicion::latest('created_at')->first();
                    if ($ultimaMedicion) {
                        $diferenciaHoras = $ultimaMedicion->created_at->diffInHours($fecha_lectura);
                        if ($diferenciaHoras < 2) {
                            $yaGuardado = true;
                        }
                    }
                }

                if (!$yaGuardado) {
                    $his = new Medicion();
                    $his->temp_aire = $request->input('temp_aire', 0);
                    $his->hum_aire  = $request->input('hum_aire', 0);
                    $his->presion   = $request->input('presion', 0);
                    $his->temp_agua = $request->input('temp_agua', 0);
                    $his->ph        = $request->input('ph', 0);
                    $his->tds       = $request->input('tds', 0);
                    
                    $his->created_at = $fecha_lectura;
                    $his->updated_at = $fecha_lectura;
                    $his->save();

                    Log::info("✅ Dato histórico guardado. (Hora registrada: {$fecha_lectura->format('H:i')})");
                    SincronizarGoogleSheets::dispatch($his);
                } else {
                    Log::info("⚠️ Dato descartado (Duplicado o demasiado pronto).");
                }

                return response()->json(['status' => 'Histórico procesado']);
            }

            // 2. MODO EN VIVO
            $live = new MedicionLive();
            $live->temperatura = $request->input('temp_aire', 0);
            $live->humedad     = $request->input('hum_aire', 0);
            $live->presion     = $request->input('presion', 0);
            $live->temp_agua   = $request->input('temp_agua', 0);
            $live->ph          = $request->input('ph', 0);
            $live->tds         = $request->input('tds', 0);
            $live->save();

            // Mantenemos la tabla 'medicion_live' con un máximo de 100 registros para optimizarla.
            $count = MedicionLive::count();
            if ($count > 100) {
                // Eliminamos los registros más antiguos para mantener solo los últimos 100.
                MedicionLive::orderBy('id', 'asc')->limit($count - 100)->delete();
            }

            // 3. Lógica de Relés Dinámicos
            $estado = SistemaEstado::first();
            if (!$estado) {
                $estado = new SistemaEstado();
                $estado->modo = 'AUTO';
            }

            if ($estado && $estado->updated_at) {
                $minutosInactivo = now()->diffInMinutes($estado->updated_at);
                $motivo = $request->input('motivo_reinicio');
                $esReinicio = $request->input('reinicio') == true || !empty($motivo);

                if ($minutosInactivo >= 5 || $esReinicio) {
                    $detalleMotivo = !empty($motivo) ? " Motivo: {$motivo}." : "";
                    Bitacora::create([
                        'evento'      => 'SISTEMA',
                        'descripcion' => "Microcontrolador reconectado. Tiempo inactivo: {$minutosInactivo} min.{$detalleMotivo}"
                    ]);
                }
            }

            if ($estado && $estado->modo === 'AUTO') {
                $valR1 = $request->input($estado->r1_sensor, 0);
                $estado->r1 = ($valR1 < $estado->r1_min || $valR1 > $estado->r1_max);

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
            Log::error("Error en AcuarioController: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateState(Request $request)
    {
        Log::info('📥 Payload recibido desde Angular:', $request->all());

        $estado = SistemaEstado::first();
        if (!$estado) {
            $estado = new SistemaEstado();
            $estado->modo = 'AUTO';
        }

        if ($request->has('fan_state')) {
            $estado->fan_cmd = $request->fan_state ? 1 : 0;
        }

        if ($request->has('modo')) {
            $estado->modo = $request->modo;
        }

        foreach (['r1', 'r2', 'r3', 'r4'] as $r) {
            if ($request->has($r)) {
                $estado->$r = filter_var($request->input($r), FILTER_VALIDATE_BOOLEAN);
            }
            if ($request->has("{$r}_en")) {
                $estado->{"{$r}_en"} = filter_var($request->input("{$r}_en"), FILTER_VALIDATE_BOOLEAN);
            }
        }

        foreach (['r1', 'r2'] as $r) {
            if ($request->has("{$r}_sensor")) {
                $estado->{"{$r}_sensor"} = $request->input("{$r}_sensor");
            }
            if ($request->has("{$r}_min") && $request->input("{$r}_min") !== null) {
                $estado->{"{$r}_min"} = (float) $request->input("{$r}_min");
            }
            if ($request->has("{$r}_max") && $request->input("{$r}_max") !== null) {
                $estado->{"{$r}_max"} = (float) $request->input("{$r}_max");
            }
        }

        $estado->save();
        return response()->json(['status' => 'ok', 'config' => $estado]);
    }
}

//actualizacion_flexible -> reg_lecturas