<?php
namespace App\Http\Controllers;

use App\Models\Configuracion;
use Illuminate\Http\Request;

class ControlController extends Controller  
{   


public function updateFan(Request $request)
{
    $config = Configuracion::first();
    // Recibimos un booleano desde Angular y lo convertimos a 0 o 255
    $config->fan_speed = $request->state ? 255 : 0; 
    $config->save();

    return response()->json(['message' => 'Ventilador actualizado']);
}

}