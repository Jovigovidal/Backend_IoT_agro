<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    // CAMBIA 'configuraciones' POR 'configuracions'
    Schema::table('configuracions', function (Blueprint $table) {
        $table->integer('fan_speed')->default(0); 
    });
}

public function down()
{
    Schema::table('configuracions', function (Blueprint $table) {
        $table->dropColumn('fan_speed');
    });
}
};
    