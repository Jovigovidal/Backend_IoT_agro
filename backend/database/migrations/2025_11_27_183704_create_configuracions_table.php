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
    Schema::create('configuracions', function (Blueprint $table) {
        $table->id();
        $table->string('modo')->default('AUTO'); // 'AUTO' o 'MANUAL'
        $table->boolean('relay1_status')->default(false); // Ventilador
        $table->boolean('relay2_status')->default(false); // Riego
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracions');
    }
};
