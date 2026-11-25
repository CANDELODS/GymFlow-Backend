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
- [x] **RF7 -- Registrar cliente:** registro de clientes mediante sus datos personales y de contacto, validación de la autorización para el tratamiento de datos personales y asignación automática del estado activo. Permite registrar opcionalmente la primera membresía del cliente en la misma solicitud, reutilizando las reglas y la lógica de registro de membresías adquiridas. Si los datos de la membresía no son válidos, el cliente permanece registrado.
- [x] **RF8 -- Consultar clientes:** consulta de clientes registrados para dueños y empleados, con búsqueda por nombres, apellidos, documento y teléfono mediante coincidencias parciales. Incluye filtros por estado del cliente y estado de la última membresía adquirida, clasificación automática de membresías según su fecha de vencimiento y paginación de cinco registros por página.
- [x] **RF9 -- Actualizar y gestionar estado del cliente:** actualización parcial de nombres, apellidos, documento y teléfono, con validación de unicidad del documento. Permite modificar opcionalmente el estado durante la actualización y también habilitar o deshabilitar clientes mediante un endpoint específico. Conserva la autorización para el tratamiento de datos personales y los registros históricos de membresías. Acceso para dueños y empleados.
- [x] **RF10 -- Consultar información de membresía del cliente:** consulta de la membresía vigente y del historial de adquisiciones de un cliente específico. Presenta la membresía vigente de forma independiente del historial, clasifica el estado de cada adquisición según su fecha de vencimiento y conserva la información del usuario administrativo responsable del registro. El historial se ordena desde la adquisición más reciente y se pagina de cinco registros por página. Permite consultar clientes inactivos y contempla respuestas informativas cuando no existen membresías vigentes o adquisiciones registradas. Acceso para dueños y empleados.
- [x] **RF11 -- Registrar tipo de membresía:** registro de tipos de membresía mediante la definición de su nombre, duración y precio, con validación de unicidad, restricciones sobre los valores y habilitación automática del tipo registrado.
- [x] **RF12 -- Consultar tipos de membresía:** consulta de tipos registrados para dueños y empleados, con búsqueda por coincidencias parciales del nombre, filtros por estado y paginación de cinco registros por página.
- [x] **RF13 -- Actualizar y gestionar el estado de tipos de membresía:** actualización parcial o simultánea del nombre, duración, precio y estado; activación y desactivación mediante un endpoint específico. Acceso para dueños y empleados, con validación de datos y protección de cuentas inactivas.
- [x] **RF14 -- Registrar membresía adquirida:** registro de membresías para clientes activos mediante la selección de un tipo de membresía disponible, fecha de inicio, valor pagado y método de pago. El sistema calcula automáticamente la fecha de vencimiento y conserva snapshots del nombre y duración del tipo de membresía para proteger el historial de las adquisiciones. Incluye control de concurrencia durante el registro y la desactivación de tipos de membresía.

### En desarrollo / pendientes

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
│   ├── Cliente/
│   ├── Fortify/
│   ├── MembresiaAdquirida/
│   ├── TipoMembresia/
│   └── UsuarioAdministrativo/
│
├── Rules/
│
└── Http/
    ├── Controllers/
    ├── Middleware/
    ├── Requests/
    │   ├── Auth/
    │   ├── Cliente/
    │   ├── MembresiaAdquirida/
    │   ├── TipoMembresia/
    │   └── UsuarioAdministrativo/
    └── Resources/

tests/
├── Feature/
│   ├── Auth/
│   ├── Cliente/
│   ├── MembresiaAdquirida/
│   ├── TipoMembresia/
│   └── UsuarioAdministrativo/
├── Support/
└── Unit/
```

### Responsabilidades principales

- **Actions:** concentran las operaciones y la lógica de aplicación, agrupadas según el módulo al que pertenecen.
- **Rules:** centralizan reglas de validación reutilizables entre diferentes componentes del sistema.
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
| [Membresías adquiridas](docs/modulos/membresias-adquiridas.md) | Implementación, componentes, reglas de negocio, validaciones y pruebas del módulo de registro de membresías adquiridas. |
| [Clientes](docs/modulos/cliente.md) | Implementación, componentes, validaciones, autorización, registro, consulta, actualización, gestión del estado, consulta de membresías, filtros y pruebas del módulo de clientes. |

La documentación se actualizará conforme se implementen y validen nuevos requerimientos funcionales.

## Requisitos

Para ejecutar el proyecto localmente se necesitan:

- PHP 8.4.1 o superior.
- Laravel Herd, para gestionar el entorno local de PHP y ejecutar los comandos del proyecto.
- Composer 2.
- MySQL 8.
- Node.js y npm.
- Git.

También se requiere disponer de una instancia de MySQL y tener configurado el entorno local de Laravel Herd con una versión de PHP compatible.

## Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/CANDELODS/GymFlow-Backend.git
cd GymFlow-Backend
```

### 2. Crear y configurar la base de datos

Antes de ejecutar el instalador, crear la base de datos MySQL que utilizará el proyecto. Por defecto, el archivo `.env.example` está configurado para utilizar una base de datos llamada `gymflow`.

Si se utiliza otro nombre, se deberá configurar la variable DB_DATABASE en el archivo .env durante el siguiente paso.

### 3. Configurar variables de entorno

Crear el archivo `.env` a partir de `.env.example`:

```bash
copy .env.example .env
```

Configurar las variables de conexión a la base de datos según el entorno local:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gymflow
DB_USERNAME=root
DB_PASSWORD=
```

Ajustar las credenciales según la configuración de MySQL utilizada.

### 4. Instalar y preparar el proyecto

Una vez creada la base de datos y configurado el archivo `.env`, ejecutar:

```bash
herd composer run setup
```

Este comando automatiza la preparación inicial del proyecto mediante las siguientes operaciones:

1. Instala las dependencias de PHP utilizando Composer.
2. Crea el archivo `.env` a partir de `.env.example` si todavía no existe.
3. Genera la clave de aplicación de Laravel.
4. Ejecuta las migraciones y los seeders iniciales.
5. Instala las dependencias de JavaScript mediante npm.
6. Compila los recursos del frontend con Vite.

### Datos iniciales

El proyecto incluye factories y seeders para generar información ficticia que facilita el desarrollo y las pruebas manuales del backend.

La carga inicial contempla:

| Entidad | Registros |
|---|---:|
| Usuarios administrativos | 4 |
| Clientes | 20 |
| Tipos de membresía | 4 |
| Membresías adquiridas | 30 |

Los datos generados incluyen clientes sin membresía y clientes con membresías vencidas, próximas a vencer y activas. También se generan adquisiciones históricas para facilitar la consulta de los registros.

> **Importante:** estos datos son exclusivamente para entornos de desarrollo. No deben utilizarse como información real ni cargarse en producción.

> **Precaución:** el comando `setup` está diseñado para la instalación inicial. No debe ejecutarse repetidamente sobre una instalación existente, ya que vuelve a ejecutar los seeders y genera nuevamente la clave de aplicación.

Para reconstruir completamente la base de datos y volver a cargar los datos iniciales durante el desarrollo, se puede ejecutar:

```bash
herd php artisan migrate:fresh --seed
```

Este comando elimina todas las tablas existentes antes de ejecutar nuevamente las migraciones y los seeders. Debe utilizarse con precaución, ya que elimina la información almacenada en la base de datos.

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
Tests: 278 passed (1299 assertions)
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
- Registro de clientes por parte de dueños y empleados.
- Validación de los datos personales y de contacto, unicidad del documento y autorización para el tratamiento de datos personales.
- Asignación automática del estado activo y registro de la fecha de autorización por parte del backend.
- Registro opcional de la primera membresía durante la creación del cliente.
- Validación de los datos de membresía y conservación del cliente cuando la membresía no puede registrarse.
- Protección del registro de clientes frente a usuarios no autenticados y cuentas inactivas.
- Consulta de clientes por parte de dueños y empleados.
- Protección de la consulta frente a usuarios no autenticados, cuentas inactivas y roles no autorizados.
- Búsqueda de clientes por nombres, apellidos, documento y teléfono mediante coincidencias parciales.
- Filtrado de clientes por estado activo o inactivo.
- Filtrado por estado de la última membresía adquirida: activa, por vencer, vencida o sin membresía.
- Clasificación automática del estado de membresía según la fecha de vencimiento, incluyendo los límites de diez días.
- Consulta de la última membresía adquirida de cada cliente.
- Paginación de cinco registros por página y conservación de filtros entre páginas.
- Validación de parámetros de búsqueda, filtros y paginación.
- Combinación de filtros y respuestas vacías cuando no existen clientes o coincidencias.
- Actualización parcial de información de clientes y modificación simultánea de sus campos editables.
- Validación de campos obligatorios, tipos de datos y longitudes máximas durante la actualización de clientes.
- Validación de unicidad del documento, permitiendo conservar el documento actual del cliente.
- Actualización opcional del estado durante la modificación de información.
- Protección de los datos de autorización para el tratamiento de datos personales durante la actualización.
- Habilitación y deshabilitación de clientes mediante una operación independiente.
- Restricción del cambio de estado a la modificación exclusiva del atributo `activo`.
- Protección de las operaciones de actualización y cambio de estado frente a usuarios no autenticados y cuentas inactivas.
- Conservación de los datos personales y los registros históricos de membresías al actualizar o cambiar el estado de un cliente.
- Consulta de la membresía vigente y del historial de adquisiciones de un cliente específico.
- Protección de la consulta de membresías frente a usuarios no autenticados, cuentas inactivas y roles no autorizados.
- Consulta del historial de membresías de clientes inactivos.
- Presentación independiente de la membresía vigente y exclusión de esta del historial para evitar duplicaciones.
- Clasificación automática del estado de las membresías adquiridas: activa, por vencer o vencida.
- Conservación de la información del usuario administrativo responsable de cada adquisición, incluso cuando su cuenta está inactiva.
- Ordenamiento del historial desde la adquisición más reciente y paginación de cinco registros por página.
- Validación de los parámetros de paginación y respuestas informativas cuando no existen membresías vigentes o adquisiciones registradas.
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
- Registro de membresías adquiridas por dueños y empleados.
- Validación de clientes y tipos de membresía existentes y activos.
- Validación de fechas de inicio, valores pagados y métodos de pago permitidos.
- Cálculo automático de la fecha de vencimiento según la duración del tipo de membresía.
- Conservación de snapshots del nombre y duración del tipo de membresía al momento de la adquisición.
- Persistencia de los datos históricos de una adquisición al modificar o desactivar su tipo de membresía.
- Validación de fechas de vencimiento en cambios de mes y año, y de membresías con duración de un día.
- Control de concurrencia entre el registro de una membresía adquirida y la desactivación de su tipo de membresía.

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
- **RF7:** Registrar cliente.
- **RF8:** Consultar clientes.
- **RF9:** Actualizar y gestionar el estado del cliente.
- **RF10:** Consultar información de membresía del cliente.
- **RF11:** Registrar tipo de membresía.
- **RF12:** Consultar tipos de membresía.
- **RF13:** Actualizar y gestionar el estado de tipos de membresía.
- **RF14:** Registrar membresía adquirida.

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