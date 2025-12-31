<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    use HasFactory;

    protected $fillable = [
    'modo', 
    'relay1_status', 'relay1_enabled',
    'relay2_status', 'relay2_enabled',
    'relay3_status', 'relay3_enabled', 
    'relay4_status', 'relay4_enabled',
    'last_temp', 'last_hum', 'last_pres',
    'last_temp_agua', 'last_ph', 'last_tds', // <--- ¡ controlar los pines de agua
    'last_connection',
    'fan_speed' // <--- Nuevo campo para la velocidad del ventilador
    ];
}