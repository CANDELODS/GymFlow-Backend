# GymFlow

**Sistema web de gestión administrativa para gimnasios.**

GymFlow es una aplicación web orientada a centralizar y optimizar la gestión administrativa de un gimnasio, facilitando el control de clientes, membresías, pagos y operaciones administrativas.

El proyecto está desarrollado con una arquitectura que separa el backend y el frontend, buscando mantener una estructura organizada, escalable y fácil de mantener.

> **Estado del proyecto:** En desarrollo.

---

## Contenido

- [Objetivo del proyecto](#objetivo-del-proyecto)
- [Funcionalidades](#funcionalidades)
- [Tecnologías](#tecnologías)
- [Arquitectura](#arquitectura)
- [Estructura del backend](#estructura-del-backend)
- [Documentación](#documentación)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Pruebas](#pruebas)
- [Estado del desarrollo](#estado-del-desarrollo)

## Objetivo del proyecto

Desarrollar una solución que permita administrar las operaciones de un gimnasio desde una plataforma centralizada, mejorando el control de la información y facilitando el seguimiento de sus actividades.

GymFlow está diseñado para ser utilizado exclusivamente por usuarios administrativos, contemplando dos roles:

- **Dueño:** acceso a funcionalidades administrativas y de gestión.
- **Empleado:** acceso a funcionalidades operativas según sus permisos.

Los clientes del gimnasio no necesitan cuentas de acceso al sistema.

## Funcionalidades

El desarrollo se organiza mediante requerimientos funcionales (RF), implementados y validados progresivamente.

### Implementadas

- [x] **RF1 -- Inicio de sesión:** autenticación de usuarios administrativos, validación de credenciales, control de intentos fallidos y bloqueos temporales.
- [x] **RF2 -- Cierre de sesión:** finalización de la sesión autenticada, invalidación de la sesión y protección frente al acceso posterior sin autenticación.
- [x] **RF3 -- Cambio de contraseña:** verificación de la contraseña actual, validación de la nueva contraseña según la política de seguridad, almacenamiento mediante hashing e invalidación de las sesiones anteriores.
- [x] **Gestión de sesiones:** expiración automática después de 30 minutos de inactividad.
- [x] **RF4 -- Registrar usuario administrativo:** registro de nuevas cuentas administrativas, asignación de roles, selección del estado inicial y validación de los datos mediante reglas de seguridad.
- [x] **RF5 -- Consultar usuarios administrativos:** consulta de cuentas administrativas, búsqueda por nombre de usuario mediante coincidencias parciales y paginación de cinco registros por página. Acceso restringido al rol Dueño.
- [x] **RF6 -- Actualizar y gestionar estado de usuarios administrativos:** actualización parcial o simultánea del nombre de usuario, rol, contraseña y estado de activación mediante el endpoint de actualización. También permite activar y desactivar cuentas mediante un endpoint específico. Incluye protección del último dueño activo, restricción de auto-desactivación y control de acceso de cuentas inactivas.
- [x] **RF11 -- Registrar tipo de membresía:** registro de tipos de membresía mediante la definición de su nombre, duración y precio, con validación de unicidad, restricciones sobre los valores y habilitación automática del tipo registrado.
- [x] **RF12 -- Consultar tipos de membresía:** consulta de tipos registrados para dueños y empleados, con búsqueda por coincidencias parciales del nombre, filtros por estado y paginación de cinco registros por página.
- [x] **RF13 -- Actualizar y gestionar el estado de tipos de membresía:** actualización parcial o simultánea del nombre, duración, precio y estado; activación y desactivación mediante un endpoint específico. Acceso para dueños y empleados, con validación de datos y protección de cuentas inactivas.

### En desarrollo / pendientes

- [ ] Gestión de clientes.
- [ ] Gestión de membresías adquiridas.
- [ ] Registro y auditoría de modificaciones.
- [ ] Dashboard y reportes administrativos.

Las funcionalidades pendientes se incorporarán progresivamente conforme avance el desarrollo y se validen sus respectivos requerimientos.

## Tecnologías

| Área | Tecnologías |
|---|---|
| Backend | PHP, Laravel 13 |
| Frontend | Vue, TypeScript |
| Base de datos | MySQL 8 |
| Autenticación | Laravel Fortify, Laravel Sanctum |
| Comunicación HTTP | Axios |
| Control de versiones | Git |

## Arquitectura

GymFlow está compuesto por dos aplicaciones independientes, mantenidas en repositorios separados:

- **Backend (GymFlow-Backend):** API REST desarrollada con Laravel, responsable de la lógica de negocio, validaciones, autenticación y persistencia de información.
- **Frontend (GymFlow-Frontend):** aplicación SPA desarrollada con Vue y TypeScript, responsable de la interfaz y la interacción con los usuarios.

Ambas aplicaciones se comunican mediante solicitudes HTTP, manteniendo una separación de responsabilidades que permite desarrollar, probar y desplegar cada componente de manera independiente.

En el backend se busca mantener una separación clara de responsabilidades, aplicando principios SOLID y buenas prácticas de desarrollo cuando aporten valor a la solución.

## Estructura del backend

El backend organiza sus componentes por responsabilidad y módulo funcional. Esta estructura facilita localizar la lógica de aplicación, las validaciones y las pruebas, manteniendo separados los distintos contextos del sistema.

```text
app/
├── Actions/
│   ├── Auth/
│   ├── Fortify/
│   ├── TipoMembresia/
│   └── UsuarioAdministrativo/
│
└── Http/
    ├── Controllers/
    ├── Middleware/
    ├── Requests/
    │   ├── Auth/
    │   ├── TipoMembresia/
    │   └── UsuarioAdministrativo/
    └── Resources/

tests/
├── Feature/
│   ├── Auth/
│   ├── TipoMembresia/
│   └── UsuarioAdministrativo/
├── Support/
└── Unit/
```

### Responsabilidades principales

- **Actions:** concentran las operaciones y la lógica de aplicación, agrupadas según el módulo al que pertenecen.
- **Controllers:** reciben las solicitudes HTTP, coordinan las acciones correspondientes y construyen las respuestas de la API.
- **Middleware:** implementa controles transversales, como la verificación del estado de las cuentas y la autorización de acceso.
- **Requests:** contienen las reglas de validación y autorización de las solicitudes, organizadas por módulo.
- **Resources:** definen la estructura de los datos de los modelos que se exponen en las respuestas JSON.
- **tests/Feature:** agrupa las pruebas de integración por módulo funcional.
- **tests/Support:** contiene utilidades auxiliares utilizadas por las pruebas.
- **tests/Unit:** contiene pruebas unitarias.

Los controladores, middleware y recursos se mantienen en sus directorios comunes mientras su tamaño y responsabilidades no requieran una subdivisión adicional.

## Documentación

La documentación técnica del proyecto se encuentra en la carpeta [`docs/`](docs/).

| Documento | Descripción |
|---|---|
| [Autenticación](docs/modulos/autenticacion.md) | Implementación, componentes, configuraciones, reglas de seguridad y pruebas del módulo de autenticación. |
| [Administración de usuarios](docs/modulos/administracion-usuarios.md) | Implementación, componentes, validaciones, autorización y pruebas del módulo de administración de usuarios administrativos. |
| [ADR-001: Autenticación con Sanctum](docs/decisiones/001-autenticacion-sanctum.md) | Decisiones arquitectónicas que justifican el mecanismo de autenticación seleccionado. |
| [Tipos de membresía](docs/modulos/tipos-membresia.md) | Implementación, componentes, validaciones, autorización y pruebas del módulo de gestión de tipos de membresía. |

La documentación se actualizará conforme se implementen y validen nuevos requerimientos funcionales.

## Requisitos

Para ejecutar el proyecto localmente se necesitan:

- PHP 8.3 o superior.
- Composer.
- MySQL 8.
- Node.js y npm.
- Laravel 13.
- Laravel Herd para el entorno local utilizado durante el desarrollo.

También se requiere configurar el entorno local de Laravel y disponer de una instancia de MySQL.

## Instalación

### 1. Clonar el repositorio

```bash
git clone <URL_DEL_REPOSITORIO>
cd gymflow
```

### 2. Instalar dependencias del backend

```bash
composer install
```

### 3. Configurar variables de entorno

Crear el archivo `.env` a partir de `.env.example`:

```bash
cp .env.example .env
```

Configurar las variables de conexión a la base de datos y los demás parámetros necesarios para el entorno local.

Generar la clave de aplicación:

```bash
herd php artisan key:generate
```

### 4. Preparar la base de datos

Crear la base de datos configurada en `.env` y ejecutar las migraciones:

```bash
herd php artisan migrate
```

### 5. Ejecutar el backend

El proyecto utiliza Laravel Herd para servir la aplicación localmente. Una vez configurado el sitio en Herd, el backend estará disponible en el dominio local configurado.

> **Nota:** la configuración completa de desarrollo y la instalación del frontend se documentarán cuando se prepare el entorno de ejecución para nuevos desarrolladores.

## Pruebas

El backend utiliza PHPUnit para verificar el comportamiento de sus funcionalidades.

Las pruebas utilizan el entorno de configuración de Laravel identificado como `testing`. La conexión a la base de datos de pruebas se define en el archivo `.env.testing`. Este archivo contiene las variables específicas del entorno de pruebas y no debe incluirse en el repositorio si contiene credenciales.

El archivo `phpunit.xml` conserva la configuración general de PHPUnit y establece el entorno `testing`; las variables de conexión a la base de datos se obtienen de `.env.testing`.

Para ejecutar la suite completa:

```bash
herd php artisan test
```

Último resultado registrado:

```text
Tests: 159 passed (693 assertions)
```

Las pruebas actuales incluyen:

- Validación de credenciales y autenticación.
- Control de intentos fallidos y bloqueos temporales.
- Cierre de sesión e invalidación de sesiones.
- Protección de rutas frente a usuarios no autenticados.
- Expiración de sesiones por inactividad.
- Cambio de contraseña y validación de sus reglas.
- Almacenamiento seguro de contraseñas mediante hashing.
- Rechazo de sesiones que conservan un hash de contraseña anterior.
- Registro de usuarios administrativos y asignación de roles.
- Protección de las operaciones administrativas según el rol.
- Validación de datos y política de contraseñas durante el registro.
- Protección de información sensible en las respuestas de la API.
- Consulta y búsqueda de usuarios administrativos.
- Paginación de resultados y conservación de filtros entre páginas.
- Validación de parámetros de búsqueda y paginación.
- Restricción de consultas y modificaciones administrativas según el rol.
- Actualización parcial y simultánea de los atributos de usuarios administrativos, incluyendo nombre de usuario, rol, contraseña y estado de activación.
- Validación de contraseñas y confirmación durante la actualización, así como conservación de la contraseña actual cuando no se envía una nueva.
- Activación y desactivación de cuentas administrativas mediante la actualización general y el endpoint específico de cambio de estado.
- Validación del estado de activación y conservación del estado actual cuando no se envía en la solicitud.
- Restricción de la auto-desactivación y protección del último dueño activo.
- Protección de las operaciones administrativas frente a usuarios no autenticados, empleados y cuentas inactivas.
- Revocación del acceso de sesiones pertenecientes a cuentas desactivadas.
- Registro de tipos de membresía para dueños y empleados.
- Validación de datos, unicidad del nombre, longitud y precisión decimal de los tipos de membresía.
- Protección del registro de tipos de membresía frente a usuarios no autenticados e inactivos.
- Consulta de tipos de membresía por parte de dueños y empleados.
- Búsqueda por coincidencias parciales del nombre y filtros por estado activo o inactivo.
- Paginación de cinco registros por página y conservación de filtros entre páginas.
- Validación de parámetros de búsqueda, estado y paginación.
- Actualización parcial y simultánea de los atributos de los tipos de membresía.
- Activación y desactivación de tipos de membresía para dueños y empleados.
- Protección de las operaciones de actualización frente a usuarios no autenticados e inactivos.
- Validación del estado y conservación de los demás atributos cuando no se modifican.
- Respuestas vacías cuando no existen registros o no se encuentran coincidencias.

Cada nuevo requerimiento funcional deberá contar con sus respectivas pruebas antes de considerarse terminado.

## Estado del desarrollo

GymFlow se encuentra en una etapa de desarrollo progresivo.

Actualmente se han completado la implementación y validación de los siguientes requerimientos funcionales:

- **RF1:** Inicio de sesión.
- **RF2:** Cierre de sesión.
- **RF3:** Cambio de contraseña.
- **RF4:** Registrar usuario administrativo.
- **RF5:** Consultar usuarios administrativos.
- **RF6:** Actualizar y gestionar el estado de usuarios administrativos.
- **RF11:** Registrar tipo de membresía.
- **RF12:** Consultar tipos de membresía.
- **RF13:** Actualizar y gestionar el estado de tipos de membresía.

También se encuentra implementada y probada la gestión de expiración de sesiones por inactividad.

Los siguientes módulos se desarrollarán siguiendo el mismo proceso:

1. Análisis del requerimiento.
2. Diseño de la solución.
3. Implementación.
4. Pruebas automatizadas.
5. Documentación técnica.
6. Validación y cierre del requerimiento.

---

**GymFlow** · Proyecto de desarrollo de software.