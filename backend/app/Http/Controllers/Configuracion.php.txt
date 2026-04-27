<?php

namespace App\Http\Controllers;
use App\Models\Configuracion;
use Illuminate\Http\Request;

class ControlController extends Controller  
{   

    protected $fillable = [
    'modo', 
    'relay1_status', 'relay1_enabled', 
    // ... tus otros campos ...
    'fan_speed',
    'last_temp', 'last_hum', 'last_pres', 'last_temp_agua', 'last_ph', 'last_tds',
    
    // --- AGREGA ESTOS DOS ---
    'last_box_temp', 
    'last_box_hum' 
];
}