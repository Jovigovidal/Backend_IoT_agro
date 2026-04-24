<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicion extends Model
{
    use HasFactory;
    
    // Permitir asignación masiva de estos campos
    protected $fillable = [
        'temp_aire', 'hum_aire', 'presion',
        'temp_agua', 'ph', 'tds',
        'box_temp',
    ];
}