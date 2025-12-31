<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;


// RUTA PRINCIPAL: Carga el Angular
Route::get('/', function () {
    // Busca el archivo index.html que acabas de pegar en la carpeta public
    return File::get(public_path() . '/index.html');
});

// IMPORTANTE: Si recargas la página en una ruta interna de Angular, 
// esto evita el error 404 redirigiendo todo al index.
Route::fallback(function () {
    return File::get(public_path() . '/index.html');
});