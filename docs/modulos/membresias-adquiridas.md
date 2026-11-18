# Módulo de gestión de membresías adquiridas -- GymFlow

## 1. Objetivo y alcance

El módulo de gestión de membresías adquiridas permite registrar las membresías que los clientes adquieren en el gimnasio, asociando cada registro con un cliente, un tipo de membresía y el usuario administrativo responsable de realizar la operación.

El registro contempla la selección de un cliente activo, un tipo de membresía disponible, la fecha de inicio, el valor pagado y el método de pago utilizado. A partir de la duración del tipo de membresía seleccionado, el sistema calcula automáticamente la fecha de vencimiento.

Una característica importante del módulo es la conservación de la información histórica. Al registrar una membresía adquirida, se almacenan snapshots del nombre y la duración del tipo de membresía seleccionado. De esta manera, las modificaciones posteriores realizadas sobre el tipo original no alteran la información de la adquisición.

Las operaciones del módulo están disponibles para usuarios administrativos con rol Dueño o Empleado. Además, el sistema registra automáticamente el usuario administrativo responsable de la operación.

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
- Validación de los datos mediante un Form Request.
- Protección de la operación mediante autenticación y verificación del estado de la cuenta.
- Ejecución de la operación mediante una transacción de base de datos.
- Control de concurrencia entre el registro de una adquisición y la desactivación de su tipo de membresía.
- Validación automatizada de los escenarios de registro y concurrencia.

## 2. Tecnologías utilizadas

| Tecnología | Responsabilidad |
|---|---|
| Laravel 13 | Backend y gestión de solicitudes. |
| Laravel Sanctum | Autenticación de solicitudes API. |
| MySQL 8 | Almacenamiento de membresías adquiridas y relaciones. |
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

### MembresiaAdquiridaController

Archivo: `app/Http/Controllers/MembresiaAdquiridaController.php`

Coordina las solicitudes HTTP relacionadas con el registro de membresías adquiridas.

Implementa el método `store()`.

**Responsabilidades de `store()`:**

1. Recibir la solicitud mediante `RegistrarMembresiaAdquiridaRequest`.
2. Obtener los datos previamente validados.
3. Ejecutar la acción `RegistrarMembresiaAdquirida`.
4. Transformar el resultado mediante `MembresiaAdquiridaResource`.
5. Devolver una respuesta JSON con HTTP `201 Created`, un mensaje de confirmación y los datos de la membresía adquirida.

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

## 4. Flujos de operación

El registro de membresías adquiridas se realiza mediante una solicitud HTTP `POST`.

### Endpoint

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

El cálculo se realiza en el backend, por lo que el frontend no puede establecer arbitrariamente la fecha de vencimiento.

### Respuesta exitosa

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
| `422 Unprocessable Entity` | Los datos enviados incumplen las reglas de validación o el tipo de membresía no está disponible. |

## 5. Reglas de validación y seguridad

### Autorización por roles

El registro de membresías adquiridas está permitido a los usuarios con rol `dueno` y `empleado`.

La autorización se comprueba mediante `RegistrarMembresiaAdquiridaRequest`.

### Cliente

El cliente debe existir en la base de datos y encontrarse activo.

No se permite registrar una membresía adquirida para un cliente inexistente o inactivo.

### Tipo de membresía

El tipo de membresía debe existir y encontrarse activo al momento del registro.

Los tipos desactivados no están disponibles para nuevas adquisiciones.

La comprobación del estado se realiza nuevamente dentro de la transacción, después de obtener el bloqueo del registro. Esto evita depender únicamente de la validación inicial del Form Request cuando existen operaciones concurrentes.

### Fecha de inicio

La fecha de inicio es obligatoria y debe cumplir el formato `Y-m-d`.

No se permiten fechas posteriores al día actual.

### Fecha de vencimiento

La fecha de vencimiento no es proporcionada por el frontend.

El backend la calcula utilizando la fecha de inicio y la duración del tipo de membresía seleccionado. El día de inicio se cuenta como el primer día de la membresía y el vencimiento se establece a las 23:59:59 del último día.

### Valor pagado

El valor pagado es obligatorio, debe ser numérico y mayor que cero.

La base de datos almacena el valor con dos posiciones decimales.

### Método de pago

El método de pago es obligatorio y debe corresponder a una de las siguientes opciones:

- `efectivo`
- `nequi`
- `bancolombia`
- `otro`

No se permiten valores diferentes a los definidos.

### Snapshots e integridad histórica

Al registrar una membresía adquirida, el sistema almacena los siguientes valores del tipo de membresía:

- `nombre_tipo_snapshot`: nombre que tenía el tipo de membresía al momento de la adquisición.
- `duracion_dias_snapshot`: duración que tenía el tipo de membresía al momento de la adquisición.

Estos valores se almacenan directamente en la tabla `membresia_adquirida`, por lo que no dependen de los valores actuales del tipo de membresía.

Si posteriormente se modifica el nombre o la duración del tipo de membresía, los snapshots de las adquisiciones anteriores permanecen intactos.

De igual manera, cuando un tipo de membresía se desactiva, las adquisiciones existentes no se eliminan ni se modifican.

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

### Control de concurrencia

El registro de una membresía adquirida y la desactivación de un tipo de membresía pueden ejecutarse desde procesos independientes.

Ambas operaciones utilizan transacciones y `lockForUpdate()` sobre el mismo registro de `tipo_membresia`.

Esto permite serializar las operaciones y mantener la consistencia de los datos.

Los escenarios posibles son:

- **El registro de la adquisición obtiene primero el bloqueo:** la membresía adquirida puede registrarse correctamente y, posteriormente, el tipo puede desactivarse.
- **La desactivación obtiene primero el bloqueo:** la adquisición espera a que termine la operación y, al continuar, comprueba que el tipo ya está inactivo. En consecuencia, el registro es rechazado.

En ambos casos se conserva la integridad de la información y no se permite registrar una nueva adquisición utilizando un tipo que ya se encuentra desactivado.

### Protección de rutas

La operación está protegida mediante autenticación con Sanctum y verificación del estado de la cuenta administrativa.

La autorización por rol se aplica en el backend mediante el Form Request correspondiente.

El frontend no constituye el mecanismo de seguridad: las restricciones se aplican en el servidor.

## 6. Rutas API

Las rutas relacionadas con el registro de membresías adquiridas se encuentran en:

`routes/api.php`

Actualmente se encuentra implementada:

| Método | Endpoint | Nombre | Operación |
|---|---|---|---|
| POST | `/api/membresias-adquiridas` | `membresias-adquiridas.store` | Registrar una membresía adquirida. |

La ruta está protegida mediante `auth:sanctum` y `usuario.activo`. La autorización por rol se realiza mediante `RegistrarMembresiaAdquiridaRequest`, que permite el acceso a usuarios con rol `dueno` o `empleado`.

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

### Resultado de las pruebas

Última ejecución individual registrada para el módulo:

```text
Tests: 25 passed (110 assertions)
```

La prueba de concurrencia se ejecuta mediante:

```bash
herd php artisan test --filter=ConcurrenciaMembresiaAdquiridaTest
```

Las pruebas de registro se ejecutan mediante:

```bash
herd php artisan test --filter=RegistrarMembresiaAdquiridaTest
```

La suite completa del proyecto se ejecuta mediante:

```bash
herd php artisan test
```

## 8. Estado del módulo

El módulo de gestión de membresías adquiridas cuenta con el registro (RF14), implementado y validado mediante pruebas automatizadas.

La funcionalidad permite registrar adquisiciones para clientes activos, asociarlas con tipos de membresía disponibles y almacenar la información correspondiente al pago y al período de vigencia.

El cálculo de la fecha de vencimiento se realiza automáticamente en el backend, mientras que los snapshots permiten conservar la información histórica del tipo de membresía al momento de la adquisición.

También se implementaron restricciones de integridad referencial y control de concurrencia para proteger los registros existentes y evitar adquisiciones sobre tipos que hayan sido desactivados.

El módulo queda preparado para continuar con los siguientes requerimientos relacionados con la gestión de clientes y el seguimiento de sus membresías.