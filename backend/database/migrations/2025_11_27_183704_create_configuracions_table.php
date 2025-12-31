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
