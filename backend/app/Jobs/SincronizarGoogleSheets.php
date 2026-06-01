<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Medicion;

class SincronizarGoogleSheets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Medicion $medicion;

    /**
     * Recibimos la medición que Laravel acaba de guardar en MySQL.
     */
    public function __construct(Medicion $medicion)
    {
        $this->medicion = $medicion;
    }

    /**
     * Aquí ocurre la magia en segundo plano.
     */
    public function handle(): void
    {
        $url = env('GOOGLE_SHEETS_WEBHOOK');

        if (!$url) {
            Log::error('Falta la URL de Google Sheets en el archivo .env');
            return;
        }

        try {
            // Envolvemos en try-catch por si hay errores de red (Ej. problemas de SSL)
            // withoutVerifying() desactiva la validación estricta SSL, solucionando el problema del reloj en 2026
            $response = Http::withoutVerifying()->get($url, [
                'temp_aire' => $this->medicion->temp_aire,
                'hum_aire'  => $this->medicion->hum_aire,
                'presion'   => $this->medicion->presion,
                'temp_agua' => $this->medicion->temp_agua,
                'ph'        => $this->medicion->ph,
                'tds'       => $this->medicion->tds,
                'evento'    => 'LARAVEL_SYNC'
            ]);
        } catch (\Exception $e) {
            Log::error("❌ Excepción de red al contactar a Google Sheets: " . $e->getMessage());
            throw $e;
        }

        if ($response->successful() && str_contains($response->body(), 'OK_SYNC_COMPLETO')) {
            Log::info("☁️ Registro ID {$this->medicion->id} sincronizado exitosamente a Google Sheets.");
        } else {
            Log::error("❌ Error al sincronizar a Google Sheets: " . $response->body());
            
            // Al lanzar una excepción, Laravel volverá a intentar subir el dato más tarde automáticamente
            throw new \Exception('Fallo al enviar a Google Sheets');
        }
    }
}
// prueba de sync .github