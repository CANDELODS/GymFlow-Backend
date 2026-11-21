<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class TipoMembresia extends Model
{
    use HasFactory;
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

    // Membresías adquiridas correspondientes a este tipo.
    public function membresiasAdquiridas(): HasMany
    {
        return $this->hasMany(
            MembresiaAdquirida::class,
            'id_tipo_membresia', // Clave foránea en membresia_adquirida
            'id_tipo_membresia' // Clave local en tipo_membresia
        );
    }
}
