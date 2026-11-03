# Módulo de autenticación – GymFlow

## 1. Objetivo y alcance

El módulo de autenticación permite controlar el acceso al sistema administrativo de GymFlow, garantizando que únicamente los usuarios administrativos autorizados puedan ingresar a la aplicación.

El sistema contempla dos roles administrativos:

- **Dueño (`dueno`):** usuario con permisos administrativos superiores.
- **Empleado (`empleado`):** usuario con permisos operativos según las restricciones del sistema.

Los clientes del gimnasio no tienen cuentas de acceso a la aplicación.

### Funcionalidades implementadas

- Inicio de sesión mediante usuario y contraseña.
- Validación de credenciales.
- Rechazo de usuarios inexistentes o desactivados.
- Bloqueo temporal después de intentos fallidos.
- Limitación de solicitudes de autenticación.
- Autenticación mediante sesiones y cookies.
- Cierre de sesión e invalidación de la sesión autenticada.
- Regeneración del token CSRF después del cierre de sesión.
- Redirección al inicio de sesión después de cerrar sesión.
- Expiración automática por inactividad.
- Protección de rutas mediante autenticación.

## 2. Tecnologías utilizadas

| Tecnología | Responsabilidad |
|---|---|
| Laravel 13 | Backend y gestión de solicitudes. |
| Laravel Fortify | Gestión del proceso de autenticación. |
| Laravel Sanctum | Autenticación SPA mediante sesiones y cookies. |
| MySQL 8 | Almacenamiento de usuarios y sesiones. |
| Vue CLI | Entorno de desarrollo del frontend. |
| TypeScript | Desarrollo del frontend. |
| Vue Router | Gestión de la navegación entre vistas. |
| Axios | Comunicación HTTP entre Vue y Laravel. |

## 3. Componentes y responsabilidades

### Modelo UsuarioAdministrativo

Archivo: `app/Models/UsuarioAdministrativo.php`

Representa los usuarios administrativos del sistema.

Características principales:

- Utiliza la tabla `usuario_administrativo`.
- Define `id_usuario` como clave primaria.
- Oculta la contraseña en las serializaciones.
- Utiliza el cast `hashed` para almacenar contraseñas mediante hash.
- Convierte los atributos correspondientes a sus tipos de datos.

### UsuarioAdministrativoLoginRequest

Archivo: `app/Http/Requests/UsuarioAdministrativoLoginRequest.php`

Personaliza la validación de las solicitudes de inicio de sesión.

Validaciones principales:

- Usuario obligatorio, de tipo texto, entre 5 y 100 caracteres.
- Contraseña obligatoria, de tipo texto, entre 8 y 255 caracteres.

También proporciona mensajes de validación en español.

### AutenticarUsuarioAdministrativo

Archivo: `app/Actions/Auth/AutenticarUsuarioAdministrativo.php`

Centraliza el proceso de autenticación.

Responsabilidades:

1. Buscar el usuario administrativo.
2. Comprobar que exista y esté activo.
3. Verificar si tiene un bloqueo temporal vigente.
4. Reiniciar el contador cuando un bloqueo haya expirado.
5. Comprobar la contraseña mediante su hash.
6. Registrar los intentos fallidos.
7. Reiniciar el contador después de una autenticación exitosa.

Utiliza transacciones de base de datos y `lockForUpdate()` para coordinar las actualizaciones del contador de intentos y reducir problemas de concurrencia.

### GestionarIntentosFallidos

Archivo: `app/Actions/Auth/GestionarIntentosFallidos.php`

Centraliza la lógica relacionada con los intentos fallidos y los bloqueos temporales.

Políticas implementadas:

- Cinco intentos fallidos consecutivos.
- Bloqueo temporal de 15 minutos.
- Reinicio del contador después de una autenticación exitosa.
- Permite nuevos intentos cuando el bloqueo ha expirado.

### FortifyServiceProvider

Archivo: `app/Providers/FortifyServiceProvider.php`

Configura el comportamiento de Laravel Fortify.

Responsabilidades:

- Registrar la implementación personalizada de `LoginRequest`.
- Configurar el limitador de solicitudes de autenticación.
- Delegar la autenticación a `AutenticarUsuarioAdministrativo`.

### Cierre de sesión mediante Laravel Fortify

Archivos de referencia:

- `vendor/laravel/fortify/src/Http/Controllers/AuthenticatedSessionController.php`
- `vendor/laravel/fortify/src/Http/Responses/LogoutResponse.php`

GymFlow utiliza el mecanismo de cierre de sesión proporcionado por Laravel Fortify, evitando implementar un controlador personalizado para esta operación.

La ruta `POST /logout` utiliza el middleware `auth:web`, por lo que requiere una sesión autenticada.

El controlador de Fortify realiza las siguientes operaciones:

1. Cierra la autenticación mediante el guard correspondiente.
2. Invalida la sesión actual mediante `invalidate()`.
3. Regenera el token CSRF mediante `regenerateToken()`.
4. Devuelve la respuesta correspondiente a la solicitud.

Cuando la solicitud espera JSON, Fortify responde con HTTP `204 No Content`.

La navegación posterior hacia `/login` es responsabilidad del frontend mediante Vue Router.

### VerificarExpiracionSesion

Archivo: `app/Http/Middleware/VerificarExpiracionSesion.php`

Controla la expiración de las sesiones por inactividad.

Funcionamiento:

1. Comprueba si existe un usuario autenticado mediante el guard `web`.
2. Recupera el valor `ultima_actividad` de la sesión.
3. Comprueba si han transcurrido 30 minutos o más.
4. Si la sesión expiró, cierra la autenticación, invalida la sesión y regenera el token CSRF.
5. Si continúa vigente, actualiza `ultima_actividad`.

## 4. Flujo de autenticación

GymFlow utiliza autenticación SPA mediante sesiones y cookies, integrando Laravel Fortify y Laravel Sanctum.

### Inicio de sesión

El proceso sigue este orden:

1. Vue solicita `GET /sanctum/csrf-cookie`.
2. Laravel genera las cookies necesarias para la sesión y la protección CSRF.
3. Vue envía `POST /login` con el usuario y la contraseña.
4. Fortify delega la autenticación a `AutenticarUsuarioAdministrativo`.
5. Si las credenciales son correctas, Laravel establece la sesión autenticada.
6. Vue puede consultar `GET /api/user` para recuperar los datos del usuario autenticado.

### Protección de rutas

Las rutas que requieren autenticación utilizan los mecanismos de Laravel correspondientes.

Para las rutas API se utiliza:

`auth:sanctum`

La ruta `GET /api/user` permite consultar los datos del usuario autenticado.

### Cierre de sesión

El proceso de cierre de sesión sigue este orden:

1. El usuario selecciona la opción de cerrar sesión en GymFlow.
2. Vue envía una solicitud `POST /logout` mediante Axios.
3. Laravel verifica la autenticación mediante el middleware `auth:web`.
4. Fortify cierra la autenticación del usuario.
5. Laravel invalida la sesión actual y regenera el token CSRF.
6. Fortify devuelve una respuesta HTTP `204 No Content` cuando la solicitud espera JSON.
7. Vue recibe la respuesta satisfactoria y utiliza Vue Router para redirigir al usuario a `/login`.

Después del cierre de sesión, las solicitudes a rutas protegidas deben ser rechazadas mientras no exista una nueva autenticación.

## 5. Reglas de seguridad

### Intentos fallidos

| Parámetro | Política |
|---|---|
| Intentos permitidos | 5 |
| Duración del bloqueo | 15 minutos |
| Reinicio del contador | Después de una autenticación exitosa |
| Usuario desactivado | No puede autenticarse |

### Limitación de solicitudes

Se implementaron dos límites complementarios:

| Alcance | Límite |
|---|---|
| Por dirección IP | 15 solicitudes por minuto |
| Por usuario e IP | 5 solicitudes por minuto |

Ambos límites se aplican al proceso de autenticación.

### Protección CSRF

Las solicitudes del frontend utilizan cookies y el encabezado `X-XSRF-TOKEN`.

Axios se configura para enviar credenciales y gestionar automáticamente el token CSRF.

Después del cierre de sesión, Laravel regenera el token CSRF para proteger las siguientes solicitudes.

### Expiración de sesiones

- Duración máxima de inactividad: 30 minutos.
- Cada solicitud autenticada válida actualiza `ultima_actividad`.
- La expiración se comprueba en los grupos de middleware `web` y `api`.
- Al expirar, Laravel invalida la sesión y rechaza el acceso con HTTP 401.

### Cierre de sesión

- La ruta de cierre de sesión utiliza el método HTTP `POST`.
- Requiere autenticación mediante el guard `web`.
- La sesión autenticada se invalida al cerrar sesión.
- Se regenera el token CSRF.
- El backend responde con HTTP `204 No Content` para solicitudes JSON.
- El frontend redirige al usuario a `/login` después de recibir una respuesta satisfactoria.
- Las rutas protegidas rechazan solicitudes posteriores sin autenticación.

## 6. Configuración de la integración

### Laravel Sanctum

Se utiliza `statefulApi()` en `bootstrap/app.php` para permitir la autenticación mediante cookies y sesiones en solicitudes API provenientes del frontend.

Se configuró el dominio del frontend en `SANCTUM_STATEFUL_DOMAINS`.

### CORS

Archivo: `config/cors.php`

La configuración permite solicitudes desde el origen del frontend:

`http://gymflow.test:8080`

Se habilitó `supports_credentials` para permitir el intercambio de cookies.

Las rutas contempladas incluyen:

- `api/*`
- `sanctum/csrf-cookie`
- `login`
- `logout`

### Variables de entorno

Configuración relevante de desarrollo:

```env
SESSION_DRIVER=database
SESSION_LIFETIME=30
SANCTUM_STATEFUL_DOMAINS=gymflow.test:8080
```

En el entorno de pruebas:

```env
SESSION_DRIVER=array
SESSION_LIFETIME=30
SANCTUM_STATEFUL_DOMAINS=gymflow.test:8080
```

No se deben incluir secretos, contraseñas ni claves privadas en este documento.

### Frontend

El frontend utiliza Axios mediante una instancia centralizada en `src/services/api.ts`.

Configuración de la dirección del backend:

```env
VUE_APP_API_URL=http://gymflow.test
```

El servidor de desarrollo Vue CLI utiliza:

`http://gymflow.test:8080`

El backend Laravel utiliza:

`http://gymflow.test`

Ambos comparten hostname para facilitar el manejo de cookies durante el desarrollo local.

### Vue Router

El frontend utiliza Vue Router para gestionar la navegación entre las vistas de la aplicación.

La instancia del enrutador se registra en `main.ts` y las rutas se definen en `src/router/index.ts`.

Después de cerrar sesión correctamente, el frontend utiliza `router.push('/login')` para navegar hacia la vista de inicio de sesión sin recargar toda la aplicación.

## 7. Pruebas automatizadas

Se implementaron pruebas de autenticación y sesiones mediante PHPUnit.

### Pruebas de autenticación

Archivo: `tests/Feature/UsuarioAdministrativoLoginTest.php`

Se comprueban, entre otros escenarios:

- Rechazo de credenciales vacías.
- Validación de longitud del usuario y contraseña.
- Autenticación exitosa.
- Incremento de intentos fallidos.
- Bloqueo después de cinco intentos.
- Rechazo de usuarios bloqueados.
- Reintento después de expirar el bloqueo.
- Rechazo de usuarios desactivados e inexistentes.
- Limitación de solicitudes por IP y por usuario e IP.
- Duración exacta del bloqueo.

### Pruebas de cierre de sesión

Archivo: `tests/Feature/UsuarioAdministrativoLogoutTest.php`

Se comprueban los siguientes escenarios:

- Cierre de sesión exitoso de un usuario autenticado.
- Rechazo del acceso a rutas protegidas después del cierre de sesión.
- Rechazo de solicitudes de cierre de sesión sin autenticación.
- Invalidación del identificador de la sesión anterior.

### Pruebas de sesiones

Archivo: `tests/Feature/UsuarioAdministrativoSesionTest.php`

Se comprueban:

- Duración configurada de 30 minutos.
- Expiración después de 30 minutos de inactividad.
- Renovación del tiempo mediante actividad.
- Expiración en el límite exacto.
- Invalidación de la sesión.
- Actualización de actividad mediante solicitudes API.
- Renovación mediante solicitudes API.
- Expiración mediante solicitudes API.

### Resultado de las pruebas

Última ejecución completa:

```text
Tests: 27 passed (125 assertions)
```

Este resultado incluye las pruebas de ejemplo iniciales de Laravel.

La suite completa se ejecuta mediante:

```bash
php artisan test
```

Cada nuevo requerimiento funcional deberá contar con sus respectivas pruebas antes de considerarse terminado.

## 8. Problemas encontrados y soluciones

### Error 419 – CSRF Token Mismatch

**Problema:** Laravel rechazaba la solicitud `POST /login` porque el navegador no enviaba correctamente las cookies y el encabezado CSRF.

**Causa identificada:** Vue y Laravel utilizaban hostnames diferentes (`localhost` y `gymflow.test`), lo que dificultaba el manejo de las cookies desde Axios.

**Solución:** se configuró Vue CLI para utilizar `gymflow.test` en el puerto 8080, conservando Laravel en el mismo hostname y puerto predeterminado.

También se ajustaron CORS y los dominios stateful de Sanctum.

### Error al acceder a la sesión desde solicitudes API

**Problema:** el middleware de expiración generaba `Session store not set on request`.

**Causa:** Sanctum no reconocía el origen utilizado por las pruebas automatizadas como un dominio stateful.

**Solución:** se agregó `SANCTUM_STATEFUL_DOMAINS=gymflow.test:8080` a `.env.testing`.

Después de este cambio, las pruebas API pudieron ejecutarse correctamente.

### Diferencias entre Vue CLI y Vite

**Problema:** TypeScript no reconocía `import.meta.env`.

**Causa:** el proyecto creado utiliza Vue CLI y no Vite.

**Solución:** se utilizó `process.env.VUE_APP_API_URL`, que corresponde al mecanismo de variables de entorno de Vue CLI.

## 9. Consideraciones para producción

La configuración de desarrollo local no debe trasladarse directamente a producción.

Antes del despliegue se deben revisar:

- Uso de HTTPS.
- Dominios stateful de Sanctum.
- Orígenes permitidos en CORS.
- Configuración de cookies seguras y de dominio.
- Almacenamiento compartido y persistente para los limitadores de solicitudes.
- Configuración de proxies de confianza.
- Variables de entorno y credenciales de producción.