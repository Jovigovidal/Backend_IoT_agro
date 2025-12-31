<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bitacora;

class BitacoraController extends Controller
{
    public function store(Request $request)
    {
        $log = new Bitacora();
        $log->evento = $request->input('evento', 'SISTEMA');
        $log->descripcion = $request->input('descripcion', '');
        $log->save();

        return response()->json(['status' => 'log_guardado']);
    }

    // Obtener últimos 5 eventos (Angular llama aquí)
    public function index()
    {
        return Bitacora::orderBy('created_at', 'desc')->take(5)->get();
    }
}
