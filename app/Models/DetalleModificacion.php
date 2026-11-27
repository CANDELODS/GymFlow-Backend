<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleModificacion extends Model
{
    // Indicamos el nombre de la tabla asociada.
    protected $table = 'detalle_modificacion';

    // Definimos la clave primaria personalizada.
    protected $primaryKey = 'id_detalle';

    // Deshabilitamos los timestamps porque la tabla no los contiene.
    public $timestamps = false;

    // Declaramos los atributos que pueden asignarse masivamente.
    protected $fillable = [
        'id_modificacion',
        'campo_modificado',
        'valor_anterior',
        'valor_nuevo',
    ];

    // Relación con el registro de modificación al que pertenece.
    public function registroModificacion(): BelongsTo
    {
        return $this->belongsTo(
            RegistroModificacion::class,
            'id_modificacion',
            'id_modificacion'
        );
    }
}