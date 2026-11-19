<?php

use Illuminate\Support\Facades\Route;

Route::get('/panel', function () {
    return response()->json([
        'mensaje' => 'Acceso al panel administrativo autorizado',
    ]);
})->middleware('auth:web')->name('panel.index');

Route::get('/', function () {
    return view('welcome');
});
