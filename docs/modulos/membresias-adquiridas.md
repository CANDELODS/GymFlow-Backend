# Módulo de gestión de membresías adquiridas -- GymFlow

## 1. Objetivo y alcance

El módulo de gestión de membresías adquiridas permite registrar y actualizar las membresías que los clientes adquieren en el gimnasio, asociando cada registro con un cliente, un tipo de membresía y el usuario administrativo responsable de realizar la operación.

El registro contempla la selección de un cliente activo, un tipo de membresía disponible, la fecha de inicio, el valor pagado y el método de pago utilizado. A partir de la duración del tipo de membresía seleccionado, el sistema calcula automáticamente la fecha de vencimiento.

La actualización permite modificar parcial o simultáneamente el cliente, el tipo de membresía, la fecha de inicio, el valor pagado y el método de pago. Cuando cambia la fecha de inicio o el tipo de membresía, el sistema recalcula automáticamente la fecha de vencimiento.

Una característica importante del módulo es la conservación de la información histórica. Al registrar una membresía adquirida, se almacenan snapshots del nombre y la duración del tipo de membresía seleccionado. De esta manera, las modificaciones posteriores realizadas sobre el tipo original no alteran la información de la adquisición. Cuando se actualiza el tipo de membresía de una adquisición, los snapshots se actualizan con la información del nuevo tipo seleccionado.

Adicionalmente, las modificaciones realizadas sobre una membresía adquirida generan registros de auditoría que permiten identificar al usuario administrativo responsable, el motivo de la modificación y los valores anteriores y nuevos de cada campo modificado.

Las operaciones de registro están disponibles para usuarios administrativos con rol Dueño o Empleado, mientras que la actualización está restringida exclusivamente al rol Dueño.

### Funcionalidades implementadas

- Registro de membresías adquiridas para clientes activos.
- Asociación de la adquisición con un tipo de membresía activo.
- Registro automático del usuario administrativo responsable.
- Definición de la fecha de inicio de la membresía.
- Cálculo automático de la fecha de vencimiento según la duración del tipo de membresía.
- Registro del valor pagado.
- Selección del método de pago entre las opciones permitidas.
- Conservación de snapshots del nombre y duración del tipo de membresía.
- Protección de la información histórica ante modificaciones posteriores del tipo de membresía.
- Conservación de las adquisiciones existentes cuando se desactiva un tipo de membresía.
- Actualización parcial o simultánea de los datos de una membresía adquirida.
- Cambio del cliente asociado a una membresía adquirida.
- Cambio del tipo de membresía y actualización de sus snapshots.
- Recalculación automática de la fecha de vencimiento cuando cambia la fecha de inicio o el tipo de membresía.
- Registro del motivo de cada modificación.
- Auditoría de los campos modificados, conservando los valores anteriores y nuevos.
- Identificación del usuario administrativo responsable de cada modificación.
- Validación de los datos mediante Form Requests.
- Protección de las operaciones mediante autenticación y verificación del estado de la cuenta.
- Restricción de la actualización al rol Dueño.
- Ejecución de las operaciones mediante transacciones de base de datos.
- Control de concurrencia entre el registro de una adquisición y la desactivación de su tipo de membresía.
- Validación automatizada de los escenarios de registro, actualización, permisos, reglas de negocio e integridad transaccional.

## 2. Tecnologías utilizadas

| Tecnología | Responsabilidad |
|---|---|
| Laravel 13 | Backend y gestión de solicitudes. |
| Laravel Sanctum | Autenticación de solicitudes API. |
| MySQL 8 | Almacenamiento de membresías adquiridas, relaciones y registros de auditoría. |
| PHPUnit | Pruebas automatizadas. |
| Carbon | Manipulación y cálculo de fechas. |

## 3. Componentes y responsabilidades

### MembresiaAdquirida

Archivo: `app/Models/MembresiaAdquirida.php`

Representa el modelo asociado a la tabla `membresia_adquirida`.

Configuraciones principales:

- Tabla personalizada: `membresia_adquirida`.
- Clave primaria: `id_membresia_adquirida`.
- Representa la adquisición de una membresía por parte de un cliente.
- Almacena la referencia al cliente, al tipo de membresía y al usuario administrativo que realizó el registro.
- Conserva los snapshots del nombre y la duración del tipo de membresía.
- Almacena la fecha de inicio, la fecha de vencimiento, el valor pagado y el método de pago.
- Define las conversiones de los atributos correspondientes a fechas y valores numéricos.

### RegistrarMembresiaAdquiridaRequest

Archivo: `app/Http/Requests/MembresiaAdquirida/RegistrarMembresiaAdquiridaRequest.php`

Valida y autoriza las solicitudes de registro de membresías adquiridas.

**Autorización:**

Permite realizar la operación a usuarios autenticados con rol `dueno` o `empleado`.

**Campos validados:**

- `id_cliente`: obligatorio, de tipo entero y debe corresponder a un cliente existente y activo.
- `id_tipo_membresia`: obligatorio, de tipo entero y debe corresponder a un tipo de membresía existente y activo.
- `fecha_inicio`: obligatoria, debe cumplir el formato `Y-m-d` y no puede ser posterior a la fecha actual.
- `valor_pagado`: obligatorio, de tipo numérico y mayor que cero.
- `metodo_pago`: obligatorio, de tipo texto y debe pertenecer a las opciones permitidas: `efectivo`, `nequi`, `bancolombia` u `otro`.

Las reglas de validación correspondientes a los campos de la membresía se obtienen mediante la clase `ReglasMembresiaAdquirida`, evitando duplicar las mismas definiciones en los diferentes componentes del sistema.

El Form Request también proporciona mensajes de validación personalizados en español y atributos descriptivos para los errores.

La fecha de vencimiento no se recibe desde el frontend, ya que es calculada por el backend a partir de la fecha de inicio y la duración del tipo de membresía.

### ActualizarMembresiaAdquiridaRequest

Archivo: `app/Http/Requests/MembresiaAdquirida/ActualizarMembresiaAdquiridaRequest.php`

Valida y autoriza las solicitudes de actualización de membresías adquiridas.

**Autorización:**

Permite realizar la operación exclusivamente a usuarios autenticados con rol `dueno`.

**Campos validados:**

- `id_cliente`: opcional; cuando se envía, debe ser de tipo entero y corresponder a un cliente existente y activo.
- `id_tipo_membresia`: opcional; cuando se envía, debe ser de tipo entero y corresponder a un tipo de membresía existente y activo.
- `fecha_inicio`: opcional; cuando se envía, debe cumplir las reglas de validación de fecha establecidas para la membresía.
- `valor_pagado`: opcional; cuando se envía, debe ser numérico y cumplir la restricción de valor positivo.
- `metodo_pago`: opcional; cuando se envía, debe pertenecer a las opciones permitidas: `efectivo`, `nequi`, `bancolombia` u `otro`.
- `motivo`: obligatorio, de tipo texto, no puede estar vacío y tiene una longitud máxima de 255 caracteres.

El Request utiliza reglas de actualización parcial mediante `sometimes|required`, por lo que únicamente valida los campos modificables que se envían en la solicitud.

También comprueba que se incluya al menos un campo modificable. El motivo de la modificación es obligatorio incluso cuando se actualiza un solo atributo.

La autorización impide que los empleados realicen actualizaciones de membresías adquiridas.

### ReglasMembresiaAdquirida

Archivo: `app/Rules/ReglasMembresiaAdquirida.php`

Centraliza las reglas de validación correspondientes a los datos de una membresía adquirida.

La clase proporciona tres métodos estáticos:

- `rules()`: devuelve las reglas de validación de los campos de la membresía.
- `messages()`: proporciona los mensajes personalizados para los errores de validación.
- `attributes()`: define los nombres descriptivos de los atributos.

Esta implementación permite reutilizar las mismas reglas de validación en diferentes componentes del sistema, manteniendo la consistencia de las validaciones y evitando duplicar código.

Actualmente, la clase es utilizada por `RegistrarMembresiaAdquiridaRequest` y también permite su reutilización en otros procesos, como el registro de clientes con su primera membresía.

La clase no contiene lógica de negocio ni ejecuta operaciones sobre la base de datos; su responsabilidad se limita a proporcionar las definiciones necesarias para validar los datos de una membresía adquirida.

### RegistrarMembresiaAdquirida

Archivo: `app/Actions/MembresiaAdquirida/RegistrarMembresiaAdquirida.php`

Centraliza la operación de registro de membresías adquiridas.

La operación se ejecuta dentro de una transacción mediante `DB::transaction()`.

Responsabilidades:

1. Recibir los datos previamente validados.
2. Consultar el tipo de membresía seleccionado y comprobar que continúe activo.
3. Bloquear el registro del tipo de membresía mediante `lockForUpdate()`, evitando que otra operación concurrente lo modifique mientras se realiza el registro.
4. Obtener el nombre y la duración actuales del tipo de membresía.
5. Calcular la fecha de vencimiento utilizando la fecha de inicio y la duración correspondiente.
6. Registrar la adquisición con la información del cliente, el tipo de membresía y el usuario administrativo responsable.
7. Almacenar los snapshots del nombre y la duración del tipo de membresía.
8. Guardar la fecha de inicio, fecha de vencimiento, valor pagado y método de pago.
9. Devolver la membresía adquirida registrada.

La operación utiliza `auth()->id()` para obtener automáticamente el identificador del usuario administrativo autenticado que realiza el registro.

### ActualizarMembresiaAdquirida

Archivo: `app/Actions/MembresiaAdquirida/ActualizarMembresiaAdquirida.php`

Centraliza la operación de actualización de membresías adquiridas.

La operación se ejecuta dentro de una transacción mediante `DB::transaction()` y utiliza `lockForUpdate()` para bloquear el registro de la membresía durante la actualización.

Responsabilidades:

1. Recibir la membresía adquirida y los datos previamente validados.
2. Bloquear el registro de la membresía adquirida mediante `lockForUpdate()`.
3. Separar el motivo de modificación de los campos que se actualizarán.
4. Comprobar que el cliente seleccionado exista y se encuentre activo cuando se solicita un cambio de cliente.
5. Comprobar que el tipo de membresía seleccionado exista y se encuentre activo cuando se solicita un cambio de tipo.
6. Actualizar los snapshots del nombre y la duración cuando cambia el tipo de membresía.
7. Identificar los cambios reales en los campos modificables, evitando registrar modificaciones cuando los valores enviados son idénticos a los existentes.
8. Recalcular la fecha de vencimiento cuando cambia la fecha de inicio o el tipo de membresía, utilizando la duración correspondiente.
9. Guardar los cambios realizados en la membresía adquirida.
10. Despachar el evento `MembresiaAdquiridaModificadaEvent` con la membresía actualizada, el usuario responsable, el motivo y los campos modificados.
11. Devolver la membresía adquirida actualizada.

La operación utiliza una transacción para garantizar que la actualización de la membresía y el registro de auditoría se mantengan como una sola unidad de trabajo. Si ocurre una excepción durante la operación, los cambios se revierten.

### MembresiaAdquiridaController

Archivo: `app/Http/Controllers/MembresiaAdquiridaController.php`

Coordina las solicitudes HTTP relacionadas con el registro y la actualización de membresías adquiridas.

Implementa los métodos `store()` y `update()`.

**Responsabilidades de `store()`:**

1. Recibir la solicitud mediante `RegistrarMembresiaAdquiridaRequest`.
2. Obtener los datos previamente validados.
3. Ejecutar la acción `RegistrarMembresiaAdquirida`.
4. Transformar el resultado mediante `MembresiaAdquiridaResource`.
5. Devolver una respuesta JSON con HTTP `201 Created`, un mensaje de confirmación y los datos de la membresía adquirida.

**Responsabilidades de `update()`:**

1. Recibir la solicitud mediante `ActualizarMembresiaAdquiridaRequest`.
2. Obtener los datos previamente validados.
3. Ejecutar la acción `ActualizarMembresiaAdquirida`.
4. Transformar el resultado mediante `MembresiaAdquiridaResource`.
5. Devolver una respuesta JSON con HTTP `200 OK`, un mensaje de confirmación y los datos de la membresía adquirida actualizada.

### MembresiaAdquiridaResource

Archivo: `app/Http/Resources/MembresiaAdquiridaResource.php`

Transforma el modelo `MembresiaAdquirida` en una respuesta JSON controlada.

Expone los datos correspondientes a:

- `id_membresia_adquirida`
- `id_cliente`
- `id_tipo_membresia`
- `id_usuario_registro`
- `nombre_tipo_snapshot`
- `duracion_dias_snapshot`
- `fecha_inicio`
- `fecha_vencimiento`
- `valor_pagado`
- `metodo_pago`

Las fechas se presentan con formatos definidos para facilitar su interpretación desde el frontend.

### MembresiaAdquiridaModificadaEvent

Archivo: `app/Events/MembresiaAdquiridaModificadaEvent.php`

Representa el evento que se genera cuando se actualiza una membresía adquirida.

Transporta la información necesaria para registrar la modificación:

- La membresía adquirida actualizada.
- El identificador del usuario administrativo responsable.
- El motivo de la modificación.
- Los campos que fueron modificados, incluyendo sus valores anteriores y nuevos.

Este evento permite desacoplar la operación de actualización de la lógica encargada de registrar la auditoría.

### RegistrarModificacionMembresiaListener

Archivo: `app/Listeners/RegistrarModificacionMembresiaListener.php`

Escucha el evento `MembresiaAdquiridaModificadaEvent` y se encarga de registrar la auditoría de las modificaciones realizadas sobre una membresía adquirida.

Responsabilidades:

1. Recibir el evento de modificación.
2. Crear un registro general en `registro_modificaciones`.
3. Asociar el registro con la membresía adquirida y el usuario administrativo responsable.
4. Almacenar el motivo de la modificación.
5. Registrar cada campo modificado en `detalle_modificacion`.
6. Conservar el nombre del campo y sus valores anteriores y nuevos.

El listener permite mantener un historial de las modificaciones realizadas sin incorporar la lógica de auditoría directamente en el controlador.

El registro de auditoría se ejecuta dentro de la transacción iniciada por la Action de actualización. De esta manera, si ocurre un error durante el proceso, la modificación de la membresía y sus registros de auditoría se revierten conjuntamente.

### RegistroModificacion

Archivo: `app/Models/RegistroModificacion.php`

Representa el modelo asociado a la tabla `registro_modificaciones`.

Almacena la información general de cada modificación realizada sobre una membresía adquirida:

- Identificador de la membresía adquirida.
- Identificador del usuario administrativo responsable.
- Fecha y hora de la modificación.
- Motivo de la modificación.

Establece las relaciones necesarias con la membresía adquirida, el usuario administrativo y los detalles de modificación.

### DetalleModificacion

Archivo: `app/Models/DetalleModificacion.php`

Representa el modelo asociado a la tabla `detalle_modificacion`.

Almacena el detalle de cada campo modificado dentro de un registro de auditoría:

- Identificador del registro de modificación.
- Nombre del campo modificado.
- Valor anterior.
- Valor nuevo.

Permite identificar de manera independiente cada cambio realizado durante una actualización.

## 4. Flujos de operación

El registro y la actualización de membresías adquiridas se realizan mediante solicitudes HTTP `POST` y `PATCH`, respectivamente.

### Endpoint de registro

`POST /api/membresias-adquiridas`

**Middlewares aplicados:**

- `auth:sanctum`: requiere autenticación.
- `usuario.activo`: impide el acceso a cuentas desactivadas.

La autorización por rol se realiza mediante `RegistrarMembresiaAdquiridaRequest`, que permite únicamente a usuarios con rol `dueno` o `empleado`.

**Nombre de ruta:**

`membresias-adquiridas.store`

### Proceso de registro

El proceso sigue este orden:

1. El usuario administrativo accede a la funcionalidad de registro de membresías adquiridas.
2. El frontend envía una solicitud `POST /api/membresias-adquiridas` con los datos del cliente, tipo de membresía, fecha de inicio, valor pagado y método de pago.
3. Laravel comprueba la autenticación mediante `auth:sanctum`.
4. El middleware `VerificarUsuarioActivo` comprueba que la cuenta del usuario continúe activa.
5. `RegistrarMembresiaAdquiridaRequest` autoriza la operación y valida los datos recibidos.
6. `MembresiaAdquiridaController` obtiene los datos previamente validados.
7. El controlador ejecuta la acción `RegistrarMembresiaAdquirida`.
8. La acción inicia una transacción y consulta el tipo de membresía seleccionado, comprobando que se encuentre activo.
9. La acción bloquea el registro del tipo de membresía mediante `lockForUpdate()`.
10. Se obtienen el nombre y la duración del tipo de membresía para almacenarlos como snapshots.
11. Se calcula automáticamente la fecha de vencimiento.
12. Se registra la membresía adquirida con los datos recibidos y los valores calculados.
13. La transacción confirma la operación.
14. `MembresiaAdquiridaResource` transforma el modelo registrado.
15. El backend devuelve HTTP `201 Created` con un mensaje de confirmación y los datos de la adquisición.

### Endpoint de actualización

`PATCH /api/membresias-adquiridas/{membresiaAdquirida}`

**Middlewares aplicados:**

- `auth:sanctum`: requiere autenticación.
- `usuario.activo`: impide el acceso a cuentas desactivadas.
- `rol.administrativo`: dueno: solo permite el accesi a usuarios administrativos con el rol dueno.

La autorización por rol se realiza mediante `ActualizarMembresiaAdquiridaRequest`, que permite únicamente a usuarios con rol `dueno`.

**Nombre de ruta:**

`membresias-adquiridas.update`

### Proceso de actualización

El proceso sigue este orden:

1. El dueño accede a la funcionalidad de actualización de una membresía adquirida.
2. El frontend envía una solicitud `PATCH /api/membresias-adquiridas/{membresiaAdquirida}` con uno o varios campos modificables y el motivo de la modificación.
3. Laravel comprueba la autenticación mediante `auth:sanctum`.
4. El middleware `VerificarUsuarioActivo` comprueba que la cuenta del usuario continúe activa.
5. `ActualizarMembresiaAdquiridaRequest` autoriza la operación y valida los datos recibidos.
6. `MembresiaAdquiridaController` obtiene los datos previamente validados.
7. El controlador ejecuta la acción `ActualizarMembresiaAdquirida`.
8. La acción inicia una transacción y bloquea el registro de la membresía adquirida mediante `lockForUpdate()`.
9. Cuando se solicita un cambio de cliente, comprueba que el cliente seleccionado exista y se encuentre activo.
10. Cuando se solicita un cambio de tipo de membresía, comprueba que el nuevo tipo exista y se encuentre activo.
11. Si cambia el tipo de membresía, actualiza los snapshots del nombre y la duración.
12. Identifica los campos cuyos valores realmente cambiaron y evita registrar modificaciones cuando no existen cambios efectivos.
13. Si cambia la fecha de inicio o el tipo de membresía, calcula nuevamente la fecha de vencimiento.
14. Guarda los cambios realizados en la membresía adquirida.
15. Despacha el evento `MembresiaAdquiridaModificadaEvent` con la información de la modificación.
16. `RegistrarModificacionMembresiaListener` recibe el evento y registra la información general y el detalle de los campos modificados.
17. La transacción confirma la actualización y los registros de auditoría.
18. `MembresiaAdquiridaResource` transforma el modelo actualizado.
19. El backend devuelve HTTP `200 OK` con un mensaje de confirmación y los datos de la membresía adquirida actualizada.

Si ocurre una excepción durante la actualización o el registro de auditoría, la transacción revierte los cambios realizados.

### Cálculo de la fecha de vencimiento

La fecha de vencimiento se calcula considerando que el día de inicio también forma parte de la duración de la membresía.

Por esta razón, se utiliza la siguiente operación:

```php
Carbon::parse($fechaInicio)
    ->addDays($duracionDias - 1)
    ->setTime(23, 59, 59);
```

De esta manera, una membresía de 30 días que inicia el 1 de noviembre vence el 30 de noviembre a las 23:59:59.

Para una membresía de un solo día, la fecha de vencimiento corresponde al mismo día de inicio a las 23:59:59.

Durante la actualización, este cálculo se vuelve a ejecutar cuando cambia la fecha de inicio o el tipo de membresía. Si se modifica otro campo, como el valor pagado o el método de pago, la fecha de vencimiento se conserva.

El cálculo se realiza en el backend, por lo que el frontend no puede establecer arbitrariamente la fecha de vencimiento.

### Respuesta exitosa del registro

La API devuelve una respuesta JSON con la siguiente estructura:

```json
{
    "mensaje": "Membresía adquirida registrada correctamente.",
    "membresia_adquirida": {
        "id_membresia_adquirida": 1,
        "id_cliente": 1,
        "id_tipo_membresia": 1,
        "id_usuario_registro": 1,
        "nombre_tipo_snapshot": "Mensual",
        "duracion_dias_snapshot": 30,
        "fecha_inicio": "2026-11-01",
        "fecha_vencimiento": "2026-11-30 23:59:59",
        "valor_pagado": "80000.00",
        "metodo_pago": "efectivo"
    }
}
```

Los valores son ilustrativos.

### Respuestas de error

| Código HTTP | Situación |
|---|---|
| `401 Unauthorized` | El usuario no está autenticado. |
| `403 Forbidden` | La cuenta del usuario está desactivada o no tiene autorización para realizar la operación. |
| `404 Not Found` | La membresía adquirida solicitada no existe. |
| `422 Unprocessable Entity` | Los datos enviados incumplen las reglas de validación, no se detectan cambios reales o el cliente o tipo de membresía seleccionado no existe o se encuentra inactivo. |

## 5. Reglas de validación y seguridad

### Autorización por roles

El registro de membresías adquiridas está permitido a los usuarios con rol `dueno` y `empleado`.

La actualización de membresías adquiridas está permitida exclusivamente al usuario con rol `dueno`.

La autorización se comprueba mediante los respectivos Form Requests:

- `RegistrarMembresiaAdquiridaRequest`.
- `ActualizarMembresiaAdquiridaRequest`.

### Cliente

El cliente debe existir en la base de datos y encontrarse activo.

No se permite registrar una membresía adquirida para un cliente inexistente o inactivo.

Durante la actualización, si se envía un nuevo `id_cliente`, el sistema comprueba nuevamente que el cliente seleccionado exista y se encuentre activo.

Si el cliente no cumple estas condiciones, la operación es rechazada y se conserva la asociación original de la membresía.

### Tipo de membresía

El tipo de membresía debe existir y encontrarse activo al momento del registro.

Los tipos desactivados no están disponibles para nuevas adquisiciones.

Durante la actualización, si se envía un nuevo `id_tipo_membresia`, el sistema comprueba que el tipo seleccionado exista y se encuentre activo.

Cuando se modifica el tipo de membresía, se actualizan los snapshots del nombre y la duración para conservar la información correspondiente a la nueva adquisición.

La comprobación de los registros relacionados se realiza nuevamente dentro de la transacción, evitando depender únicamente de la validación inicial del Form Request.

### Fecha de inicio

Durante el registro, la fecha de inicio es obligatoria y debe cumplir el formato `Y-m-d`.

No se permiten fechas posteriores al día actual.

Durante la actualización, la fecha de inicio es opcional. Cuando se envía, debe cumplir las reglas de validación establecidas para este campo.

Si cambia la fecha de inicio, el backend recalcula la fecha de vencimiento utilizando la duración almacenada en el snapshot del tipo de membresía actual, salvo que también se haya seleccionado un nuevo tipo de membresía.

### Fecha de vencimiento

La fecha de vencimiento no es proporcionada por el frontend.

El backend la calcula utilizando la fecha de inicio y la duración correspondiente al tipo de membresía. El día de inicio se cuenta como el primer día de la membresía y el vencimiento se establece a las 23:59:59 del último día.

Durante la actualización, el vencimiento se recalcula cuando cambia la fecha de inicio o el tipo de membresía.

### Valor pagado

El valor pagado es obligatorio durante el registro, debe ser numérico y mayor que cero.

Durante la actualización es opcional, pero cuando se envía debe cumplir las mismas restricciones.

La base de datos almacena el valor con dos posiciones decimales.

### Método de pago

El método de pago es obligatorio durante el registro y opcional durante la actualización.

Cuando se envía, debe corresponder a una de las siguientes opciones:

- `efectivo`
- `nequi`
- `bancolombia`
- `otro`

No se permiten valores diferentes a los definidos.

### Motivo de modificación

Toda solicitud de actualización debe incluir el campo `motivo`.

Este campo debe contener texto y no puede superar los 255 caracteres.

El motivo permite identificar la razón por la cual se realizó la modificación y se almacena en el registro general de auditoría.

No se permite realizar una actualización sin motivo.

### Validación de cambios reales

La actualización debe incluir al menos uno de los campos modificables.

Además, el sistema compara los valores recibidos con los almacenados para identificar los cambios efectivos.

Si los datos enviados no producen ninguna modificación real, la operación se rechaza y no se genera un registro de auditoría.

### Snapshots e integridad histórica

Al registrar una membresía adquirida, el sistema almacena los siguientes valores del tipo de membresía:

- `nombre_tipo_snapshot`: nombre que tenía el tipo de membresía al momento de la adquisición.
- `duracion_dias_snapshot`: duración que tenía el tipo de membresía al momento de la adquisición.

Estos valores se almacenan directamente en la tabla `membresia_adquirida`, por lo que no dependen de los valores actuales del tipo de membresía.

Si posteriormente se modifica el nombre o la duración del tipo de membresía, los snapshots de las adquisiciones anteriores permanecen intactos.

De igual manera, cuando un tipo de membresía se desactiva, las adquisiciones existentes no se eliminan ni se modifican.

Cuando se actualiza el tipo de membresía asociado a una adquisición, los snapshots se reemplazan por los valores correspondientes al nuevo tipo seleccionado.

### Auditoría de modificaciones

Cada actualización que produce cambios reales genera un registro de auditoría.

La auditoría se organiza mediante dos tablas:

**`registro_modificaciones`**

Almacena la información general de la modificación:

- `id_modificacion`: identificador del registro.
- `id_membresia_adquirida`: membresía adquirida modificada.
- `id_usuario`: usuario administrativo responsable.
- `fecha_hora`: fecha y hora de la modificación.
- `motivo`: razón de la modificación.

**`detalle_modificacion`**

Almacena los cambios realizados en cada campo:

- `id_detalle`: identificador del detalle.
- `id_modificacion`: registro general al que pertenece.
- `campo_modificado`: nombre del campo modificado.
- `valor_anterior`: valor que tenía el campo antes de la actualización.
- `valor_nuevo`: valor que tiene después de la actualización.

El sistema registra únicamente los campos que presentaron cambios reales.

La información de auditoría permite identificar quién realizó la modificación, cuándo se realizó, por qué se realizó y qué valores fueron modificados.

### Integridad transaccional

Las operaciones de registro y actualización utilizan transacciones de base de datos.

En el caso de la actualización, el registro de la membresía y la generación de los registros de auditoría forman parte de una misma transacción.

Si ocurre una excepción durante el proceso, la operación se revierte para evitar que la membresía quede actualizada sin su correspondiente registro de auditoría o que se almacene una auditoría incompleta.

La actualización también utiliza `lockForUpdate()` sobre la membresía adquirida para proteger el registro mientras se ejecuta la operación.

### Integridad referencial

La tabla `membresia_adquirida` establece relaciones mediante claves foráneas con:

- `cliente`
- `tipo_membresia`
- `usuario_administrativo`

Las restricciones `RESTRICT ON DELETE` y `RESTRICT ON UPDATE` protegen la integridad de los registros históricos, impidiendo eliminar o modificar las claves referenciadas mientras existan adquisiciones asociadas.

Además, la tabla incluye restricciones `CHECK` para garantizar que:

- La duración almacenada en el snapshot sea mayor que cero.
- El valor pagado sea mayor que cero.
- La fecha de vencimiento corresponda al cálculo realizado a partir de la fecha de inicio y la duración almacenada.

Las tablas de auditoría también establecen las relaciones necesarias para conservar la asociación entre la modificación, la membresía adquirida y sus detalles.

### Control de concurrencia

El registro de una membresía adquirida y la desactivación de un tipo de membresía pueden ejecutarse desde procesos independientes.

Ambas operaciones utilizan transacciones y `lockForUpdate()` sobre el mismo registro de `tipo_membresia`.

Esto permite serializar las operaciones y mantener la consistencia de los datos.

Los escenarios posibles son:

- **El registro de la adquisición obtiene primero el bloqueo:** la membresía adquirida puede registrarse correctamente y, posteriormente, el tipo puede desactivarse.
- **La desactivación obtiene primero el bloqueo:** la adquisición espera a que termine la operación y, al continuar, comprueba que el tipo ya está inactivo. En consecuencia, el registro es rechazado.

En ambos casos se conserva la integridad de la información y no se permite registrar una nueva adquisición utilizando un tipo que ya se encuentra desactivado.

### Protección de rutas

Las operaciones están protegidas mediante autenticación con Sanctum y verificación del estado de la cuenta administrativa.

La autorización por rol se aplica en el backend mediante los Form Requests correspondientes.

El registro está permitido para usuarios `dueno` y `empleado`, mientras que la actualización está restringida al rol `dueno`.

El frontend no constituye el mecanismo de seguridad: las restricciones se aplican en el servidor.

## 6. Rutas API

Las rutas relacionadas con el registro y la actualización de membresías adquiridas se encuentran en:

`routes/api.php`

Actualmente se encuentran implementadas:

| Método | Endpoint | Nombre | Operación |
|---|---|---|---|
| POST | `/api/membresias-adquiridas` | `membresias-adquiridas.store` | Registrar una membresía adquirida. |
| PATCH | `/api/membresias-adquiridas/{membresiaAdquirida}` | `membresias-adquiridas.update` | Actualizar una membresía adquirida. |

Ambas rutas están protegidas mediante `auth:sanctum` y `usuario.activo`.

La autorización por rol se realiza mediante los respectivos Form Requests:

- `RegistrarMembresiaAdquiridaRequest`: permite el acceso a usuarios con rol `dueno` o `empleado`.
- `ActualizarMembresiaAdquiridaRequest`: permite el acceso exclusivamente a usuarios con rol `dueno`.

## 7. Pruebas automatizadas

### Pruebas de registro de membresías adquiridas

Archivo: `tests/Feature/MembresiaAdquirida/RegistrarMembresiaAdquiridaTest.php`

Se implementaron 25 pruebas mediante PHPUnit para verificar el comportamiento del registro.

**Escenarios comprobados:**

1. Un dueño puede registrar una membresía adquirida.
2. Un empleado puede registrar una membresía adquirida.
3. Un usuario no autenticado no puede registrar una membresía adquirida.
4. Un usuario inactivo no puede registrar una membresía adquirida.
5. No se puede registrar una membresía para un cliente inactivo.
6. No se puede registrar una membresía con un tipo de membresía inactivo.
7. No se puede registrar una membresía con un cliente inexistente.
8. No se puede registrar una membresía con un tipo de membresía inexistente.
9. No se permite registrar una membresía con fecha de inicio futura.
10. No se permite registrar una membresía con formato de fecha de inicio inválido.
11. No se permite registrar una membresía sin fecha de inicio.
12. No se permite registrar una membresía con valor pagado igual a cero.
13. No se permite registrar una membresía con valor pagado negativo.
14. No se permite registrar una membresía con valor pagado no numérico.
15. No se permite registrar una membresía sin valor pagado.
16. No se permite registrar una membresía con método de pago no permitido.
17. No se permite registrar una membresía sin método de pago.
18. Se comprueba la obligatoriedad de todos los datos del registro.
19. Los snapshots se conservan cuando se modifica el tipo de membresía.
20. Se permite registrar membresías con los métodos de pago `bancolombia` y `otro`.
21. Una membresía de un día vence el mismo día a las 23:59:59.
22. Se registra correctamente un valor pagado con decimales.
23. Se calcula correctamente el vencimiento al cambiar de mes.
24. Se calcula correctamente el vencimiento al cambiar de año.
25. Una membresía adquirida se conserva al desactivar su tipo de membresía.

### Pruebas de concurrencia

Archivo: `tests/Feature/MembresiaAdquirida/ConcurrenciaMembresiaAdquiridaTest.php`

Se implementó una prueba de concurrencia para verificar el comportamiento del registro de membresías adquiridas frente a la desactivación simultánea del tipo de membresía asociado.

**Escenario comprobado:**

- Se ejecutan procesos independientes para registrar una membresía adquirida y desactivar el tipo de membresía correspondiente.
- Se comprueba que la operación de desactivación pueda completarse y que el registro de la adquisición respete el estado del tipo de membresía.
- Se verifica el resultado de ambas operaciones y la consistencia de los registros almacenados.

El escenario utiliza procesos independientes y una base de datos MySQL para comprobar el comportamiento de los bloqueos transaccionales.

### Pruebas de actualización de membresías adquiridas

Archivo: `tests/Feature/MembresiaAdquirida/ActualizarMembresiaAdquiridaTest.php`

Se implementaron 19 pruebas mediante PHPUnit para verificar el comportamiento de la actualización de membresías adquiridas.

**Escenarios comprobados:**

1. Un dueño puede actualizar el valor pagado de una membresía adquirida.
2. Un dueño puede actualizar el cliente de una membresía adquirida.
3. Un dueño puede actualizar el tipo de membresía y sus snapshots.
4. Un dueño puede actualizar la fecha de inicio y recalcular el vencimiento.
5. Un dueño puede actualizar el método de pago de una membresía adquirida.
6. Un dueño puede actualizar varios campos de una membresía adquirida.
7. Un empleado no puede actualizar una membresía adquirida.
8. Un usuario no autenticado no puede actualizar una membresía adquirida.
9. No se puede actualizar una membresía sin campos modificables.
10. No se puede actualizar una membresía con un motivo vacío.
11. No se puede actualizar una membresía con un valor pagado negativo.
12. No se puede actualizar una membresía con un método de pago no permitido.
13. No se puede asignar un cliente inactivo a una membresía adquirida.
14. No se puede asignar un tipo de membresía inactivo.
15. No se puede actualizar una membresía con los mismos valores.
16. No se puede asignar un cliente inexistente.
17. No se puede asignar un tipo de membresía inexistente.
18. No se puede actualizar una membresía adquirida inexistente.
19. Se revierte la actualización si falla el registro de auditoría.

**Aspectos verificados:**

- Actualización individual y simultánea de los campos permitidos.
- Validación de permisos según el rol del usuario administrativo.
- Validación de clientes y tipos de membresía existentes y activos.
- Rechazo de solicitudes que no cumplen las reglas de validación.
- Rechazo de actualizaciones que no generan cambios reales.
- Recalculación de la fecha de vencimiento cuando corresponde.
- Actualización de snapshots cuando cambia el tipo de membresía.
- Registro de auditoría con los campos modificados, sus valores anteriores y nuevos, el motivo y el usuario responsable.
- Reversión de los cambios de la membresía y de la auditoría cuando ocurre una excepción durante la operación.

### Resultado de las pruebas

Última ejecución individual registrada para el registro:

```text
Tests: 25 passed (110 assertions)
```

Última ejecución individual registrada para la actualización:

```text
Tests: 19 passed (113 assertions)
```

La prueba de concurrencia se ejecuta mediante:

```bash
herd php artisan test --filter=ConcurrenciaMembresiaAdquiridaTest
```

Las pruebas de registro se ejecutan mediante:

```bash
herd php artisan test --filter=RegistrarMembresiaAdquiridaTest
```

Las pruebas de actualización se ejecutan mediante:

```bash
herd php artisan test --filter=ActualizarMembresiaAdquiridaTest
```

La suite completa del proyecto se ejecuta mediante:

```bash
herd php artisan test
```

## 8. Estado del módulo

El módulo de gestión de membresías adquiridas cuenta con el registro (RF14) y la actualización (RF15), implementados y validados mediante pruebas automatizadas.

La funcionalidad permite registrar adquisiciones para clientes activos, asociarlas con tipos de membresía disponibles y almacenar la información correspondiente al pago y al período de vigencia.

También permite actualizar los datos de una adquisición, recalcular su fecha de vencimiento cuando corresponde y conservar los snapshots del tipo de membresía asociado.

Las modificaciones realizadas quedan registradas mediante un sistema de auditoría que conserva el usuario administrativo responsable, el motivo y los valores anteriores y nuevos de cada campo modificado.

El cálculo de la fecha de vencimiento se realiza automáticamente en el backend, mientras que los snapshots permiten conservar la información histórica del tipo de membresía correspondiente a cada adquisición.

También se implementaron restricciones de integridad referencial, transacciones y control de concurrencia para proteger los registros existentes y mantener la consistencia de la información.