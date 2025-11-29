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
        // Por defecto en true (Habilitados)
        $table->boolean('relay1_enabled')->default(true); 
        $table->boolean('relay2_enabled')->default(true);
    });
}

public function down(): void
{
    Schema::table('configuracions', function (Blueprint $table) {
        $table->dropColumn(['relay1_enabled', 'relay2_enabled']);
    });
}
};
