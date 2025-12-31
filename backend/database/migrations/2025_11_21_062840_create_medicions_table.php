<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicions', function (Blueprint $table) {
            $table->id();
            
            // --- DATOS DEL AIRE ---
            // Nombres ajustados para coincidir con el JSON del ESP32
            $table->double('temp_aire')->nullable(); // Antes 'temperatura'
            $table->double('hum_aire')->nullable();  // Antes 'humedad'
            $table->double('presion')->nullable();

            // --- DATOS DEL AGUA ---
            $table->double('temp_agua')->nullable();
            $table->double('ph')->nullable();
            $table->double('tds')->nullable();

            // --- ⚠️ LO QUE FALTABA (Vital para tu código actual) ---
            $table->double('box_temp')->nullable();      // Temperatura del sistema (Caja)
            $table->boolean('llenando')->default(false); // ¿Está la bomba llenando?
            $table->double('volumen_actual_ml')->default(0); // Litros contados

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicions');
    }
};