<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoMembresia extends Model
{
    // Indicamos el nombre de la tabla asociada.
    protected $table = 'tipo_membresia';

    // Definimos la clave primaria personalizada.
    protected $primaryKey = 'id_tipo_membresia';

    // Declaramos los atributos que pueden asignarse masivamente.
    protected $fillable = [
        'nombre',
        'duracion_dias',
        'precio',
        'activo',
    ];

    // Definimos las conversiones de atributos.
    protected function casts(): array
    {
        return [
            'duracion_dias' => 'integer',
            'precio' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }
}
