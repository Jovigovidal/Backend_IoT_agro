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
        Schema::table('sistema_estados', function (Blueprint $table) {
            $table->string('r2_sensor')->default('temp_agua');
            $table->float('r2_min')->default(20.0);
            $table->float('r2_max')->default(30.0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
