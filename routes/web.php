<?php

use Illuminate\Support\Facades\Route;

Route::get('/panel', function () {
    return response()->json([
        'mensaje' => 'Acceso al panel administrativo autorizado',
    ]);
})->middleware('auth:web');

Route::get('/', function () {
    return view('welcome');
});
