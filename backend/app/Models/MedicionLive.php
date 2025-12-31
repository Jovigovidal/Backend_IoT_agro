<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MedicionLive extends Model
{
  // Asegura que apunte a tu tabla personalizada
  use HasFactory;
    protected $table = 'mediciones_live'; 

    protected $fillable = [
        'temperatura', 
        'humedad', 
        'presion',
        'temp_agua', 
        'ph',        
        'tds'
    ];
}
