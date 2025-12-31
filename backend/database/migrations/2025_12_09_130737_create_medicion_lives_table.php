<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('mediciones_live', function (Blueprint $table) {
        $table->id();
        $table->float('temperatura');
        $table->float('humedad');
        $table->float('presion');
        /* Sensores de Agua , ph y tds agregados */
        $table->float('temp_agua')->nullable();
        $table->float('ph')->nullable();
        $table->float('tds')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicion_lives');
    }
};
