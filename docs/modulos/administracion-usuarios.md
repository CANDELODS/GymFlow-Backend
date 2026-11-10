# Módulo de administración de usuarios administrativos -- GymFlow

## 1. Objetivo y alcance

El módulo de administración de usuarios administrativos permite gestionar las cuentas de acceso al sistema GymFlow.

Actualmente, contempla el registro, la consulta, la actualización de información y la gestión del estado de las cuentas administrativas.

El registro permite asignar uno de los roles disponibles y establecer el estado inicial de la cuenta. La consulta permite a los usuarios con rol Dueño visualizar las cuentas registradas, buscar por nombre de usuario y consultar los resultados mediante paginación. La actualización permite modificar el nombre de usuario, el rol y, opcionalmente, la contraseña. La gestión del estado permite activar o desactivar cuentas, aplicando restricciones para proteger el acceso administrativo al sistema.

El sistema contempla dos roles administrativos:

- **Dueño (`dueno`):** usuario con permisos administrativos superiores.
- **Empleado (`empleado`):** usuario con permisos operativos según las restricciones del sistema.

El registro, la consulta, la actualización y la gestión del estado de las cuentas están restringidos exclusivamente a usuarios autenticados con el rol `dueno`.

### Funcionalidades implementadas

- Registro de nuevos usuarios administrativos.
- Asignación de roles durante el registro.
- Selección del estado inicial de la cuenta (activo o inactivo).
- Validación de unicidad del nombre de usuario.
- Validación de la política de contraseñas.
- Protección de las operaciones mediante autenticación y autorización.
- Consulta de usuarios administrativos.
- Búsqueda por coincidencias parciales del nombre de usuario.
- Paginación de cinco registros por página.
- Conservación de los filtros de búsqueda al cambiar de página.
- Consulta del rol y estado de cada cuenta administrativa.
- Exclusión de información sensible en las respuestas de la API.
- Actualización del nombre de usuario y del rol de cuentas administrativas.
- Actualización opcional de la contraseña, conservando la actual cuando no se proporciona una nueva.
- Activación y desactivación de cuentas administrativas.
- Protección para impedir la desactivación de la propia cuenta.
- Protección para garantizar que siempre exista al menos un dueño activo.
- Bloqueo de acceso a rutas protegidas e invalidación de sesión cuando una cuenta es desactivada.
- Protección de las operaciones concurrentes que podrían dejar el sistema sin dueños activos.

## 2. Tecnologías utilizadas

| Tecnología | Responsabilidad |
|---|---|
| Laravel 13 | Backend y gestión de solicitudes. |
| Laravel Sanctum | Autenticación de solicitudes API. |
| MySQL 8 | Almacenamiento de usuarios administrativos. |
| PHPUnit | Pruebas automatizadas. |

## 3. Componentes y responsabilidades

### RegistrarUsuarioAdministrativoRequest

Archivo: `app/Http/Requests/UsuarioAdministrativo/RegistrarUsuarioAdministrativoRequest.php`

Valida los datos enviados para registrar un nuevo usuario administrativo.

**Autorización:**

Comprueba que el usuario autenticado tenga el rol `dueno`.

**Campos validados:**

- `usuario`: obligatorio, de tipo texto, entre 5 y 100 caracteres y único en la tabla `usuario_administrativo`.
- `password`: obligatoria, de tipo texto, con un máximo de 255 caracteres y sujeta a la política de seguridad.
- `password_confirmation`: obligatoria, de tipo texto y debe coincidir con la contraseña.
- `rol`: obligatorio y limitado a los valores `dueno` y `empleado`.
- `activo`: obligatorio y de tipo booleano.

También proporciona mensajes de validación personalizados en español.

### PoliticaContrasena

Archivo: `app/Rules/PoliticaContrasena.php`

Implementa las condiciones de seguridad definidas para las nuevas contraseñas:

- Mínimo 8 caracteres.
- Al menos un número.
- Al menos un carácter especial.

Esta regla se reutiliza en el registro de usuarios administrativos, evitando duplicar la lógica de validación implementada para el cambio de contraseña.

### RegistrarUsuarioAdministrativo

Archivo: `app/Actions/UsuarioAdministrativo/RegistrarUsuarioAdministrativo.php`

Centraliza la operación de registro de usuarios administrativos.

Responsabilidades:

1. Recibir los datos previamente validados.
2. Seleccionar explícitamente los atributos que serán almacenados.
3. Crear el usuario administrativo mediante Eloquent.
4. Devolver la instancia del usuario creado.

Los campos persistidos son:

- `usuario`
- `password`
- `rol`
- `activo`

La confirmación de contraseña no se almacena.

El hashing de la contraseña es realizado automáticamente mediante el cast `hashed` definido en el modelo `UsuarioAdministrativo`.

### ConsultarUsuariosAdministrativosRequest

Archivo: `app/Http/Requests/UsuarioAdministrativo/ConsultarUsuariosAdministrativosRequest.php`

Valida y autoriza las solicitudes de consulta de usuarios administrativos.

**Autorización:**

Comprueba que el usuario autenticado tenga el rol `dueno`.

**Parámetros validados:**

- `usuario`: opcional, de tipo texto y con un máximo de 100 caracteres.
- `page`: opcional, debe ser un número entero mayor o igual a 1.

También proporciona mensajes de validación personalizados en español.

### ConsultarUsuariosAdministrativos

Archivo: `app/Actions/UsuarioAdministrativo/ConsultarUsuariosAdministrativos.php`

Centraliza la lógica de consulta de usuarios administrativos.

Responsabilidades:

1. Inicializar una consulta sobre el modelo `UsuarioAdministrativo`.
2. Aplicar el filtro por nombre de usuario cuando se proporciona.
3. Buscar mediante coincidencias parciales utilizando `LIKE`.
4. Ordenar los resultados alfabéticamente por nombre de usuario.
5. Paginar los resultados en grupos de cinco registros.
6. Conservar los parámetros de consulta al cambiar de página mediante `withQueryString()`.

Devuelve una instancia de `LengthAwarePaginator`, que proporciona los registros y la información necesaria para construir la paginación.

### ActualizarUsuarioAdministrativoRequest

Archivo: `app/Http/Requests/UsuarioAdministrativo/ActualizarUsuarioAdministrativoRequest.php`

Valida y autoriza las solicitudes de actualización de usuarios administrativos.

**Autorización:**

Comprueba que el usuario autenticado tenga el rol `dueno`.

**Campos validados:**

- `usuario`: obligatorio, de tipo texto, entre 5 y 100 caracteres y único, exceptuando el registro que se está actualizando.
- `password`: opcional; cuando se envía una nueva contraseña, debe ser de tipo texto, no superar los 255 caracteres y cumplir la política de seguridad.
- `password_confirmation`: obligatoria cuando se envía `password` y debe coincidir con ella.
- `rol`: obligatorio y limitado a `dueno` y `empleado`.

### ActualizarUsuarioAdministrativo

Archivo: `app/Actions/UsuarioAdministrativo/ActualizarUsuarioAdministrativo.php`

Centraliza la actualización de la información de una cuenta administrativa.

Responsabilidades:

1. Recibir el usuario que se actualizará y los datos previamente validados.
2. Ejecutar la operación dentro de una transacción.
3. Bloquear los registros de usuarios administrativos para proteger las reglas relacionadas con los dueños activos.
4. Impedir que el sistema se quede sin dueños activos cuando se intenta cambiar el rol del último dueño activo a `empleado`.
5. Actualizar el nombre de usuario y el rol.
6. Actualizar la contraseña únicamente cuando se proporciona una nueva.
7. Guardar y devolver el usuario actualizado.

El hashing de la nueva contraseña se realiza mediante el cast `hashed` del modelo `UsuarioAdministrativo`.

### CambiarEstadoUsuarioAdministrativoRequest

Archivo: `app/Http/Requests/UsuarioAdministrativo/CambiarEstadoUsuarioAdministrativoRequest.php`

Autoriza la operación únicamente para usuarios con rol `dueno` y valida que `activo` sea obligatorio y represente un valor booleano válido.

### CambiarEstadoUsuarioAdministrativo

Archivo: `app/Actions/UsuarioAdministrativo/CambiarEstadoUsuarioAdministrativo.php`

Centraliza la activación y desactivación de cuentas administrativas.

Responsabilidades:

1. Ejecutar el cambio de estado dentro de una transacción.
2. Bloquear los registros de usuarios administrativos para proteger la regla del último dueño activo.
3. Impedir que un dueño desactive su propia cuenta.
4. Impedir que se desactive al último dueño activo.
5. Guardar y devolver el usuario con el estado actualizado.

La acción permite activar cuentas desactivadas y desactivar cuentas siempre que se respeten las restricciones anteriores.

### VerificarUsuarioActivo

Archivo: `app/Http/Middleware/VerificarUsuarioActivo.php`

Comprueba que la cuenta del usuario autenticado continúe activa antes de permitir el acceso a rutas protegidas.

Si la cuenta está desactivada, cierra la sesión, invalida sus datos y el token CSRF cuando existe una sesión, y devuelve HTTP `403 Forbidden`.

Este control permite revocar el acceso de una sesión que ya estaba iniciada cuando un dueño desactiva la cuenta.

### UsuarioAdministrativoController

Archivo: `app/Http/Controllers/UsuarioAdministrativoController.php`

Coordina las solicitudes HTTP relacionadas con la administración de usuarios administrativos.

Implementa los métodos `index()`, `store()`, `update()` y `cambiarEstado()`.

Responsabilidades:

1. Recibir la solicitud mediante `RegistrarUsuarioAdministrativoRequest`.
2. Obtener los datos previamente validados.
3. Ejecutar la acción `RegistrarUsuarioAdministrativo`.
4. Transformar el usuario creado mediante `UsuarioAdministrativoResource`.
5. Devolver una respuesta JSON con HTTP `201 Created`.
6. Gestionar las solicitudes de consulta mediante `ConsultarUsuariosAdministrativosRequest`.
7. Ejecutar la acción `ConsultarUsuariosAdministrativos`.
8. Transformar los resultados mediante `UsuarioAdministrativoResource::collection()`.
9. Devolver los registros junto con la información de paginación.
10. Gestionar las solicitudes de actualización mediante `ActualizarUsuarioAdministrativoRequest`.
11. Ejecutar la acción `ActualizarUsuarioAdministrativo` y devolver el usuario actualizado mediante `UsuarioAdministrativoResource`.
12. Gestionar los cambios de estado mediante `CambiarEstadoUsuarioAdministrativoRequest`.
13. Ejecutar la acción `CambiarEstadoUsuarioAdministrativo` y devolver el estado actualizado mediante `UsuarioAdministrativoResource`.

### UsuarioAdministrativoResource

Archivo: `app/Http/Resources/UsuarioAdministrativoResource.php`

Transforma el modelo `UsuarioAdministrativo` en una respuesta JSON controlada.

Atributos expuestos:

- `id_usuario`
- `usuario`
- `rol`
- `activo`

Se excluyen los atributos internos y sensibles, como la contraseña, los intentos fallidos y la fecha de bloqueo.

Este Resource se reutiliza en las operaciones de registro y consulta de usuarios administrativos.

### VerificarRolUsuarioAdministrativo

Archivo: `app/Http/Middleware/VerificarRolUsuarioAdministrativo.php`

Controla el acceso a las operaciones según los roles administrativos permitidos.

Responsabilidades:

1. Obtener el usuario autenticado.
2. Comprobar que exista un usuario autenticado.
3. Verificar que su rol coincida con alguno de los roles permitidos.
4. Rechazar la solicitud con HTTP `403 Forbidden` cuando no tenga autorización.

El middleware permite recibir uno o varios roles como parámetros.

## 4. Flujo de registro

El registro de usuarios administrativos se realiza mediante una solicitud HTTP `POST`.

### Endpoint

`POST /api/usuarios-administrativos`

**Middlewares aplicados:**

- `auth:sanctum`: requiere autenticación.
- `usuario.activo`: impide el acceso a cuentas desactivadas.
- `rol.administrativo:dueno`: permite únicamente el acceso a usuarios con rol `dueno`.

**Nombre de ruta:**

`usuarios-administrativos.store`

### Proceso de registro

El proceso sigue este orden:

1. El dueño accede a la funcionalidad de registro de usuarios administrativos.
2. El frontend envía una solicitud `POST /api/usuarios-administrativos`.
3. Laravel comprueba la autenticación mediante `auth:sanctum`.
4. El middleware `VerificarRolUsuarioAdministrativo` comprueba que el usuario tenga el rol `dueno`.
5. `RegistrarUsuarioAdministrativoRequest` autoriza la operación y valida los datos recibidos.
6. `UsuarioAdministrativoController` obtiene los datos validados.
7. El controlador ejecuta `RegistrarUsuarioAdministrativo`.
8. La acción crea el registro mediante Eloquent.
9. El modelo aplica el hashing de la contraseña.
10. `UsuarioAdministrativoResource` transforma el usuario creado.
11. El backend devuelve HTTP `201 Created` con un mensaje de confirmación y los datos permitidos del usuario.

### Respuesta exitosa

La API devuelve una respuesta JSON con la siguiente estructura:

```json
{
    "mensaje": "Usuario administrativo registrado correctamente.",
    "usuario": {
        "id_usuario": 2,
        "usuario": "empleado.test",
        "rol": "empleado",
        "activo": true
    }
}
```

Los valores son ilustrativos.

### Respuestas de error

| Código HTTP | Situación |
|---|---|
| `401 Unauthorized` | El usuario no está autenticado. |
| `403 Forbidden` | El usuario autenticado no tiene el rol requerido. |
| `422 Unprocessable Entity` | Los datos enviados incumplen las reglas de validación. |

## 5. Flujo de consulta

La consulta de usuarios administrativos se realiza mediante una solicitud HTTP `GET`.

### Endpoint

`GET /api/usuarios-administrativos`

**Middlewares aplicados:**

- `auth:sanctum`: requiere autenticación.
- `usuario.activo`: impide el acceso a cuentas desactivadas.
- `rol.administrativo:dueno`: permite únicamente el acceso a usuarios con rol `dueno`.

**Nombre de ruta:**

`usuarios-administrativos.index`

### Parámetros de consulta

| Parámetro | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `usuario` | String | No | Nombre de usuario utilizado para realizar la búsqueda parcial. |
| `page` | Integer | No | Número de página que se desea consultar. |

La paginación devuelve cinco registros por página. Si no se proporciona el parámetro `page`, se consulta la primera página.

### Proceso de consulta

El proceso sigue este orden:

1. El dueño solicita consultar los usuarios administrativos.
2. El frontend envía una solicitud `GET /api/usuarios-administrativos`.
3. Laravel comprueba la autenticación mediante `auth:sanctum`.
4. El middleware `VerificarRolUsuarioAdministrativo` comprueba que el usuario tenga el rol `dueno`.
5. `ConsultarUsuariosAdministrativosRequest` autoriza la operación y valida los parámetros recibidos.
6. `UsuarioAdministrativoController` obtiene los filtros validados.
7. El controlador ejecuta la acción `ConsultarUsuariosAdministrativos`.
8. La acción construye la consulta, aplica los filtros y pagina los resultados.
9. `UsuarioAdministrativoResource::collection()` transforma los registros.
10. El backend devuelve HTTP `200 OK` con los usuarios encontrados y la información de paginación.

### Búsqueda

La búsqueda se realiza mediante coincidencias parciales del nombre de usuario.

Por ejemplo:

`GET /api/usuarios-administrativos?usuario=cand`

La consulta devuelve los usuarios cuyos nombres contienen el texto proporcionado.

### Paginación

La consulta utiliza cinco registros por página.

Para acceder a otra página:

`GET /api/usuarios-administrativos?page=2`

También es posible conservar el filtro de búsqueda:

`GET /api/usuarios-administrativos?usuario=cand&page=2`

La acción utiliza `withQueryString()` para conservar los parámetros de consulta al generar los enlaces de paginación.

### Respuestas de error

| Código HTTP | Situación |
|---|---|
| `401 Unauthorized` | El usuario no está autenticado. |
| `403 Forbidden` | El usuario autenticado no tiene el rol requerido. |
| `422 Unprocessable Entity` | Los parámetros enviados incumplen las reglas de validación. |

## 6. Flujo de actualización y gestión del estado

La actualización de información y el cambio de estado se realizan mediante solicitudes HTTP `PATCH` independientes.

### Actualizar información

**Endpoint:** `PATCH /api/usuarios-administrativos/{usuarioAdministrativo}`

**Nombre de ruta:** `usuarios-administrativos.update`

Permite modificar el nombre de usuario y el rol. También permite establecer una nueva contraseña enviando `password` y `password_confirmation`; si no se envía una contraseña nueva, se conserva la actual.

**Proceso:**

1. El dueño envía la solicitud `PATCH` con los datos que desea actualizar.
2. Laravel comprueba la autenticación y que la cuenta del solicitante esté activa.
3. El middleware de rol y `ActualizarUsuarioAdministrativoRequest` restringen la operación al rol `dueno`.
4. La Request valida los datos recibidos.
5. `UsuarioAdministrativoController` ejecuta `ActualizarUsuarioAdministrativo`.
6. La acción aplica las reglas de protección del último dueño activo y guarda los cambios dentro de una transacción.
7. El backend devuelve HTTP `200 OK` con el usuario actualizado mediante `UsuarioAdministrativoResource`.

### Cambiar estado de una cuenta

**Endpoint:** `PATCH /api/usuarios-administrativos/{usuarioAdministrativo}/estado`

**Nombre de ruta:** `usuarios-administrativos.estado`

Recibe el campo booleano `activo`: `true` para activar la cuenta y `false` para desactivarla.

**Proceso:**

1. El dueño envía la solicitud `PATCH` con el nuevo estado.
2. Laravel comprueba la autenticación y que la cuenta del solicitante esté activa.
3. El middleware de rol y `CambiarEstadoUsuarioAdministrativoRequest` restringen la operación al rol `dueno` y validan el estado.
4. El controlador ejecuta `CambiarEstadoUsuarioAdministrativo`.
5. La acción aplica las restricciones de auto-desactivación y protección del último dueño activo dentro de una transacción.
6. El backend devuelve HTTP `200 OK` con el usuario y su estado actualizado.

### Reglas para proteger a los dueños activos

- Un dueño no puede desactivar su propia cuenta.
- Un dueño puede cambiar su propio rol si existe otro dueño activo.
- No se puede desactivar ni cambiar a `empleado` al último dueño activo.
- Las operaciones que pueden afectar la cantidad de dueños activos bloquean los registros dentro de una transacción para evitar que solicitudes concurrentes incumplan esta regla.

### Respuestas de error

| Código HTTP | Situación |
|---|---|
| `401 Unauthorized` | El usuario no está autenticado. |
| `403 Forbidden` | El usuario no tiene el rol requerido o su cuenta está desactivada. |
| `422 Unprocessable Entity` | Los datos no son válidos o se incumple una regla de negocio, como intentar desactivar la propia cuenta o modificar al último dueño activo. |

## 7. Reglas de seguridad

### Autorización por roles

El registro, la consulta, la actualización y la gestión del estado de usuarios administrativos están restringidos al rol `dueno`.

La autorización se comprueba mediante el middleware de la ruta y la autorización definida en las respectivas Requests.

El frontend no constituye el mecanismo de seguridad: la restricción se aplica en el backend.

### Unicidad del usuario

El nombre de usuario debe ser único dentro de la tabla `usuario_administrativo`.

La validación se realiza mediante la regla `unique` de Laravel y se complementa con la restricción UNIQUE de la base de datos.

### Política de contraseñas

Las contraseñas nuevas deben cumplir las siguientes condiciones:

| Condición | Requisito |
|---|---|
| Longitud mínima | 8 caracteres |
| Número | Al menos uno |
| Carácter especial | Al menos uno |
| Almacenamiento | Mediante hashing; nunca en texto plano |

### Estado de la cuenta

Durante el registro, el dueño puede establecer si la cuenta se encuentra activa o inactiva. Posteriormente, puede activar o desactivar la cuenta mediante la operación de cambio de estado.

El estado se almacena mediante el atributo booleano `activo`. Los usuarios con cuentas desactivadas no pueden iniciar sesión ni acceder a rutas protegidas. Si una cuenta se desactiva mientras mantiene una sesión iniciada, `VerificarUsuarioActivo` cierra esa sesión cuando intenta acceder nuevamente.

Un dueño no puede desactivar su propia cuenta y el sistema debe conservar al menos un dueño activo. Esta última condición también se protege frente a operaciones concurrentes.

### Protección de información sensible

Las respuestas de la API utilizan `UsuarioAdministrativoResource` para exponer únicamente los atributos necesarios.

No se devuelven contraseñas, contadores de intentos fallidos ni fechas de bloqueo.

### Protección de consultas administrativas

La consulta de usuarios administrativos está restringida al rol `dueno`.

La autorización se comprueba mediante el middleware de la ruta y la autorización definida en `ConsultarUsuariosAdministrativosRequest`.

La respuesta utiliza `UsuarioAdministrativoResource` para evitar exponer información sensible, como contraseñas, intentos fallidos y fechas de bloqueo.

## 8. Rutas API

Las rutas relacionadas con la administración de usuarios administrativos se encuentran en:

`routes/api.php`

Actualmente se encuentran implementadas:

| Método | Endpoint | Nombre | Operación |
|---|---|---|---|
| POST | `/api/usuarios-administrativos` | `usuarios-administrativos.store` | Registrar usuario administrativo. |
| GET | `/api/usuarios-administrativos` | `usuarios-administrativos.index` | Consultar usuarios administrativos. |
| PATCH | `/api/usuarios-administrativos/{usuarioAdministrativo}` | `usuarios-administrativos.update` | Actualizar información de un usuario administrativo. |
| PATCH | `/api/usuarios-administrativos/{usuarioAdministrativo}/estado` | `usuarios-administrativos.estado` | Activar o desactivar una cuenta administrativa. |

Las rutas están protegidas mediante `auth:sanctum`, `usuario.activo` y autorización por rol `rol.administrativo:dueno`.

## 9. Pruebas automatizadas

### Pruebas de registro de usuarios administrativos

Archivo: `tests/Feature/RegistrarUsuarioAdministrativoTest.php`

Se implementaron pruebas mediante PHPUnit para verificar el comportamiento del registro.

### Escenarios comprobados

1. Un dueño puede registrar un usuario administrativo con rol `empleado`.
2. Un dueño puede registrar otro usuario con rol `dueno`.
3. Un empleado no puede registrar usuarios administrativos.
4. Un usuario no autenticado no puede registrar usuarios administrativos.
5. No se permite registrar un nombre de usuario duplicado.
6. Se rechazan contraseñas que incumplen la política de seguridad.
7. Se rechaza el registro cuando la confirmación de contraseña no coincide.
8. No se permite asignar roles diferentes de `dueno` y `empleado`.
9. Se rechazan valores no válidos para el estado de la cuenta.
10. Se comprueba que todos los campos del registro sean obligatorios.
11. Se comprueban los límites de longitud del nombre de usuario.
12. Se verifica que la respuesta no exponga información sensible.
13. Un dueño puede registrar una cuenta desactivada.
14. Se comprueban los valores iniciales de seguridad del usuario registrado.

### Pruebas de consulta de usuarios administrativos

Archivo: `tests/Feature/ConsultarUsuariosAdministrativosTest.php`

Se implementaron pruebas mediante PHPUnit para verificar el comportamiento de la consulta.

**Escenarios comprobados:**

1. Un dueño puede consultar los usuarios administrativos.
2. Un empleado no puede consultar los usuarios administrativos.
3. Un usuario no autenticado no puede realizar la consulta.
4. Se permite buscar usuarios mediante coincidencias parciales.
5. Una búsqueda sin coincidencias devuelve una colección vacía.
6. Los resultados se paginan de cinco en cinco.
7. La búsqueda se conserva al cambiar de página.
8. No se permiten números de página menores que uno.
9. No se permiten valores no enteros en el número de página.
10. El nombre de usuario debe ser de tipo texto.
11. El nombre de usuario no puede superar los cien caracteres.
12. La respuesta no expone información sensible de los usuarios.

### Pruebas de actualización de usuarios administrativos

Archivo: `tests/Feature/ActualizarUsuarioAdministrativoTest.php`

**Escenarios comprobados:**

1. Un dueño puede actualizar el nombre de usuario.
2. Un dueño puede convertir un empleado en dueño.
3. Un dueño puede cambiar el rol de otro dueño cuando existen más dueños activos.
4. No se puede cambiar el rol del último dueño activo.
5. Un dueño puede cambiar su propio rol si existe otro dueño activo.
6. Un dueño puede actualizar la contraseña de otro usuario.
7. Se conserva la contraseña actual cuando no se envía una nueva.
8. Se rechazan contraseñas que no cumplen la política.
9. No se permite utilizar un nombre de usuario que ya pertenece a otra cuenta.
10. Un empleado no puede actualizar usuarios administrativos.
11. Un usuario no autenticado no puede actualizar usuarios administrativos.

### Pruebas de cambio de estado

Archivo: `tests/Feature/CambiarEstadoUsuarioAdministrativoTest.php`

**Escenarios comprobados:**

1. Un dueño puede desactivar y activar cuentas de empleados.
2. Un dueño puede desactivar a otro dueño cuando existen más dueños activos.
3. Un dueño no puede desactivar su propia cuenta.
4. Se rechazan valores inválidos para el estado.
5. Un empleado no puede cambiar el estado de usuarios administrativos.
6. Un usuario no autenticado no puede cambiar el estado de usuarios administrativos.

### Pruebas de protección del último dueño activo

Archivo: `tests/Unit/CambiarEstadoUsuarioAdministrativoTest.php`

Comprueba que la acción rechace la desactivación del último dueño activo y que su cuenta conserve el estado original.

### Pruebas de concurrencia

Archivo: `tests/Feature/ConcurrenciaUsuariosAdministrativosTest.php`

Comprueba mediante procesos independientes que dos operaciones simultáneas no puedan desactivar ni cambiar el rol de los dos últimos dueños activos, dejando el sistema sin un dueño activo.

### Pruebas de verificación de cuenta activa

Archivo: `tests/Feature/VerificarUsuarioActivoTest.php`

Comprueba que una cuenta activa pueda acceder a rutas protegidas, que una cuenta inactiva sea rechazada y que una sesión previamente autenticada pierda el acceso cuando la cuenta es desactivada.

### Resultado de las pruebas

Última ejecución completa:

```text
Tests: 85 passed (365 assertions)
```

Las pruebas de RF4 se ejecutan mediante:

```bash
herd php artisan test --filter=RegistrarUsuarioAdministrativoTest
```

Las pruebas de RF5 se ejecutan mediante:

```bash
herd php artisan test --filter=ConsultarUsuariosAdministrativosTest
```

Las pruebas de actualización de usuarios administrativos se ejecutan mediante:

```bash
herd php artisan test --filter=ActualizarUsuarioAdministrativoTest
```

Las pruebas de cambio de estado se ejecutan mediante:

```bash
herd php artisan test --filter=CambiarEstadoUsuarioAdministrativoTest
```

La suite completa del proyecto se ejecuta mediante:

```bash
herd php artisan test
```

## 10. Estado del módulo

El módulo de administración de usuarios administrativos cuenta con las funcionalidades de registro (RF4), consulta (RF5), actualización y gestión del estado (RF6), implementadas y validadas mediante pruebas automatizadas.

Cada nuevo requerimiento deberá contar con su correspondiente implementación, pruebas automatizadas y documentación técnica antes de considerarse terminado.