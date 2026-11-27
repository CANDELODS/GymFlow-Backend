<?php

namespace App\Actions\MembresiaAdquirida;

use App\Events\MembresiaAdquiridaModificadaEvent;
use App\Models\Cliente;
use App\Models\MembresiaAdquirida;
use App\Models\TipoMembresia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActualizarMembresiaAdquirida
{
    public function ejecutar(
        MembresiaAdquirida $membresiaAdquirida,
        array $datos
    ): MembresiaAdquirida {

        return DB::transaction(function () use ($membresiaAdquirida, $datos) {

            // Recuperamos y bloqueamos la membresía que vamos a actualizar.
            $membresia = MembresiaAdquirida::where(
                'id_membresia_adquirida',
                $membresiaAdquirida->id_membresia_adquirida
            )
                ->lockForUpdate()
                ->firstOrFail();

            // Separamos el motivo de los campos de la membresía.
            $motivo = $datos['motivo'];
            unset($datos['motivo']);

            // Inicializamos los arreglos que almacenarán los cambios.
            $cambios = [];
            $actualizacion = [];

            // Comparamos y actualizamos el cliente, si fue proporcionado.
            if (array_key_exists('id_cliente', $datos)) {

                $nuevoIdCliente = (int) $datos['id_cliente'];

                if ($membresia->id_cliente != $nuevoIdCliente) {

                    // Verificamos que el nuevo cliente esté activo.
                    Cliente::where('activo', true)
                        ->lockForUpdate()
                        ->findOrFail($nuevoIdCliente);

                    $cambios['id_cliente'] = [
                        'anterior' => $membresia->id_cliente,
                        'nuevo' => $nuevoIdCliente,
                    ];

                    $actualizacion['id_cliente'] = $nuevoIdCliente;
                }
            }

            // Comparamos y actualizamos el tipo de membresía, si fue proporcionado.
            if (array_key_exists('id_tipo_membresia', $datos)) {

                $nuevoIdTipo = (int) $datos['id_tipo_membresia'];

                if ($membresia->id_tipo_membresia != $nuevoIdTipo) {

                    // Obtenemos el nuevo tipo de membresía y verificamos que esté activo.
                    $tipoMembresia = TipoMembresia::where('activo', true)
                        ->lockForUpdate()
                        ->findOrFail($nuevoIdTipo);

                    // Registramos los cambios en los snapshots.
                    $cambios['id_tipo_membresia'] = [
                        'anterior' => $membresia->id_tipo_membresia,
                        'nuevo' => $nuevoIdTipo,
                    ];

                    $cambios['nombre_tipo_snapshot'] = [
                        'anterior' => $membresia->nombre_tipo_snapshot,
                        'nuevo' => $tipoMembresia->nombre,
                    ];

                    $cambios['duracion_dias_snapshot'] = [
                        'anterior' => $membresia->duracion_dias_snapshot,
                        'nuevo' => $tipoMembresia->duracion_dias,
                    ];

                    $actualizacion['id_tipo_membresia'] = $nuevoIdTipo;
                    $actualizacion['nombre_tipo_snapshot'] = $tipoMembresia->nombre;
                    $actualizacion['duracion_dias_snapshot'] = $tipoMembresia->duracion_dias;
                } else {
                    // Si el tipo no cambia, conservamos su duración registrada.
                    $tipoMembresia = null;
                }
            } else {
                $tipoMembresia = null;
            }

            // Comparamos la fecha de inicio, si fue proporcionada.
            $fechaInicio = $membresia->fecha_inicio->toDateString();

            if (array_key_exists('fecha_inicio', $datos)) {

                $nuevaFechaInicio = Carbon::parse($datos['fecha_inicio'])
                    ->toDateString();

                if ($fechaInicio !== $nuevaFechaInicio) {

                    $cambios['fecha_inicio'] = [
                        'anterior' => $fechaInicio,
                        'nuevo' => $nuevaFechaInicio,
                    ];

                    $actualizacion['fecha_inicio'] = $nuevaFechaInicio;

                    $fechaInicio = $nuevaFechaInicio;
                }
            }

            // Comparamos el valor pagado, si fue proporcionado.
            if (array_key_exists('valor_pagado', $datos)) {

                // number_format(numero, decimales, separador_decimal, separador_miles);
                $nuevoValor = number_format(
                    $datos['valor_pagado'],
                    2,
                    '.',
                    '' // No utiliza separador de miles
                );

                $valorActual = number_format(
                    $membresia->valor_pagado,
                    2,
                    '.',
                    ''
                );

                if ($valorActual !== $nuevoValor) {

                    $cambios['valor_pagado'] = [
                        'anterior' => $valorActual,
                        'nuevo' => $nuevoValor,
                    ];

                    $actualizacion['valor_pagado'] = $nuevoValor;
                }
            }

            // Comparamos el método de pago, si fue proporcionado.
            if (array_key_exists('metodo_pago', $datos)) {

                if ($membresia->metodo_pago !== $datos['metodo_pago']) {

                    $cambios['metodo_pago'] = [
                        'anterior' => $membresia->metodo_pago,
                        'nuevo' => $datos['metodo_pago'],
                    ];

                    $actualizacion['metodo_pago'] = $datos['metodo_pago'];
                }
            }

            // Rechazamos la actualización si no existe ningún cambio real.
            if (empty($cambios)) {
                throw ValidationException::withMessages([
                    'membresia' => 'No se detectaron cambios en la información de la membresía.',
                ]);
            }

            // Recalculamos el vencimiento si cambió la fecha de inicio o el tipo.
            if (
                array_key_exists('fecha_inicio', $cambios) ||
                array_key_exists('id_tipo_membresia', $cambios)
            ) {

                // Utilizamos la duración del nuevo tipo o la duración registrada.
                $duracionDias = $tipoMembresia !== null
                    ? $tipoMembresia->duracion_dias
                    : $membresia->duracion_dias_snapshot;

                $nuevaFechaVencimiento = Carbon::parse($fechaInicio)
                    ->addDays($duracionDias - 1)
                    ->setTime(23, 59, 59);

                $fechaVencimientoAnterior = $membresia->fecha_vencimiento
                    ->format('Y-m-d H:i:s');

                $fechaVencimientoNueva = $nuevaFechaVencimiento
                    ->format('Y-m-d H:i:s');

                // Registramos el cambio de vencimiento únicamente si es diferente.
                if ($fechaVencimientoAnterior !== $fechaVencimientoNueva) {

                    $cambios['fecha_vencimiento'] = [
                        'anterior' => $fechaVencimientoAnterior,
                        'nuevo' => $fechaVencimientoNueva,
                    ];

                    $actualizacion['fecha_vencimiento'] = $nuevaFechaVencimiento;
                }
            }

            // Actualizamos la membresía existente.
            $membresia->update($actualizacion);

            // Despachamos el evento para registrar la auditoría.
            event(new MembresiaAdquiridaModificadaEvent(
                $membresia->refresh(),
                (int) auth()->id(),
                $motivo,
                $cambios
            ));

            // Devolvemos la membresía actualizada.
            return $membresia->refresh();
        });
    }
}
