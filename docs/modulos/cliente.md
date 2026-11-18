# Módulo de gestión de clientes -- GymFlow

## 1. Objetivo y alcance

El módulo de gestión de clientes permite registrar los clientes del gimnasio, almacenando su información personal y de contacto, así como la autorización para el tratamiento de sus datos personales.

El registro contempla los nombres, apellidos, documento de identidad y teléfono del cliente. El sistema verifica que el documento no se encuentre registrado previamente y establece automáticamente el estado inicial del cliente como activo.

Una característica importante del módulo es la posibilidad de registrar la primera membresía del cliente durante el mismo proceso de creación. De esta manera, el usuario administrativo puede registrar al cliente y, opcionalmente, asociarle una membresía adquirida mediante una única solicitud HTTP.

El registro de la membresía es independiente de la creación del cliente. Si los datos de la membresía no cumplen las reglas de validación o se presenta un error durante su registro, el cliente permanece almacenado en la base de datos.

Las operaciones del módulo están disponibles para usuarios administrativos con rol Dueño o Empleado.

### Funcionalidades implementadas

- Registro de clientes por parte de usuarios administrativos.
- Registro de nombres, apellidos, documento y teléfono.
- Validación de unicidad del documento de identidad.
- Autorización obligatoria para el tratamiento de datos personales.
- Registro automático de la fecha de autorización por parte del backend.
- Asignación automática del estado activo al cliente.
- Registro opcional de la primera membresía durante la creación del cliente.
- Reutilización de las reglas de validación y la lógica de registro de membresías adquiridas.
- Conservación del cliente cuando los datos de la membresía son inválidos o su registro presenta un error.
- Validación de los datos mediante un Form Request.
- Protección de la operación mediante autenticación y verificación del estado de la cuenta.
- Transformación de la información del cliente mediante un Laravel Resource.
- Validación automatizada de los escenarios de registro y registro opcional de membresía.

## 2. Tecnologías utilizadas

| Tecnología | Responsabilidad |
|---|---|
| Laravel 13 | Backend y gestión de solicitudes. |
| Laravel Sanctum | Autenticación de solicitudes API. |
| MySQL 8 | Almacenamiento de clientes y relaciones. |
| PHPUnit | Pruebas automatizadas. |
| Laravel Validation | Validación de los datos recibidos. |
| Laravel Eloquent | Persistencia de información mediante modelos. |

## 3. Componentes y responsabilidades

### Cliente

Archivo: `app/Models/Cliente.php`

Representa el modelo asociado a la tabla `cliente`.

El modelo permite gestionar la información de los clientes y su relación con las membresías adquiridas.

### RegistrarClienteRequest

Archivo: `app/Http/Requests/Cliente/RegistrarClienteRequest.php`

Valida y autoriza las solicitudes de registro de clientes.

**Autorización:**

Permite realizar la operación a usuarios autenticados con rol `dueno` o `empleado`.

**Campos validados:**

- `nombres`: obligatorio, de tipo texto y con un máximo de 100 caracteres.
- `apellidos`: obligatorio, de tipo texto y con un máximo de 100 caracteres.
- `documento`: obligatorio, de tipo texto, con un máximo de 30 caracteres y único en la tabla `cliente`.
- `telefono`: obligatorio, de tipo texto y con un máximo de 20 caracteres.
- `autorizacion_tratamiento`: obligatorio y debe contener una aceptación válida del tratamiento de datos personales.
- `registrar_membresia`: opcional y debe ser un valor booleano. Indica si se desea registrar la primera membresía durante la creación del cliente.

El Form Request valida exclusivamente los datos correspondientes al cliente y el indicador opcional de registro de membresía.

Los datos de la membresía no se validan en este componente, ya que el cliente debe poder registrarse independientemente del resultado de esa operación.

También proporciona mensajes de validación personalizados en español y atributos descriptivos para los errores.

### RegistrarCliente

Archivo: `app/Actions/Cliente/RegistrarCliente.php`

Centraliza la operación de registro de clientes.

Responsabilidades:

1. Recibir los datos previamente validados.
2. Crear el cliente mediante Eloquent.
3. Asignar automáticamente el estado inicial activo.
4. Registrar la autorización para el tratamiento de datos personales.
5. Generar la fecha de autorización mediante `now()`.
6. Devolver la instancia del cliente registrado.

La fecha de autorización se genera en el backend, por lo que no puede ser establecida arbitrariamente desde el frontend.

La acción no registra membresías adquiridas. Esta operación se ejecuta posteriormente desde el controlador cuando se solicita el registro opcional de la primera membresía.

### ClienteController

Archivo: `app/Http/Controllers/ClienteController.php`

Coordina las solicitudes HTTP relacionadas con el registro de clientes.

Implementa el método `store()`.

**Responsabilidades de `store()`:**

1. Recibir la solicitud mediante `RegistrarClienteRequest`.
2. Obtener los datos previamente validados.
3. Ejecutar la acción `RegistrarCliente`.
4. Transformar la información del cliente mediante `ClienteResource`.
5. Comprobar si se solicitó el registro de la primera membresía.
6. Cuando corresponde, validar los datos de la membresía mediante `Validator::make()` y las reglas proporcionadas por `ReglasMembresiaAdquirida`.
7. Incorporar el identificador del cliente recién creado a los datos validados de la membresía.
8. Ejecutar la acción `RegistrarMembresiaAdquirida` para realizar el registro.
9. Devolver una respuesta JSON con el resultado de las operaciones.

La creación del cliente y el registro de la membresía se manejan como operaciones independientes. Esto permite conservar el cliente aunque la segunda operación no pueda completarse.

Los errores producidos durante el registro de la membresía se reportan mediante el mecanismo de excepciones de Laravel, sin exponer detalles internos al frontend.

### ClienteResource

Archivo: `app/Http/Resources/ClienteResource.php`

Transforma el modelo `Cliente` en una respuesta JSON controlada.

Expone los datos correspondientes a:

- `id_cliente`
- `nombres`
- `apellidos`
- `documento`
- `telefono`
- `activo`
- `autorizacion_tratamiento`
- `fecha_autorizacion`

La fecha de autorización se presenta con el formato `Y-m-d H:i:s`.

No se expone información interna adicional del modelo, como los campos de marcas de tiempo de Eloquent.

### ReglasMembresiaAdquirida

Archivo: `app/Rules/ReglasMembresiaAdquirida.php`

Centraliza las reglas de validación correspondientes a los datos de una membresía adquirida.

La clase proporciona tres métodos estáticos:

- `rules()`: devuelve las reglas de validación de los campos de la membresía.
- `messages()`: proporciona los mensajes personalizados para los errores de validación.
- `attributes()`: define los nombres descriptivos de los atributos.

Esta clase permite reutilizar las mismas reglas de validación tanto en `RegistrarMembresiaAdquiridaRequest` como en el proceso de registro de clientes con su primera membresía.

De esta manera, se evita duplicar las reglas y se mantiene la consistencia de las validaciones entre ambos módulos.

## 4. Flujos de operación

El registro de clientes se realiza mediante una solicitud HTTP `POST`.

### Endpoint

`POST /api/clientes`

**Middlewares aplicados:**

- `auth:sanctum`: requiere autenticación.
- `usuario.activo`: impide el acceso a cuentas desactivadas.

La autorización por rol se realiza mediante `RegistrarClienteRequest`, que permite únicamente a usuarios con rol `dueno` o `empleado`.

**Nombre de ruta:**

`cliente.store`

### Proceso de registro

El proceso sigue este orden:

1. El usuario administrativo accede a la funcionalidad de registro de clientes.
2. El frontend envía una solicitud `POST /api/clientes` con los datos personales y de contacto, junto con la autorización para el tratamiento de datos personales.
3. Laravel comprueba la autenticación mediante `auth:sanctum`.
4. El middleware `VerificarUsuarioActivo` comprueba que la cuenta del usuario continúe activa.
5. `RegistrarClienteRequest` autoriza la operación y valida los datos recibidos.
6. `ClienteController` obtiene los datos previamente validados.
7. El controlador ejecuta la acción `RegistrarCliente`.
8. La acción crea el cliente, establece su estado inicial activo y registra la fecha de autorización.
9. `ClienteResource` transforma el modelo registrado.
10. El controlador comprueba si se solicitó registrar una primera membresía.
11. Si no se solicitó una membresía, el backend devuelve HTTP `201 Created` con los datos del cliente.
12. Si se solicitó una membresía, el controlador valida sus datos y ejecuta la acción `RegistrarMembresiaAdquirida` cuando son válidos.
13. El backend devuelve HTTP `201 Created` con el resultado del registro del cliente y, cuando corresponde, los datos de la membresía adquirida.

### Registro opcional de la primera membresía

El registro de la primera membresía se realiza desde la misma solicitud utilizada para crear el cliente.

Para activar esta funcionalidad, el frontend debe enviar el campo `registrar_membresia` con valor `true` e incluir el objeto `membresia` con los datos correspondientes.

**Ejemplo de solicitud:**

```json
{
    "nombres": "Carlos",
    "apellidos": "Gomez",
    "documento": "1234567890",
    "telefono": "3001234567",
    "autorizacion_tratamiento": true,
    "registrar_membresia": true,
    "membresia": {
        "id_tipo_membresia": 1,
        "fecha_inicio": "2026-11-17",
        "valor_pagado": 80000,
        "metodo_pago": "efectivo"
    }
}
```

Los valores son ilustrativos.

El identificador del cliente no se envía dentro del objeto `membresia`, ya que el backend lo obtiene automáticamente a partir del cliente recién creado.

La validación de los datos de la membresía se realiza después de crear el cliente, utilizando `Validator::make()` y las reglas proporcionadas por `ReglasMembresiaAdquirida`.

Cuando los datos son válidos, se incorpora el identificador del cliente y se ejecuta la acción `RegistrarMembresiaAdquirida`.

Esta acción reutiliza la lógica implementada en RF14, incluyendo el cálculo de la fecha de vencimiento, la conservación de snapshots y el control de concurrencia.

### Respuesta exitosa sin membresía

Cuando no se solicita registrar una primera membresía, la API devuelve una respuesta JSON con la siguiente estructura:

```json
{
    "mensaje": "Cliente registrado correctamente.",
    "cliente": {
        "id_cliente": 1,
        "nombres": "Carlos",
        "apellidos": "Gomez",
        "documento": "1234567890",
        "telefono": "3001234567",
        "activo": true,
        "autorizacion_tratamiento": true,
        "fecha_autorizacion": "2026-11-17 10:30:00"
    }
}
```

Los valores son ilustrativos.

### Respuesta exitosa con membresía

Cuando se registra correctamente el cliente y su primera membresía, la API devuelve HTTP `201 Created` con la siguiente estructura:

```json
{
    "mensaje": "Cliente registrado y membresía adquirida correctamente.",
    "cliente": {
        "id_cliente": 1,
        "nombres": "Carlos",
        "apellidos": "Gomez",
        "documento": "1234567890",
        "telefono": "3001234567",
        "activo": true,
        "autorizacion_tratamiento": true,
        "fecha_autorizacion": "2026-11-17 10:30:00"
    },
    "membresia_adquirida": {
        "id_membresia_adquirida": 1,
        "id_cliente": 1,
        "id_tipo_membresia": 1,
        "id_usuario_registro": 1,
        "nombre_tipo_snapshot": "Mensual",
        "duracion_dias_snapshot": 30,
        "fecha_inicio": "2026-11-17",
        "fecha_vencimiento": "2026-12-16 23:59:59",
        "valor_pagado": "80000.00",
        "metodo_pago": "efectivo"
    }
}
```

Los valores son ilustrativos.

### Respuesta cuando la membresía no puede registrarse

Si el cliente se crea correctamente, pero los datos de la membresía no cumplen las reglas de validación, el backend conserva el cliente y devuelve HTTP `201 Created`.

La respuesta incluye el recurso del cliente, el indicador `membresia_registrada` con valor `false` y los errores correspondientes a la membresía.

**Ejemplo de respuesta:**

```json
{
    "mensaje": "Cliente registrado, pero no fue posible registrar la membresía.",
    "cliente": {
        "id_cliente": 1,
        "nombres": "Carlos",
        "apellidos": "Gomez",
        "documento": "1234567890",
        "telefono": "3001234567",
        "activo": true,
        "autorizacion_tratamiento": true,
        "fecha_autorizacion": "2026-11-17 10:30:00"
    },
    "membresia_registrada": false,
    "errores": {
        "fecha_inicio": [
            "La fecha de inicio no puede ser posterior a la fecha actual."
        ]
    }
}
```

Los valores son ilustrativos.

Si se produce una excepción durante el registro de la membresía, el controlador también conserva el cliente y reporta el error mediante Laravel. La respuesta comunica que no se pudo completar el registro de la membresía, sin exponer información interna de la excepción.

### Respuestas de error

| Código HTTP | Situación |
|---|---|
| `401 Unauthorized` | El usuario no está autenticado. |
| `403 Forbidden` | La cuenta del usuario está desactivada o no tiene autorización para realizar la operación. |
| `422 Unprocessable Entity` | Los datos principales del cliente incumplen las reglas de validación. |
| `201 Created` | El cliente se registra correctamente, pero los datos de la membresía opcional son inválidos o su registro no pudo completarse. |

## 5. Reglas de validación y seguridad

### Autorización por roles

El registro de clientes está permitido a los usuarios con rol `dueno` y `empleado`.

La autorización se comprueba mediante `RegistrarClienteRequest`.

### Nombres y apellidos

Los nombres y apellidos son obligatorios y deben ser valores de tipo texto.

Cada campo admite un máximo de 100 caracteres.

### Documento

El documento es obligatorio, debe ser de tipo texto y admite un máximo de 30 caracteres.

Su valor debe ser único dentro de la tabla `cliente`.

No se permite registrar dos clientes con el mismo documento.

### Teléfono

El teléfono es obligatorio, debe ser de tipo texto y admite un máximo de 20 caracteres.

### Autorización para el tratamiento de datos personales

La autorización para el tratamiento de datos personales es obligatoria.

El campo `autorizacion_tratamiento` debe contener un valor aceptado por la regla `accepted` de Laravel.

No se permite registrar un cliente sin esta autorización.

La fecha en que se concede la autorización se genera automáticamente mediante `now()` en el backend, por lo que el frontend no puede establecerla directamente.

### Estado inicial del cliente

Los clientes se registran inicialmente activos.

El estado se establece automáticamente en la acción `RegistrarCliente`, independientemente de los datos adicionales que puedan enviarse desde el frontend.

### Registro opcional de membresía

El campo `registrar_membresia` es opcional y debe contener un valor booleano válido.

Cuando se omite o contiene `false`, el sistema registra únicamente el cliente.

Cuando contiene `true`, el controlador intenta validar y registrar la membresía incluida en el objeto `membresia`.

La ausencia o invalidez de los datos de membresía no impide la creación del cliente.

### Validación de la membresía adquirida

Los datos de la primera membresía utilizan las reglas centralizadas en `ReglasMembresiaAdquirida`.

Las validaciones contemplan:

- `id_tipo_membresia`: obligatorio, de tipo entero y debe corresponder a un tipo de membresía existente y activo.
- `fecha_inicio`: obligatoria, debe cumplir el formato `Y-m-d` y no puede ser posterior a la fecha actual.
- `valor_pagado`: obligatorio, de tipo numérico y mayor que cero.
- `metodo_pago`: obligatorio, de tipo texto y debe pertenecer a las opciones permitidas: `efectivo`, `nequi`, `bancolombia` u `otro`.

El identificador del cliente se obtiene directamente del registro creado y se incorpora desde el backend.

### Integridad de las operaciones

La creación del cliente y el registro de la primera membresía no se ejecutan dentro de una única transacción que revierta ambas operaciones.

Esto permite que un error en el registro de la membresía no elimine un cliente que ya fue creado correctamente.

La acción `RegistrarMembresiaAdquirida` conserva su propia lógica transaccional, incluyendo las comprobaciones y los bloqueos necesarios para mantener la integridad de la adquisición.

### Protección de rutas

La operación está protegida mediante autenticación con Sanctum y verificación del estado de la cuenta administrativa.

La autorización por rol se aplica en el backend mediante `RegistrarClienteRequest`.

El frontend no constituye el mecanismo de seguridad: las restricciones se aplican en el servidor.

### Protección de información personal

El recurso `ClienteResource` controla los atributos que se exponen en las respuestas JSON.

La información se presenta mediante los campos definidos para el módulo, evitando exponer atributos internos adicionales del modelo.

## 6. Rutas API

Las rutas relacionadas con el registro de clientes se encuentran en:

`routes/api.php`

Actualmente se encuentra implementada:

| Método | Endpoint | Nombre | Operación |
|---|---|---|---|
| POST | `/api/clientes` | `cliente.store` | Registrar un cliente y, opcionalmente, su primera membresía. |

La ruta está protegida mediante `auth:sanctum` y `usuario.activo`. La autorización por rol se realiza mediante `RegistrarClienteRequest`, que permite el acceso a usuarios con rol `dueno` o `empleado`.

## 7. Pruebas automatizadas

### Pruebas de registro de clientes

Archivo: `tests/Feature/Cliente/RegistrarClienteTest.php`

Se implementaron 23 pruebas mediante PHPUnit para verificar el comportamiento del registro de clientes y su integración con el registro opcional de membresías adquiridas.

**Escenarios comprobados:**

1. Un dueño puede registrar un cliente.
2. Un empleado puede registrar un cliente.
3. Un usuario no autenticado no puede registrar un cliente.
4. Un usuario inactivo no puede registrar un cliente.
5. No se puede registrar un cliente sin los campos obligatorios.
6. No se puede registrar un cliente con campos que superan la longitud máxima.
7. No se puede registrar un cliente con tipos de datos incorrectos.
8. No se puede registrar un cliente con un documento ya existente.
9. No se puede registrar un cliente sin autorizar el tratamiento de datos personales.
10. No se puede registrar un cliente con un valor inválido en el indicador de registro de membresía.
11. Se puede registrar un cliente con su primera membresía en una sola solicitud.
12. Se registra el cliente aunque los datos de la membresía sean inválidos.
13. Se puede registrar un cliente sin registrar una membresía.
14. Se registra el cliente aunque el tipo de membresía no exista.
15. Se registra el cliente aunque el tipo de membresía se encuentre inactivo.
16. Se registra el cliente aunque la fecha de inicio de la membresía sea futura.
17. Se registra el cliente aunque la fecha de inicio de la membresía tenga un formato inválido.
18. Se registra el cliente aunque el valor pagado de la membresía sea cero.
19. Se registra el cliente aunque el identificador del tipo de membresía no sea entero.
20. Se registra el cliente aunque el valor pagado de la membresía sea negativo.
21. Se registra el cliente aunque el valor pagado de la membresía no sea numérico.
22. Se registra el cliente aunque el método de pago de la membresía no sea válido.
23. Se registra el cliente aunque los datos de la membresía no sean un arreglo.

### Resultado de las pruebas

Última ejecución individual registrada para el módulo:

```text
Tests: 23 passed (184 assertions)
```

Las pruebas de registro de clientes se ejecutan mediante:

```bash
herd php artisan test --filter=RegistrarClienteTest
```

La suite completa del proyecto se ejecuta mediante:

```bash
herd php artisan test
```

## 8. Estado del módulo

El módulo de gestión de clientes cuenta con el registro (RF7), implementado y validado mediante pruebas automatizadas.

La funcionalidad permite registrar clientes con sus datos personales y de contacto, exigir la autorización para el tratamiento de datos personales y establecer automáticamente su estado inicial activo.

También permite registrar opcionalmente la primera membresía del cliente durante la misma solicitud, reutilizando las reglas de validación y la lógica de negocio del módulo de membresías adquiridas.

La separación entre ambas operaciones garantiza que un error durante el registro de la membresía no impida conservar el cliente registrado.

El módulo se encuentra preparado para continuar con los siguientes requerimientos funcionales relacionados con la consulta y gestión de clientes.