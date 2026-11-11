# Módulo de gestión de tipos de membresía -- GymFlow

## 1. Objetivo y alcance

El módulo de gestión de tipos de membresía permite administrar las modalidades de membresía que ofrece el gimnasio.

Actualmente, contempla el registro de nuevos tipos de membresía, definiendo su nombre, duración en días y precio. Cada tipo registrado queda habilitado inicialmente para que pueda utilizarse en el registro de membresías adquiridas por los clientes.

El sistema permite que los usuarios administrativos con rol Dueño o Empleado realicen esta operación.

### Funcionalidades implementadas

- Registro de nuevos tipos de membresía.
- Definición del nombre, duración y precio.
- Validación de unicidad del nombre.
- Validación de la duración y el precio mediante reglas de negocio.
- Asignación automática del estado inicial habilitado.
- Protección de la operación mediante autenticación y verificación del estado de la cuenta.
- Respuestas controladas mediante un Resource.
- Validación automatizada de los escenarios de registro.

## 2. Tecnologías utilizadas

| Tecnología | Responsabilidad |
|---|---|
| Laravel 13 | Backend y gestión de solicitudes. |
| Laravel Sanctum | Autenticación de solicitudes API. |
| MySQL 8 | Almacenamiento de tipos de membresía. |
| PHPUnit | Pruebas automatizadas. |

## 3. Componentes y responsabilidades

### TipoMembresia

Archivo: `app/Models/TipoMembresia.php`

Representa el modelo asociado a la tabla `tipo_membresia`.

Configuraciones principales:

- Tabla personalizada: `tipo_membresia`.
- Clave primaria: `id_tipo_membresia`.
- Atributos asignables mediante asignación masiva: `nombre`, `duracion_dias`, `precio` y `activo`.
- Conversión de `duracion_dias` a entero.
- Conversión de `precio` a decimal con dos posiciones.
- Conversión de `activo` a booleano.

### RegistrarTipoMembresiaRequest

Archivo: `app/Http/Requests/TipoMembresia/RegistrarTipoMembresiaRequest.php`

Valida y autoriza las solicitudes de registro de tipos de membresía.

**Autorización:**

Permite realizar la operación a usuarios autenticados con rol `dueno` o `empleado`.

**Campos validados:**

- `nombre`: obligatorio, de tipo texto, con un máximo de 100 caracteres y único en la tabla `tipo_membresia`.
- `duracion_dias`: obligatoria, de tipo entero y mayor que cero.
- `precio`: obligatorio, de tipo numérico, mayor que cero y con un máximo de dos decimales.

También proporciona mensajes de validación personalizados en español.

La validación de unicidad utiliza la restricción de la base de datos, respetando su configuración de comparación de nombres.

### RegistrarTipoMembresia

Archivo: `app/Actions/TipoMembresia/RegistrarTipoMembresia.php`

Centraliza la operación de registro de tipos de membresía.

Responsabilidades:

1. Recibir los datos previamente validados.
2. Crear el tipo de membresía mediante Eloquent.
3. Recuperar los valores actualizados desde la base de datos mediante `refresh()`.
4. Devolver la instancia del tipo de membresía registrado.

La recuperación mediante `refresh()` permite obtener también los valores asignados automáticamente por la base de datos, como el estado inicial habilitado.

### TipoMembresiaController

Archivo: `app/Http/Controllers/TipoMembresiaController.php`

Coordina las solicitudes HTTP relacionadas con la gestión de tipos de membresía.

Actualmente, implementa el método `store()` para registrar nuevos tipos de membresía.

Responsabilidades:

1. Recibir la solicitud mediante `RegistrarTipoMembresiaRequest`.
2. Obtener los datos previamente validados.
3. Ejecutar la acción `RegistrarTipoMembresia`.
4. Transformar el resultado mediante `TipoMembresiaResource`.
5. Devolver una respuesta JSON con HTTP `201 Created`.

El controlador contempla los métodos correspondientes a las operaciones de consulta, actualización y cambio de estado, que se implementarán en sus respectivos requerimientos funcionales.

### TipoMembresiaResource

Archivo: `app/Http/Resources/TipoMembresiaResource.php`

Transforma el modelo `TipoMembresia` en una respuesta JSON controlada.

Atributos expuestos:

- `id_tipo_membresia`
- `nombre`
- `duracion_dias`
- `precio`
- `activo`

## 4. Flujo de registro

El registro de tipos de membresía se realiza mediante una solicitud HTTP `POST`.

### Endpoint

`POST /api/tipos-membresia`

**Middlewares aplicados:**

- `auth:sanctum`: requiere autenticación.
- `usuario.activo`: impide el acceso a cuentas desactivadas.

La autorización por rol se realiza mediante `RegistrarTipoMembresiaRequest`, que permite únicamente a usuarios con rol `dueno` o `empleado`.

**Nombre de ruta:**

`tipos-membresia.store`

### Proceso de registro

El proceso sigue este orden:

1. El usuario administrativo accede a la funcionalidad de registro de tipos de membresía.
2. El frontend envía una solicitud `POST /api/tipos-membresia`.
3. Laravel comprueba la autenticación mediante `auth:sanctum`.
4. El middleware `VerificarUsuarioActivo` comprueba que la cuenta del usuario continúe activa.
5. `RegistrarTipoMembresiaRequest` autoriza la operación y valida los datos recibidos.
6. `TipoMembresiaController` obtiene los datos validados.
7. El controlador ejecuta la acción `RegistrarTipoMembresia`.
8. La acción crea el registro mediante Eloquent.
9. La acción recupera los valores almacenados mediante `refresh()`.
10. `TipoMembresiaResource` transforma el modelo registrado.
11. El backend devuelve HTTP `201 Created` con un mensaje de confirmación y los datos del tipo de membresía.

### Respuesta exitosa

La API devuelve una respuesta JSON con la siguiente estructura:

```json
{
    "mensaje": "Tipo de membresía registrado correctamente.",
    "tipo_membresia": {
        "id_tipo_membresia": 1,
        "nombre": "Mensual",
        "duracion_dias": 30,
        "precio": "80000.00",
        "activo": true
    }
}
```

Los valores son ilustrativos.

### Respuestas de error

| Código HTTP | Situación |
|---|---|
| `401 Unauthorized` | El usuario no está autenticado. |
| `403 Forbidden` | La cuenta del usuario está desactivada o no tiene autorización para realizar la operación. |
| `422 Unprocessable Entity` | Los datos enviados incumplen las reglas de validación. |

## 5. Reglas de validación y seguridad

### Autorización por roles

El registro de tipos de membresía está permitido a los usuarios con rol `dueno` y `empleado`.

La autorización se comprueba mediante `RegistrarTipoMembresiaRequest`.

### Unicidad del nombre

El nombre de cada tipo de membresía debe ser único dentro de la tabla `tipo_membresia`.

La validación se realiza mediante la regla `unique` de Laravel y se complementa con la restricción UNIQUE de la base de datos.

La comparación de nombres respeta la configuración de la collation de la columna, por lo que no se consideran diferentes únicamente por el uso de mayúsculas y minúsculas.

### Duración

La duración debe ser un número entero mayor que cero, expresado en días.

### Precio

El precio debe ser un valor numérico mayor que cero y admitir como máximo dos posiciones decimales.

### Estado inicial

Los tipos de membresía se registran inicialmente habilitados.

El valor predeterminado se establece en la base de datos mediante el atributo booleano `activo`.

### Protección de rutas

Las operaciones del módulo están protegidas mediante autenticación con Sanctum y verificación del estado de la cuenta administrativa.

El frontend no constituye el mecanismo de seguridad: las restricciones se aplican en el backend.

## 6. Rutas API

Las rutas relacionadas con la gestión de tipos de membresía se encuentran en:

`routes/api.php`

Actualmente se encuentran implementadas:

| Método | Endpoint | Nombre | Operación |
|---|---|---|---|
| POST | `/api/tipos-membresia` | `tipos-membresia.store` | Registrar tipo de membresía. |

Las rutas están protegidas mediante `auth:sanctum` y `usuario.activo`.

## 7. Pruebas automatizadas

### Pruebas de registro de tipos de membresía

Archivo: `tests/Feature/TipoMembresia/RegistrarTipoMembresiaTest.php`

Se implementaron pruebas mediante PHPUnit para verificar el comportamiento del registro.

**Escenarios comprobados:**

1. Un dueño puede registrar un tipo de membresía.
2. Un empleado puede registrar un tipo de membresía.
3. Un usuario no autenticado no puede registrar tipos de membresía.
4. Un usuario inactivo no puede registrar tipos de membresía.
5. Se comprueba que todos los campos del registro sean obligatorios.
6. La duración y el precio deben ser mayores que cero.
7. Los campos deben cumplir con sus tipos de datos.
8. El precio no puede tener más de dos decimales.
9. No se permite registrar un tipo de membresía con un nombre duplicado.
10. No se permite registrar nombres que solo difieren en mayúsculas y minúsculas.
11. El nombre no puede superar los cien caracteres.
12. Se permite registrar un tipo de membresía con un nombre de cien caracteres.
13. Se permite registrar un tipo de membresía con dos decimales en el precio.

### Resultado de las pruebas

Última ejecución completa:

```text
Tests: 97 passed (439 assertions)
```

Las pruebas de RF11 se ejecutan mediante:

```bash
herd php artisan test --filter=RegistrarTipoMembresiaTest
```

La suite completa del proyecto se ejecuta mediante:

```bash
herd php artisan test
```

## 8. Estado del módulo

El módulo de gestión de tipos de membresía cuenta con el registro (RF11), implementado y validado mediante pruebas automatizadas.

La consulta (RF12), la actualización y gestión del estado (RF13) se desarrollarán posteriormente, siguiendo el mismo proceso de análisis, implementación, pruebas y documentación técnica.