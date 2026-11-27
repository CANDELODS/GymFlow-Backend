<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class MembresiaAdquirida extends Model
{
    use HasFactory;
    // Indicamos el nombre de la tabla asociada.
    protected $table = 'membresia_adquirida';

    // Definimos la clave primaria personalizada.
    protected $primaryKey = 'id_membresia_adquirida';

    // Declaramos los atributos que pueden asignarse masivamente.
    protected $fillable = [
        'id_cliente',
        'id_tipo_membresia',
        'id_usuario_registro',
        'nombre_tipo_snapshot',
        'duracion_dias_snapshot',
        'fecha_inicio',
        'fecha_vencimiento',
        'valor_pagado',
        'metodo_pago',
    ];

    // Definimos las conversiones de atributos.
    protected function casts(): array
    {
        return [
            'duracion_dias_snapshot' => 'integer',
            'fecha_inicio' => 'date',
            'fecha_vencimiento' => 'datetime',
            'valor_pagado' => 'decimal:2',
        ];
    }


    // Relación con el cliente al que pertenece la membresía adquirida.
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(
            Cliente::class,
            'id_cliente', // Clave foranea en membresia_adquirida
            'id_cliente' // Clave local en Cliente
        );
    }

    // Relación con el tipo de membresía adquirido.
    public function tipoMembresia(): BelongsTo
    {
        return $this->belongsTo(
            TipoMembresia::class,
            'id_tipo_membresia', // Clave foranea en membresia_adquirida
            'id_tipo_membresia' // Clave local en TipoMembresia
        );
    }

    // Relación con el usuario administrativo que registró la adquisición.
    public function usuarioRegistro(): BelongsTo
    {
        return $this->belongsTo(
            UsuarioAdministrativo::class,
            'id_usuario_registro', // Clave foranea en membresia_adquirida
            'id_usuario' // Clave local en UsuarioAdministrativo
        );
    }

    // Relación con los registros de modificaciones de la membresía.
    public function modificaciones(): HasMany
    {
        return $this->hasMany(
            RegistroModificacion::class,
            'id_membresia_adquirida',
            'id_membresia_adquirida'
        );
    }
}
