<?php

namespace App\Models;

use App\Models\DetalleModificacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegistroModificacion extends Model
{
    // Indicamos el nombre de la tabla asociada.
    protected $table = 'registro_modificaciones';

    // Definimos la clave primaria personalizada.
    protected $primaryKey = 'id_modificacion';

    // Deshabilitamos los timestamps porque la tabla no los contiene.
    public $timestamps = false;

    // Declaramos los atributos que pueden asignarse masivamente.
    protected $fillable = [
        'id_membresia_adquirida',
        'id_usuario',
        'fecha_hora',
        'motivo',
    ];

    // Definimos las conversiones de atributos.
    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
        ];
    }

    // Relación con la membresía adquirida modificada.
    public function membresiaAdquirida(): BelongsTo
    {
        return $this->belongsTo(
            MembresiaAdquirida::class,
            'id_membresia_adquirida',
            'id_membresia_adquirida'
        );
    }

    // Relación con el usuario administrativo responsable.
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            UsuarioAdministrativo::class,
            'id_usuario',
            'id_usuario'
        );
    }

    // Relación con los detalles de la modificación.
    public function detalles(): HasMany
    {
        return $this->hasMany(
            DetalleModificacion::class,
            'id_modificacion',
            'id_modificacion'
        );
    }
}