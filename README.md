# GymFlow

**Sistema web de gestión administrativa para gimnasios.**

GymFlow es una aplicación web orientada a centralizar y optimizar la
gestión administrativa de un gimnasio, facilitando el control de
clientes, membresías, pagos y operaciones administrativas.

El proyecto está desarrollado con una arquitectura que separa el backend
y el frontend, buscando mantener una estructura organizada, escalable y
fácil de mantener.

> **Estado del proyecto:** En desarrollo.

------------------------------------------------------------------------

## Contenido

-   [Objetivo del proyecto](#objetivo-del-proyecto)
-   [Funcionalidades](#funcionalidades)
-   [Tecnologías](#tecnologías)
-   [Arquitectura](#arquitectura)
-   [Documentación](#documentación)
-   [Requisitos](#requisitos)
-   [Instalación](#instalación)
-   [Pruebas](#pruebas)
-   [Estado del desarrollo](#estado-del-desarrollo)

## Objetivo del proyecto

Desarrollar una solución que permita administrar las operaciones de un
gimnasio desde una plataforma centralizada, mejorando el control de la
información y facilitando el seguimiento de sus actividades.

GymFlow está diseñado para ser utilizado exclusivamente por usuarios
administrativos, contemplando dos roles:

-   **Dueño:** acceso a funcionalidades administrativas y de gestión.
-   **Empleado:** acceso a funcionalidades operativas según sus
    permisos.

Los clientes del gimnasio no necesitan cuentas de acceso al sistema.

## Funcionalidades

El desarrollo se organiza mediante requerimientos funcionales (RF),
implementados y validados progresivamente.

### Implementadas

-   [x] **RF1 -- Inicio de sesión:** autenticación de usuarios
    administrativos, validación de credenciales, control de intentos
    fallidos y bloqueos temporales.
-   [x] **RF2 -- Cierre de sesión:** finalización de la sesión
    autenticada, invalidación de la sesión y protección frente al acceso
    posterior sin autenticación.
-   [x] **RF3 -- Cambio de contraseña:** verificación de la contraseña
    actual, validación de la nueva contraseña según la política de
    seguridad, almacenamiento mediante hashing e invalidación de las
    sesiones anteriores.
-   [x] **Gestión de sesiones:** expiración automática después de 30
    minutos de inactividad.

### En desarrollo / pendientes

-   [ ] Gestión de usuarios administrativos.
-   [ ] Gestión de clientes.
-   [ ] Gestión de tipos de membresía.
-   [ ] Gestión de membresías adquiridas.
-   [ ] Registro y auditoría de modificaciones.
-   [ ] Dashboard y reportes administrativos.

Las funcionalidades pendientes se incorporarán progresivamente conforme
avance el desarrollo y se validen sus respectivos requerimientos.

## Tecnologías

  Área                   Tecnologías
  ---------------------- ----------------------------------
  Backend                PHP, Laravel 13
  Frontend               Vue, TypeScript
  Base de datos          MySQL 8
  Autenticación          Laravel Fortify, Laravel Sanctum
  Comunicación HTTP      Axios
  Control de versiones   Git

## Arquitectura

GymFlow está compuesto por dos aplicaciones independientes, mantenidas
en repositorios separados:

-   **Backend (GymFlow-Backend):** API REST desarrollada con Laravel,
    responsable de la lógica de negocio, validaciones, autenticación y
    persistencia de información.
-   **Frontend (GymFlow-Frontend):** aplicación SPA desarrollada con Vue
    y TypeScript, responsable de la interfaz y la interacción con los
    usuarios.

Ambas aplicaciones se comunican mediante solicitudes HTTP, manteniendo
una separación de responsabilidades que permite desarrollar, probar y
desplegar cada componente de manera independiente.

En el backend se busca mantener una separación clara de
responsabilidades, aplicando principios SOLID y buenas prácticas de
desarrollo cuando aporten valor a la solución.

## Documentación

La documentación técnica del proyecto se encuentra en la carpeta
[`docs/`](docs/).

  --------------------------------------------------------------------------------------------
  Documento                                                Descripción
  -------------------------------------------------------- -----------------------------------
  [Autenticación](docs/modulos/autenticacion.md)           Implementación, componentes,
                                                           configuraciones, reglas de
                                                           seguridad y pruebas del módulo de
                                                           autenticación.

  [ADR-001: Autenticación con                              Decisiones arquitectónicas que
  Sanctum](docs/decisiones/001-autenticacion-sanctum.md)   justifican el mecanismo de
                                                           autenticación seleccionado.
  --------------------------------------------------------------------------------------------

La documentación se actualizará conforme se implementen y validen nuevos
requerimientos funcionales.

## Requisitos

Para ejecutar el proyecto localmente se necesitan:

-   PHP 8.3 o superior.
-   Composer.
-   MySQL 8.
-   Node.js y npm.
-   Laravel 13.
-   Laravel Herd para el entorno local utilizado durante el desarrollo.

También se requiere configurar el entorno local de Laravel y disponer de
una instancia de MySQL.

## Instalación

### 1. Clonar el repositorio

``` bash
git clone <URL_DEL_REPOSITORIO>
cd gymflow
```

### 2. Instalar dependencias del backend

``` bash
composer install
```

### 3. Configurar variables de entorno

Crear el archivo `.env` a partir de `.env.example`:

``` bash
cp .env.example .env
```

Configurar las variables de conexión a la base de datos y los demás
parámetros necesarios para el entorno local.

Generar la clave de aplicación:

``` bash
herd php artisan key:generate
```

### 4. Preparar la base de datos

Crear la base de datos configurada en `.env` y ejecutar las migraciones:

``` bash
herd php artisan migrate
```

### 5. Ejecutar el backend

El proyecto utiliza Laravel Herd para servir la aplicación localmente.
Una vez configurado el sitio en Herd, el backend estará disponible en el
dominio local configurado.

> **Nota:** la configuración completa de desarrollo y la instalación del
> frontend se documentarán cuando se prepare el entorno de ejecución
> para nuevos desarrolladores.

## Pruebas

El backend utiliza PHPUnit para verificar el comportamiento de sus
funcionalidades.

Para ejecutar la suite completa:

``` bash
herd php artisan test
```

Último resultado registrado:

``` text
Tests: 35 passed (156 assertions)
```

Las pruebas actuales incluyen:

-   Validación de credenciales y autenticación.
-   Control de intentos fallidos y bloqueos temporales.
-   Cierre de sesión e invalidación de sesiones.
-   Protección de rutas frente a usuarios no autenticados.
-   Expiración de sesiones por inactividad.
-   Cambio de contraseña y validación de sus reglas.
-   Almacenamiento seguro de contraseñas mediante hashing.
-   Rechazo de sesiones que conservan un hash de contraseña anterior.

Cada nuevo requerimiento funcional deberá contar con sus respectivas
pruebas antes de considerarse terminado.

## Estado del desarrollo

GymFlow se encuentra en una etapa de desarrollo progresivo.

Actualmente se han completado la implementación y validación de los tres
primeros requerimientos funcionales:

-   **RF1:** Inicio de sesión.
-   **RF2:** Cierre de sesión.
-   **RF3:** Cambio de contraseña.

También se encuentra implementada y probada la gestión de expiración de
sesiones por inactividad.

Los siguientes módulos se desarrollarán siguiendo el mismo proceso:

1.  Análisis del requerimiento.
2.  Diseño de la solución.
3.  Implementación.
4.  Pruebas automatizadas.
5.  Documentación técnica.
6.  Validación y cierre del requerimiento.

------------------------------------------------------------------------

**GymFlow** · Proyecto de desarrollo de software.