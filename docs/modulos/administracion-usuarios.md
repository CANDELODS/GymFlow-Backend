# Módulo de administración de usuarios administrativos -- GymFlow

## 1. Objetivo y alcance

El módulo de administración de usuarios administrativos permite gestionar las cuentas de acceso al sistema GymFlow.

Actualmente, contempla el registro de nuevas cuentas administrativas, permitiendo asignar uno de los roles disponibles y establecer el estado inicial de la cuenta.

El sistema contempla dos roles administrativos:

- **Dueño (`dueno`):** usuario con permisos administrativos superiores.
- **Empleado (`empleado`):** usuario con permisos operativos según las restricciones del sistema.

El registro de nuevas cuentas está restringido exclusivamente a usuarios autenticados con el rol `dueno`.

### Funcionalidades implementadas

- Registro de nuevos usuarios administrativos.
- Asignación de roles durante el registro.
- Selección del estado inicial de la cuenta (activo o inactivo).
- Validación de unicidad del nombre de usuario.
- Validación de la política de contraseñas.
- Protección de las operaciones mediante autenticación y autorización.
- Exclusión de información sensible en las respuestas de la API.

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

### UsuarioAdministrativoController

Archivo: `app/Http/Controllers/UsuarioAdministrativoController.php`

Coordina las solicitudes HTTP relacionadas con la administración de usuarios administrativos.

Actualmente implementa el método `store()`.

Responsabilidades:

1. Recibir la solicitud mediante `RegistrarUsuarioAdministrativoRequest`.
2. Obtener los datos previamente validados.
3. Ejecutar la acción `RegistrarUsuarioAdministrativo`.
4. Transformar el usuario creado mediante `UsuarioAdministrativoResource`.
5. Devolver una respuesta JSON con HTTP `201 Created`.

### UsuarioAdministrativoResource

Archivo: `app/Http/Resources/UsuarioAdministrativoResource.php`

Transforma el modelo `UsuarioAdministrativo` en una respuesta JSON controlada.

Atributos expuestos:

- `id_usuario`
- `usuario`
- `rol`
- `activo`

Se excluyen los atributos internos y sensibles, como la contraseña, los intentos fallidos y la fecha de bloqueo.

Este Resource está diseñado para reutilizarse en las futuras operaciones de consulta y actualización de usuarios administrativos.

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

## 5. Reglas de seguridad

### Autorización por roles

El registro de usuarios administrativos está restringido al rol `dueno`.

La autorización se comprueba mediante el middleware de la ruta y la autorización definida en la Request.

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

Durante el registro, el dueño puede establecer si la cuenta se encuentra activa o inactiva.

El estado se almacena mediante el atributo booleano `activo`.

### Protección de información sensible

Las respuestas de la API utilizan `UsuarioAdministrativoResource` para exponer únicamente los atributos necesarios.

No se devuelven contraseñas, contadores de intentos fallidos ni fechas de bloqueo.

## 6. Rutas API

Las rutas relacionadas con la administración de usuarios administrativos se encuentran en:

`routes/api.php`

Actualmente se encuentra implementada:

| Método | Endpoint | Nombre | Operación |
|---|---|---|---|
| POST | `/api/usuarios-administrativos` | `usuarios-administrativos.store` | Registrar usuario administrativo. |

La ruta está protegida mediante autenticación Sanctum y autorización por rol.

## 7. Pruebas automatizadas

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

### Resultado de las pruebas

Última ejecución completa:

```text
Tests: 49 passed (225 assertions)
```

Las pruebas de RF4 se ejecutan mediante:

```bash
herd php artisan test --filter=RegistrarUsuarioAdministrativoTest
```

La suite completa del proyecto se ejecuta mediante:

```bash
herd php artisan test
```

## 8. Estado del módulo

El registro de usuarios administrativos (RF4) se encuentra implementado y validado mediante pruebas automatizadas.

Las siguientes funcionalidades se desarrollarán progresivamente:

- Consultar usuarios administrativos.
- Actualizar usuarios administrativos.

Cada nuevo requerimiento deberá contar con su correspondiente implementación, pruebas automatizadas y documentación técnica antes de considerarse terminado.
