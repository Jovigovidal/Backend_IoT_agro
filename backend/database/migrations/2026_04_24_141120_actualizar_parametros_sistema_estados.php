<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sistema_estados', function (Blueprint $table) {
            // 🚀 AHORA SÍ: NUESTROS UMBRALES INTELIGENTES
            $table->string('r1_sensor')->default('ph');
            $table->float('r1_min')->default(7.0);
            $table->float('r1_max')->default(8.0);
        });
    }

    public function down()
    {
        Schema::table('sistema_estados', function (Blueprint $table) {
            $table->dropColumn(['r1_sensor', 'r1_min', 'r1_max']);
        });
    }
};
