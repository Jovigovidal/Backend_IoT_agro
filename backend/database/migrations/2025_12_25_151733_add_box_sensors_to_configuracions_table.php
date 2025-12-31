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
        Schema::table('configuracions', function (Blueprint $table) {
        $table->float('last_box_temp')->default(0)->nullable();
        $table->float('last_box_hum')->default(0)->nullable();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracions', function (Blueprint $table) {
        $table->dropColumn(['last_box_temp', 'last_box_hum']);
    });
    }
};
