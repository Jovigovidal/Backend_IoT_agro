<?php

namespace App\Http\Controllers;

use App\Models\Medicion;
use App\Models\Configuracion;
use Illuminate\Http\Request;
use Carbon\Carbon; // Necesario para la hora

class MedicionController extends Controller
{
    // Obtener últimos datos para la web
    public function index()
    {
        return Medicion::orderBy('created_at', 'desc')->take(10)->get();
    }

    // Recibir datos del ESP32
    public function store(Request $request)
    {
        // =========================================================
        // 1. FILTRO DE HORARIO (Igual que tu Google Sheets)
        // =========================================================
        
        $ahora = Carbon::now(); // Hora del servidor (Perú)
        
        // Las horas permitidas
        $horasPermitidas = [1, 5, 12, 16, 20]; 

        $guardarNuevo = false;

        // CONDICIÓN: ¿Es la hora correcta? Y ¿Es el minuto 0?
        if (in_array($ahora->hour, $horasPermitidas) && $ahora->minute == 0) {
            
            // ANTI-DUPLICADOS:
            // Como el ESP32 manda cada 10s, llegarán 6 datos en el minuto 0.
            // Verificamos si YA guardamos un dato en esta hora hoy.
            $yaGuardado = Medicion::whereDate('created_at', $ahora->toDateString())
                                  ->whereHour('created_at', $ahora->hour)
                                  ->exists();

            if (!$yaGuardado) {
                $guardarNuevo = true;
            }
        }

        // SOLO GUARDAMOS SI SE CUMPLE EL FILTRO
        if ($guardarNuevo) {
            $medicion = new Medicion();
            $medicion->temperatura = $request->input('temperatura', 0);
            $medicion->humedad = $request->input('humedad', 0);
            $medicion->presion = $request->input('presion', 0);
            $medicion->save();
        }

        // =========================================================
        // 2. LÓGICA DE CONTROL (ESTO SIEMPRE SE EJECUTA)
        // =========================================================
        // Aunque no guardemos el dato, SIEMPRE respondemos al ESP32
        // con el estado de los botones. Así el control manual es rápido.
        
        $config = Configuracion::first();

        // Crear config por defecto si no existe
        if (!$config) {
            $config = Configuracion::create([
                'modo' => 'AUTO', 'relay1_status' => 0, 'relay2_status' => 0
            ]);
        }

        return response()->json([
            'status' => 'ok',
            'guardado' => $guardarNuevo ? 'SI' : 'NO', // Para ver en Monitor Serie
            'modo' => $config->modo,
            'r1' => $config->relay1_status,
            'r2' => $config->relay2_status,
            'r1_en' => $config->relay1_enabled,
            'r2_en' => $config->relay2_enabled
        ], 200);
    }
}