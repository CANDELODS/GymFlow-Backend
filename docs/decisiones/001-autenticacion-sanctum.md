# ADR-001: Autenticación SPA mediante sesiones con Fortify y Sanctum

- **Estado:** Aceptada
- **Fecha:** 2026-11-02
- **Ámbito:** Arquitectura de autenticación
- **Proyecto:** GymFlow

## 1. Contexto

GymFlow requiere un mecanismo de autenticación que permita controlar el acceso de los usuarios administrativos a la aplicación.

El sistema cuenta con dos roles administrativos: `dueno` y `empleado`. Los clientes del gimnasio no tienen cuentas de acceso.

La arquitectura del proyecto separa el backend desarrollado con Laravel 13 del frontend desarrollado con Vue y TypeScript.

Por esta razón, necesitamos un mecanismo que permita autenticar solicitudes provenientes de una aplicación frontend independiente, manteniendo una gestión segura de sesiones y credenciales.

Adicionalmente, la autenticación debe contemplar reglas específicas de seguridad:

- Acceso mediante usuario y contraseña.
- Rechazo de usuarios inexistentes o desactivados.
- Bloqueo temporal después de cinco intentos fallidos.
- Limitación de solicitudes de autenticación.
- Expiración de sesiones después de 30 minutos de inactividad.

## 2. Problema

Era necesario seleccionar e implementar un mecanismo de autenticación que cumpliera con las necesidades de GymFlow y permitiera mantener una separación adecuada de responsabilidades.

También necesitábamos adaptar el proceso de autenticación proporcionado por Laravel a nuestro modelo `UsuarioAdministrativo`, ya que el sistema utiliza el campo `usuario` en lugar del campo `email` utilizado habitualmente en los ejemplos convencionales de Laravel.

La solución debía permitir personalizar las validaciones y reglas de autenticación sin modificar directamente el código fuente de Laravel o sus paquetes.

## 3. Decisión

Se decidió implementar autenticación SPA mediante sesiones y cookies utilizando:

- **Laravel Fortify:** gestión del proceso de autenticación.
- **Laravel Sanctum:** reconocimiento y autenticación de solicitudes SPA mediante sesiones.
- **Laravel Session:** administración de sesiones.
- **Axios:** comunicación HTTP entre Vue y Laravel.

Se descartó utilizar tokens personales de Sanctum como mecanismo principal de autenticación del frontend.

La decisión busca aprovechar los mecanismos de autenticación y protección CSRF proporcionados por Laravel, evitando implementar manualmente la gestión de tokens de acceso.

## 4. Decisiones de implementación

### 4.1. Modelo de usuario administrativo personalizado

Se creó el modelo `UsuarioAdministrativo`, asociado a la tabla `usuario_administrativo`.

Este modelo extiende `Illuminate\Foundation\Auth\User` mediante la clase `Authenticatable`, permitiendo que Laravel lo utilice como entidad autenticable.

Se configuraron:

- Tabla personalizada.
- Clave primaria `id_usuario`.
- Atributos asignables.
- Contraseña oculta en las serializaciones.
- Cast `hashed` para el almacenamiento seguro de contraseñas.

### 4.2. Request personalizado para el inicio de sesión

Se creó `UsuarioAdministrativoLoginRequest`, que extiende el `LoginRequest` proporcionado por Laravel Fortify.

La finalidad fue adaptar las reglas de validación al sistema de autenticación de GymFlow.

Entre las personalizaciones realizadas se encuentran:

- Utilizar `usuario` como identificador de acceso.
- Validar la longitud del usuario y la contraseña.
- Definir mensajes de validación en español.
- Mantener las reglas de validación independientes de la implementación interna del paquete.

Para que Fortify utilice nuestra implementación, se registró una vinculación en `FortifyServiceProvider` mediante el contenedor de servicios de Laravel:

```php
$this->app->bind(
    LoginRequest::class,
    UsuarioAdministrativoLoginRequest::class
);
```

De esta manera, cuando el framework solicita una instancia de `LoginRequest`, el contenedor proporciona `UsuarioAdministrativoLoginRequest`.

Esta decisión permite personalizar el proceso de validación sin modificar las clases originales del framework.

### 4.3. Separación del proceso de autenticación

Se creó `AutenticarUsuarioAdministrativo` para centralizar la lógica de autenticación.

Esta clase delega la administración de intentos fallidos y bloqueos temporales a `GestionarIntentosFallidos`.

Se utilizó inyección de dependencias para entregar esta última clase mediante el constructor, evitando instanciarla directamente dentro de la acción de autenticación.

También se implementaron transacciones de base de datos y bloqueos de filas mediante `lockForUpdate()` para coordinar las actualizaciones de los intentos fallidos.

### 4.4. Configuración de Fortify

Se configuró `FortifyServiceProvider` para:

- Registrar el binding del Request personalizado.
- Definir el limitador de solicitudes de autenticación.
- Delegar la autenticación a `AutenticarUsuarioAdministrativo`.

Se deshabilitaron las funcionalidades opcionales de registro público, recuperación de contraseña, verificación de correo y autenticación de dos factores, al no formar parte del alcance actual de GymFlow.

### 4.5. Integración con Sanctum

Se habilitó `statefulApi()` en `bootstrap/app.php` para permitir que las solicitudes API provenientes del frontend utilicen la autenticación basada en sesiones y cookies.

Se configuraron los dominios stateful y CORS para permitir la comunicación entre Vue y Laravel.

La protección CSRF se mantiene habilitada para las solicitudes que modifican información.

### 4.6. Expiración de sesiones por inactividad

Se estableció una duración máxima de 30 minutos de inactividad.

Para implementar esta regla se creó `VerificarExpiracionSesion`, registrado en los grupos de middleware `web` y `api`.

El middleware almacena y consulta la clave `ultima_actividad` dentro de la sesión.

Cada solicitud autenticada válida actualiza dicha marca de tiempo. Cuando se alcanzan los 30 minutos de inactividad, se cierra la autenticación, se invalida la sesión y se regenera el token CSRF.

## 5. Alternativas consideradas

### 5.1. Autenticación mediante tokens personales

Sanctum permite autenticar solicitudes mediante tokens personales.

Sin embargo, se descartó esta alternativa para el frontend de GymFlow porque se decidió utilizar sesiones y cookies para la autenticación SPA.

Esto permite aprovechar el mecanismo de sesiones de Laravel y su protección CSRF, sin tener que gestionar manualmente tokens personales de acceso.

### 5.2. Implementar la autenticación completamente de forma personalizada

Otra alternativa era construir desde cero el proceso de autenticación.

Se descartó porque Laravel ya proporciona mecanismos consolidados para gestionar sesiones, autenticación y protección CSRF.

Se decidió aprovechar Fortify y Sanctum, personalizando únicamente los componentes necesarios para cumplir los requisitos del proyecto.

## 6. Consecuencias

### Positivas

- Separación de responsabilidades entre el proceso de autenticación y las reglas específicas de GymFlow.
- Aprovechamiento de las funcionalidades nativas de Laravel.
- Personalización de las validaciones sin modificar el código interno de Fortify.
- Gestión de sesiones mediante cookies.
- Protección CSRF.
- Control centralizado de la expiración por inactividad.
- Facilidad para realizar pruebas automatizadas.

### Consideraciones y responsabilidades adicionales

- Es necesario mantener correctamente configurados CORS y los dominios stateful.
- El frontend debe enviar las cookies y el token CSRF mediante Axios.
- Las sesiones deben almacenarse en un mecanismo adecuado para el entorno de despliegue.
- Los limitadores de solicitudes deben utilizar un almacenamiento persistente y compartido en producción cuando existan múltiples instancias.
- Deben configurarse correctamente HTTPS, cookies seguras y proxies de confianza en producción.

## 7. Validación de la decisión

La implementación fue comprobada mediante pruebas automatizadas de autenticación y sesiones.

Se verificaron escenarios de:

- Autenticación exitosa y fallida.
- Usuarios inexistentes y desactivados.
- Bloqueos temporales.
- Limitación de solicitudes.
- Cierre de sesión.
- Expiración y renovación de sesiones.
- Autenticación mediante solicitudes API.

La ejecución completa de la suite registró:

```text
Tests: 23 passed (112 assertions)
```

También se realizó una prueba manual desde el navegador, comprobando que una solicitud a `/api/user` devuelve HTTP 401 después de superar los 30 minutos de inactividad.

## 8. Referencias internas

- `docs/modulos/autenticacion.md`
- `app/Models/UsuarioAdministrativo.php`
- `app/Http/Requests/UsuarioAdministrativoLoginRequest.php`
- `app/Actions/Auth/AutenticarUsuarioAdministrativo.php`
- `app/Actions/Auth/GestionarIntentosFallidos.php`
- `app/Providers/FortifyServiceProvider.php`
- `app/Http/Middleware/VerificarExpiracionSesion.php`
- `bootstrap/app.php`
- `tests/Feature/UsuarioAdministrativoLoginTest.php`
- `tests/Feature/UsuarioAdministrativoSesionTest.php`