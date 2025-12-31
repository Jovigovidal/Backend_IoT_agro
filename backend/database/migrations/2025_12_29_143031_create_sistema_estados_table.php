<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sistema_estados', function (Blueprint $table) {
            $table->id();
            $table->string('modo')->default('AUTO'); // 'AUTO' o 'MANUAL'
            
            // Control de Llenado Remoto
            $table->boolean('iniciar_llenado')->default(false);
            $table->double('meta_litros')->default(0);
            
            // Programación Horaria Auto
            $table->integer('prog_hora')->default(-1);
            $table->integer('prog_min')->default(-1);
            $table->double('prog_meta_litros')->default(0);

            // Relays (Comandos Manuales desde Web)
            $table->boolean('r1')->default(false); // Oxígeno
            $table->boolean('r2')->default(false); // Timer/Libre
            $table->boolean('r3')->default(false); // Bomba
            $table->boolean('r4')->default(false); // Calentador
            
            // Habilitadores (Interruptores Maestros)
            $table->boolean('r1_en')->default(true);
            $table->boolean('r2_en')->default(true);
            $table->boolean('r3_en')->default(true);
            $table->boolean('r4_en')->default(true);

            // Ventilador
            $table->integer('fan_cmd')->default(0); // 0=Off, 1=On

            $table->timestamps();
        });

        // IMPORTANTE: Insertar la fila inicial (Configuración por defecto)
        DB::table('sistema_estados')->insert([
            'modo' => 'AUTO',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sistema_estados');
    }
};