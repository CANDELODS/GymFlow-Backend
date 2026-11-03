<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class UsuarioAdministrativo extends Authenticatable
{
    protected $table = 'usuario_administrativo';

    protected $primaryKey = 'id_usuario';

    //Campos que pueden asignarse masivamente
    protected $fillable = [
        'usuario',
        'password',
        'rol',
        'activo',
        'intentos_fallidos',
        'bloqueado_hasta',
    ];
    //Evitamos que la contraseña se muestre cuando se serializa el modelo a JSON o array
    protected $hidden = [
        'password',
    ];
    //Definimos los tipos de datos de los campos
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo' => 'boolean',
            'intentos_fallidos' => 'integer',
            'bloqueado_hasta' => 'datetime',
        ];
    }
}

