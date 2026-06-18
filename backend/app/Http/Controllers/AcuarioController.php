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
                // 🔥 EL PARCHE: Leemos la hora enviada por el ESP32. Si no la envía, usamos la actual.
                $timestamp = $request->input('fecha_hora');
                
                if ($timestamp) {
                    $ahora = Carbon::createFromTimestamp($timestamp)->timezone('America/Lima');
                } else {
                    $ahora = Carbon::now();
                }

                // Definimos las horas permitidas (cada 3 horas)
                $horasPermitidas = [0, 3, 6, 9, 12, 15, 18, 21];

                // Si la hora (original o actual) coincide con una hora permitida...
                if (in_array($ahora->hour, $horasPermitidas)) {

                    // Verificamos si YA guardamos un dato en esa hora específica para no duplicar
                    $yaGuardado = Medicion::whereBetween('created_at', [
                        $ahora->copy()->startOfHour(),
                        $ahora->copy()->endOfHour()
                    ])->exists();

                    // Si no se ha guardado nada, lo registramos
                    if (!$yaGuardado) {
                        $his = new Medicion();
                        $his->temp_aire = $request->input('temp_aire', 0);
                        $his->hum_aire  = $request->input('hum_aire', 0);
                        $his->presion   = $request->input('presion', 0);
                        $his->temp_agua = $request->input('temp_agua', 0);
                        $his->ph        = $request->input('ph', 0);
                        $his->tds       = $request->input('tds', 0);
                        
                        // 🔥 Forzamos la fecha histórica real en la base de datos
                        $his->created_at = $ahora;
                        $his->updated_at = $ahora;
                        $his->save();

                        Log::info("✅ Dato histórico guardado en MySQL (Hora original: {$ahora->format('Y-m-d H:i')})");

                        // Enviamos a la cola para Google Sheets
                        SincronizarGoogleSheets::dispatch($his);
                    }
                }

                return response()->json(['status' => 'Histórico procesado', 'evento' => $evento]);
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

            // Limpieza para mantener solo 100 registros en la tabla "Live"
            if (MedicionLive::count() > 100) {
                MedicionLive::orderBy('id', 'asc')->limit(10)->delete();
            }

            // 3. Lógica de Relés Dinámicos
            $estado = SistemaEstado::first();
            if (!$estado) {
                $estado = new SistemaEstado();
                $estado->modo = 'AUTO';
            }

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
            Log::error("Error en AcuarioController: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

   public function updateState(Request $request)
    {
        // 1. REGISTRO DE CAJA NEGRA (Para ver qué envía Angular)
        Log::info('📥 Payload recibido desde Angular:', $request->all());

        // 2. BUSCAR O CREAR ESTADO (Protección contra base de datos vacía)
        $estado = SistemaEstado::first();
        if (!$estado) {
            $estado = new SistemaEstado();
            $estado->modo = 'AUTO';
        }

        // 3. GUARDAR COMANDOS GENERALES
        if ($request->has('fan_state')) {
            $estado->fan_cmd = $request->fan_state ? 1 : 0;
        }

        if ($request->has('modo')) {
            $estado->modo = $request->modo;
        }

        // 4. GUARDAR ESTADO DE RELÉS
        foreach (['r1', 'r2', 'r3', 'r4'] as $r) {
            if ($request->has($r)) {
                $estado->$r = filter_var($request->input($r), FILTER_VALIDATE_BOOLEAN);
            }
            if ($request->has("{$r}_en")) {
                $estado->{"{$r}_en"} = filter_var($request->input("{$r}_en"), FILTER_VALIDATE_BOOLEAN);
            }
        }

        // 5. GUARDAR ZONAS DE CONFORT (Sin bloqueos de validación)
        foreach (['r1', 'r2'] as $r) {
            if ($request->has("{$r}_sensor")) {
                $estado->{"{$r}_sensor"} = $request->input("{$r}_sensor");
            }
            
            // Verificamos si existe y no es nulo, luego forzamos a Float
            if ($request->has("{$r}_min") && $request->input("{$r}_min") !== null) {
                $estado->{"{$r}_min"} = (float) $request->input("{$r}_min");
            }
            
            if ($request->has("{$r}_max") && $request->input("{$r}_max") !== null) {
                $estado->{"{$r}_max"} = (float) $request->input("{$r}_max");
            }
        }

        $estado->save();
        Log::info('✅ BD Actualizada con éxito:', $estado->toArray());

        return response()->json(['status' => 'ok', 'config' => $estado]);
    }
}

//actualizado 18/06/26