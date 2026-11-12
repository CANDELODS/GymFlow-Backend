# Módulo de gestión de tipos de membresía -- GymFlow

## 1. Objetivo y alcance

El módulo de gestión de tipos de membresía permite administrar las modalidades de membresía que ofrece el gimnasio.

Actualmente, contempla el registro de nuevos tipos de membresía, definiendo su nombre, duración en días y precio. Cada tipo registrado queda habilitado inicialmente para que pueda utilizarse en el registro de membresías adquiridas por los clientes.

También permite consultar los tipos registrados, buscar por coincidencias parciales del nombre y filtrar los resultados según su estado. La consulta se encuentra disponible para usuarios administrativos con rol Dueño o Empleado y presenta los resultados en páginas de cinco registros, ordenados alfabéticamente por nombre.

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
- Consulta de tipos de membresía con búsqueda por nombre y filtros por estado.
- Ordenamiento alfabético y paginación de cinco registros por página.
- Conservación de los filtros al navegar entre páginas.
- Validación automatizada de los escenarios de consulta.

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

### ConsultarTiposMembresiaRequest

Archivo: `app/Http/Requests/TipoMembresia/ConsultarTiposMembresiaRequest.php`

Valida y autoriza las solicitudes de consulta de tipos de membresía.

**Autorización:**

Permite realizar la operación a usuarios autenticados con rol `dueno` o `empleado`.

**Parámetros validados:**

- `nombre`: opcional, de tipo texto y con un máximo de 100 caracteres. Se utiliza para buscar coincidencias parciales.
- `activo`: opcional, de tipo booleano. Permite filtrar los tipos habilitados (`1`) o deshabilitados (`0`).
- `page`: opcional, de tipo entero y con un valor mínimo de 1.

También proporciona mensajes de validación personalizados en español.

### ConsultarTiposMembresia

Archivo: `app/Actions/TipoMembresia/ConsultarTiposMembresia.php`

Centraliza la consulta de tipos de membresía.

Responsabilidades:

1. Construir la consulta sobre el modelo `TipoMembresia`.
2. Aplicar el filtro por nombre cuando se proporciona, utilizando coincidencias parciales.
3. Aplicar el filtro por estado cuando se proporciona, permitiendo consultar tanto registros activos como inactivos.
4. Ordenar los resultados alfabéticamente por nombre.
5. Paginar los resultados en grupos de cinco registros.
6. Conservar los parámetros de búsqueda y filtrado en los enlaces de paginación.

### TipoMembresiaController

Archivo: `app/Http/Controllers/TipoMembresiaController.php`

Coordina las solicitudes HTTP relacionadas con la gestión de tipos de membresía.

Actualmente, implementa los métodos `store()` para registrar nuevos tipos de membresía e `index()` para consultar los tipos registrados.

**Responsabilidades de `store()`:**

1. Recibir la solicitud mediante `RegistrarTipoMembresiaRequest`.
2. Obtener los datos previamente validados.
3. Ejecutar la acción `RegistrarTipoMembresia`.
4. Transformar el resultado mediante `TipoMembresiaResource`.
5. Devolver una respuesta JSON con HTTP `201 Created`.

**Responsabilidades de `index()`:**

1. Recibir la solicitud mediante `ConsultarTiposMembresiaRequest`.
2. Obtener los filtros previamente validados.
3. Ejecutar la acción `ConsultarTiposMembresia`.
4. Transformar los resultados mediante `TipoMembresiaResource`.
5. Devolver una colección JSON con los datos y la información de paginación.

Los métodos correspondientes a la actualización y cambio de estado se implementarán en sus respectivos requerimientos funcionales.

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

### Flujo de consulta

La consulta de tipos de membresía se realiza mediante una solicitud HTTP `GET`.

**Endpoint:**

`GET /api/tipos-membresia`

**Parámetros opcionales:**

| Parámetro | Descripción | Valores |
|---|---|---|
| `nombre` | Busca tipos cuyo nombre contenga el texto indicado. | Texto de hasta 100 caracteres. |
| `activo` | Filtra por estado. | `1` para activos, `0` para inactivos. |
| `page` | Indica la página que se desea consultar. | Entero mayor o igual a 1. |

Los parámetros pueden combinarse. Por ejemplo:

- `GET /api/tipos-membresia?nombre=mens`
- `GET /api/tipos-membresia?activo=1`
- `GET /api/tipos-membresia?nombre=mens&activo=0`
- `GET /api/tipos-membresia?nombre=mens&activo=0&page=2`

**Nombre de ruta:** `tipos-membresia.index`.

La respuesta utiliza la estructura de colección de Laravel Resource, con los resultados dentro de `data` y la información de paginación en `links` y `meta`. Si no existen registros o no se encuentran coincidencias, `data` contiene una colección vacía.

El proceso sigue este orden:

1. El usuario administrativo solicita la consulta de tipos de membresía.
2. El frontend envía una solicitud `GET /api/tipos-membresia`, incluyendo los filtros opcionales.
3. Laravel comprueba la autenticación mediante `auth:sanctum`.
4. El middleware `VerificarUsuarioActivo` comprueba que la cuenta del usuario continúe activa.
5. `ConsultarTiposMembresiaRequest` autoriza la operación y valida los parámetros recibidos.
6. `TipoMembresiaController` obtiene los filtros validados.
7. El controlador ejecuta la acción `ConsultarTiposMembresia`.
8. La acción aplica los filtros, ordena los resultados por nombre y pagina los registros de cinco en cinco.
9. `TipoMembresiaResource` transforma los resultados.
10. El backend devuelve HTTP `200 OK` con la colección y los metadatos de paginación.

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

### Búsqueda y filtros

La búsqueda por `nombre` permite coincidencias parciales y admite hasta 100 caracteres.

El parámetro `activo` acepta valores booleanos válidos. En las consultas mediante URL se utilizan `1` para los tipos activos y `0` para los inactivos. Cuando el parámetro no se proporciona, se incluyen ambos estados.

### Paginación

Los resultados se ordenan alfabéticamente por nombre y se presentan en páginas de cinco registros. El parámetro `page` debe ser un entero mayor o igual a 1.

Los filtros aplicados se conservan en los enlaces de paginación.

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
| GET | `/api/tipos-membresia` | `tipos-membresia.index` | Consultar tipos de membresía. |

Las rutas están protegidas mediante `auth:sanctum` y `usuario.activo`. La autorización por rol se realiza mediante los respectivos Form Requests y permite el acceso a usuarios con rol `dueno` o `empleado`.

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

### Pruebas de consulta de tipos de membresía

Archivo: `tests/Feature/TipoMembresia/ConsultarTiposMembresiaTest.php`

Se implementaron pruebas mediante PHPUnit para verificar el comportamiento de la consulta.

**Escenarios comprobados:**

1. Un dueño puede consultar los tipos de membresía.
2. Un empleado puede consultar los tipos de membresía.
3. Un usuario no autenticado no puede consultar tipos de membresía.
4. Un usuario inactivo no puede consultar tipos de membresía.
5. La búsqueda permite encontrar coincidencias parciales por nombre.
6. Una búsqueda sin coincidencias devuelve una colección vacía.
7. Se pueden consultar únicamente los tipos activos.
8. Se pueden consultar únicamente los tipos inactivos.
9. Se pueden combinar la búsqueda por nombre y el filtro por estado.
10. Los resultados se paginan de cinco en cinco.
11. Los filtros se conservan al cambiar de página.
12. No se permiten números de página menores que uno.
13. No se permiten valores no numéricos para el número de página.
14. El parámetro `nombre` debe ser de tipo texto.
15. No se permiten búsquedas con nombres de más de 100 caracteres.
16. Se permiten búsquedas con nombres de exactamente 100 caracteres.
17. El parámetro `activo` debe contener un valor booleano válido.
18. Sin tipos de membresía registrados, se devuelve una colección vacía.

### Resultado de las pruebas

Última ejecución completa:

```text
Tests: 115 passed (537 assertions)
```

Las pruebas de RF11 se ejecutan mediante:

```bash
herd php artisan test --filter=RegistrarTipoMembresiaTest
```

Las pruebas de RF12 se ejecutan mediante:

```bash
herd php artisan test --filter=ConsultarTiposMembresiaTest
```

La suite completa del proyecto se ejecuta mediante:

```bash
herd php artisan test
```

## 8. Estado del módulo

El módulo de gestión de tipos de membresía cuenta con el registro (RF11) y la consulta (RF12), implementados y validados mediante pruebas automatizadas.

La actualización y gestión del estado (RF13) se desarrollarán posteriormente, siguiendo el mismo proceso de análisis, implementación, pruebas y documentación técnica.