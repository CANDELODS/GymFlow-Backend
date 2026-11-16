<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    // Indicamos el nombre de la tabla asociada.
    protected $table = 'cliente';

    // Definimos la clave primaria personalizada.
    protected $primaryKey = 'id_cliente';

    // Declaramos los atributos que pueden asignarse masivamente.
    protected $fillable = [
        'nombres',
        'apellidos',
        'documento',
        'telefono',
        'activo',
        'autorizacion_tratamiento',
        'fecha_autorizacion',
    ];

    // Definimos las conversiones de atributos.
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'autorizacion_tratamiento' => 'boolean',
            'fecha_autorizacion' => 'datetime',
        ];
    }

    // Membresías adquiridas por el cliente.
    public function membresiasAdquiridas(): HasMany
    {
        return $this->hasMany(
            MembresiaAdquirida::class,
            'id_cliente', // Clave foranea en membresia_adquirida
            'id_cliente' // Clave local en Cliente
        );
    }
}
