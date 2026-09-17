<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sistema_estados', function (Blueprint $table) {
            // Agregamos la columna booleana para el OTA
            $table->boolean('actualizar_firmware')->default(false)->after('fan_cmd');
        });
    }

    public function down(): void
    {
        Schema::table('sistema_estados', function (Blueprint $table) {
            // Permite revertir el cambio si es necesario
            $table->dropColumn('actualizar_firmware');
        });
    }
};
