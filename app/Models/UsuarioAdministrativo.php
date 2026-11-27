<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UsuarioAdministrativo extends Authenticatable
{
    use HasFactory;
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

    // Membresías adquiridas registradas por este usuario administrativo.
    public function membresiasRegistradas(): HasMany
    {
        return $this->hasMany(
            MembresiaAdquirida::class,
            'id_usuario_registro', // Clave foránea en membresia_adquirida
            'id_usuario' // Clave local en usuario_administrativo
        );
    }

    // Modificaciones registradas por este usuario administrativo.
    public function modificacionesRegistradas(): HasMany
    {
        return $this->hasMany(
            RegistroModificacion::class,
            'id_usuario',
            'id_usuario'
        );
    }
}
