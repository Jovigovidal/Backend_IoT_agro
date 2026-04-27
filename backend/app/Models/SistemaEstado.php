<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SistemaEstado extends Model
{
    use HasFactory;
    
    protected $table = 'sistema_estados';

    protected $fillable = [
        'modo', 
        'prog_hora', 'prog_min',
        'r1', 'r2', 'r3', 'r4',
        'r1_en', 'r2_en', 'r3_en', 'r4_en',
        'fan_cmd',
        'box_temp', 'box_hum', // 👇 AGREGADOS PARA PERMITIR GUARDADO
        'r1_sensor', 'r1_min', 'r1_max',
        'r2_sensor', 'r2_min', 'r2_max'
    ];
}