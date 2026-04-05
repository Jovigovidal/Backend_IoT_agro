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
            $table->string('modo')->default('AUTO'); 
            
            // Control de Llenado Remoto
            $table->boolean('iniciar_llenado')->default(false);
            $table->double('meta_litros')->default(0);
            
            // Programación Horaria Auto
            $table->integer('prog_hora')->default(-1);
            $table->integer('prog_min')->default(-1);
            $table->double('prog_meta_litros')->default(0);

            // Relays
            $table->boolean('r1')->default(false); 
            $table->boolean('r2')->default(false); 
            $table->boolean('r3')->default(false); 
            $table->boolean('r4')->default(false); 
            
            // Habilitadores
            $table->boolean('r1_en')->default(true);
            $table->boolean('r2_en')->default(true);
            $table->boolean('r3_en')->default(true);
            $table->boolean('r4_en')->default(true);

            // Ventilador
            $table->integer('fan_cmd')->default(0); 

            // 👇 AQUÍ ESTÁN LAS COLUMNAS QUE FALTABAN 👇
            $table->double('box_temp')->default(0);
            $table->double('box_hum')->default(0);

            $table->timestamps();
        });

        // Insertar la fila inicial por defecto
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