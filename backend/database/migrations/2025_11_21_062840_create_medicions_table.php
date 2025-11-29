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
    Schema::create('medicions', function (Blueprint $table) {
        $table->id();
        $table->float('temperatura');
        $table->float('humedad');
        $table->float('presion')->nullable();
        $table->timestamps(); // Esto crea created_at (fecha registro)
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicions');
    }
};
