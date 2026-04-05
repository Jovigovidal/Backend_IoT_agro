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
        'iniciar_llenado', 'meta_litros',
        'prog_hora', 'prog_min', 'prog_meta_litros',
        'r1', 'r2', 'r3', 'r4',
        'r1_en', 'r2_en', 'r3_en', 'r4_en',
        'fan_cmd',
        'box_temp', 'box_hum' // 👇 AGREGADOS PARA PERMITIR GUARDADO
    ];
}