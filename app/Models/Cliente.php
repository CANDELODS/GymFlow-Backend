<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;
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

    // Última membresía adquirida por el cliente.
    public function ultimaMembresiaAdquirida(): HasOne
    {
        return $this->hasOne(
            MembresiaAdquirida::class,
            'id_cliente', // Clave foranea en membresia_adquirida
            'id_cliente' // Clave local en Cliente
        )
            // Primero vemos cual es su última membresía adquirida, y como criterios de desemptate tenemos
            // los campo 'created_at' y 'id_membresia_adquirida'
            ->ofMany([ //ofMany nos permite seleccionar un único registro de la relación.
                'fecha_inicio' => 'max', // max selecciona el valor máximo de una columna
                'created_at' => 'max',
                'id_membresia_adquirida' => 'max',
            ]);
    }
}
