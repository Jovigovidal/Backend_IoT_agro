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
        Schema::dropIfExists('configuracions'); // Borra si existe para empezar limpio

        Schema::create('configuracions', function (Blueprint $table) {
            $table->id();
            $table->string('modo')->default('AUTO'); 

            // RELÉS (1 al 4)
            $table->boolean('relay1_status')->default(false);
            $table->boolean('relay1_enabled')->default(true);
            $table->boolean('relay2_status')->default(false);
            $table->boolean('relay2_enabled')->default(true);
            $table->boolean('relay3_status')->default(false);
            $table->boolean('relay3_enabled')->default(true);
            $table->boolean('relay4_status')->default(false);
            $table->boolean('relay4_enabled')->default(true);

            // UMBRALES Y SENSORES PARA RELÉS (Zonas de Confort)
            $table->string('relay1_sensor')->default('temp_agua');
            $table->float('relay1_min')->default(20.0);
            $table->float('relay1_max')->default(30.0);
            $table->string('relay2_sensor')->default('ph');
            $table->float('relay2_min')->default(6.5);
            $table->float('relay2_max')->default(7.5);
            $table->string('relay3_sensor')->default('tds');
            $table->float('relay3_min')->default(300.0);
            $table->float('relay3_max')->default(500.0);
            $table->string('relay4_sensor')->default('temp_aire');
            $table->float('relay4_min')->default(20.0);
            $table->float('relay4_max')->default(30.0);

            // SENSORES (Aquí están los que daban error, ahora se crean desde el inicio)
            $table->double('last_temp')->default(0);
            $table->double('last_hum')->default(0);
            $table->double('last_pres')->default(0);
            $table->double('last_temp_agua')->default(0); 
            $table->double('last_ph')->default(0);
            $table->double('last_tds')->default(0);

            $table->timestamp('last_connection')->nullable();
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
