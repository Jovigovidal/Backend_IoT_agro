<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Medicion;
use App\Models\MedicionLive;
use App\Models\SistemaEstado;
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

        // 🪄 EL TRUCO MÁGICO PARA ANGULAR:
        // Como MedicionLive usa los nombres antiguos, los clonamos a los nombres 
        // nuevos al vuelo para que tu panel de Angular no se rompa ni note la diferencia.
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
            // 1. GUARDAR EN VIVO (Usamos los nombres ANTIGUOS para no romper la tabla)
            $live = new MedicionLive();
            $live->temperatura = $request->input('temp_aire', 0); 
            $live->humedad     = $request->input('hum_aire', 0);  
            $live->presion     = $request->input('presion', 0);
            $live->temp_agua   = $request->input('temp_agua', 0);
            $live->ph          = $request->input('ph', 0);
            $live->tds         = $request->input('tds', 0);
            // Nota: No guardamos box_temp aquí porque la tabla antigua no lo tiene
            $live->save(); // Usamos save() para saltar la protección $fillable

            // Limpieza de la rueda de hámster
            if (MedicionLive::count() > 100) {
                MedicionLive::orderBy('id', 'asc')->limit(10)->delete();
            }

            // 2. GUARDAR HISTÓRICO CADA 3 HORAS (Usamos los nombres NUEVOS)
            $ahora = Carbon::now();
            if ($ahora->hour % 3 == 0) {
                $yaGuardado = Medicion::whereDate('created_at', $ahora->toDateString())
                                      ->whereHour('created_at', $ahora->hour)
                                      ->exists();
                if (!$yaGuardado) {
                    $historico = new Medicion();
                    $historico->temp_aire = $request->input('temp_aire', 0);
                    $historico->hum_aire  = $request->input('hum_aire', 0);
                    $historico->presion   = $request->input('presion', 0);
                    $historico->temp_agua = $request->input('temp_agua', 0);
                    $historico->ph        = $request->input('ph', 0);
                    $historico->tds       = $request->input('tds', 0);
                    $historico->box_temp  = $request->input('box_temp', 0);
                    $historico->save();
                    Log::info("💾 Guardado histórico permanente exitoso.");
                }
            }

            // 3. ACTUALIZAR CAJA FUERTE Y RELÉS
            $estado = SistemaEstado::firstOrCreate([], ['modo' => 'AUTO']);
            $estado->box_temp = $request->input('box_temp', 0);
            $estado->box_hum  = $request->input('box_hum', 0);
            $estado->save();

            // 4. RESPUESTA AL ESP32
            return response()->json([
                'modo'    => $estado->modo,
                'r1'      => (bool)$estado->r1,
                'r2'      => (bool)$estado->r2,
                'r3'      => (bool)$estado->r3,
                'r4'      => (bool)$estado->r4,
                'r1_en'   => (bool)$estado->r1_en,
                'fan_cmd' => (int)$estado->fan_cmd
            ]);

        } catch (\Exception $e) {
            Log::error("❌ ERROR CRÍTICO: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateState(Request $request)
    {
        $estado = SistemaEstado::first();
        if ($request->has('fan_state')) {
            $estado->fan_cmd = $request->fan_state ? 1 : 0;
        } else {
            $estado->update($request->all());
        }
        $estado->save();
        return response()->json(['status' => 'ok']);
    }
}