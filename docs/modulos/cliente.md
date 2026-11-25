# Módulo de gestión de clientes -- GymFlow

## 1. Objetivo y alcance

El módulo de gestión de clientes permite registrar, consultar, actualizar y gestionar el estado de los clientes del gimnasio, almacenando su información personal y de contacto, así como la autorización para el tratamiento de sus datos personales.

El registro contempla los nombres, apellidos, documento de identidad y teléfono del cliente. El sistema verifica que el documento no se encuentre registrado previamente y establece automáticamente el estado inicial del cliente como activo.

Una característica importante del módulo es la posibilidad de registrar la primera membresía del cliente durante el mismo proceso de creación. De esta manera, el usuario administrativo puede registrar al cliente y, opcionalmente, asociarle una membresía adquirida mediante una única solicitud HTTP.

El registro de la membresía es independiente de la creación del cliente. Si los datos de la membresía no cumplen las reglas de validación o se presenta un error durante su registro, el cliente permanece almacenado en la base de datos.

Adicionalmente, el módulo permite consultar los clientes registrados mediante búsqueda y filtros, facilitando la identificación de clientes y el seguimiento del estado de sus membresías adquiridas.

La consulta de clientes contempla la última membresía adquirida por cada cliente, cuyo estado se determina automáticamente según su fecha de vencimiento.

El módulo también permite consultar la información de membresías de un cliente específico. Esta operación presenta de manera independiente la membresía vigente, cuando existe, y el historial completo de adquisiciones del cliente, incluyendo el usuario administrativo responsable de cada registro.

El historial se ordena desde la adquisición más reciente y se presenta mediante paginación de cinco registros por página. Los clientes que no tienen una membresía vigente pueden consultar igualmente sus adquisiciones anteriores, incluso cuando se encuentran deshabilitados.

El módulo también permite actualizar la información de los clientes registrados y gestionar su estado. Los usuarios administrativos pueden modificar los nombres, apellidos, documento y teléfono, así como habilitar o deshabilitar un cliente.

La actualización de información permite modificar parcialmente los campos editables, conservando los valores de aquellos que no se envían. También permite modificar el estado del cliente durante la misma operación. Adicionalmente, existe una operación independiente para cambiar exclusivamente su estado.

Los clientes deshabilitados conservan su información personal y sus registros históricos de membresías adquiridas. Pueden habilitarse nuevamente cuando corresponda.

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
- Consulta de clientes por parte de dueños y empleados.
- Búsqueda por nombres, apellidos, documento y teléfono mediante coincidencias parciales.
- Filtrado por estado del cliente: activo o inactivo.
- Filtrado por estado de la última membresía adquirida: activa, por vencer, vencida o sin membresía.
- Clasificación automática del estado de la membresía según su fecha de vencimiento.
- Consulta de la última membresía adquirida de cada cliente.
- Paginación de cinco registros por página y conservación de filtros entre páginas.
- Validación de los parámetros de búsqueda, filtrado y paginación.
- Consulta de la membresía vigente y del historial de adquisiciones de un cliente específico.
- Separación de la membresía vigente y el historial para evitar mostrar dos veces la misma adquisición.
- Ordenamiento del historial desde la adquisición más reciente.
- Paginación del historial de cinco registros por página.
- Consulta de la información del usuario administrativo responsable de cada adquisición.
- Consulta del historial de membresías de clientes deshabilitados.
- Clasificación automática del estado de cada membresía adquirida.
- Validación del parámetro de paginación del historial.
- Actualización parcial de la información de clientes.
- Validación de unicidad del documento al actualizar un cliente, excluyendo su propio registro.
- Actualización opcional del estado durante la modificación de información.
- Habilitación y deshabilitación de clientes mediante una operación independiente.
- Conservación de la autorización para el tratamiento de datos personales y su fecha durante las actualizaciones.
- Conservación de los registros históricos de membresías al actualizar o cambiar el estado de un cliente.
- Posibilidad de habilitar nuevamente clientes previamente deshabilitados.
- Validación automatizada de los escenarios de registro, consulta, actualización, gestión del estado y consulta de membresías.

## 2. Tecnologías utilizadas

| Tecnología | Responsabilidad |
|---|---|
| Laravel 13 | Backend y gestión de solicitudes. |
| Laravel Sanctum | Autenticación de solicitudes API. |
| MySQL 8 | Almacenamiento de clientes y relaciones. |
| PHPUnit | Pruebas automatizadas. |
| Laravel Validation | Validación de los datos recibidos. |
| Laravel Eloquent | Persistencia de información mediante modelos. |
| Laravel Resources | Transformación y estructuración de las respuestas JSON. |

## 3. Componentes y responsabilidades

### Cliente

Archivo: `app/Models/Cliente.php`

Representa el modelo asociado a la tabla `cliente`.

El modelo permite gestionar la información de los clientes y sus relaciones con las membresías adquiridas.

Entre sus relaciones se encuentran:

- `membresiasAdquiridas()`: permite obtener las membresías adquiridas asociadas al cliente.
- `ultimaMembresiaAdquirida()`: permite obtener la última membresía adquirida del cliente mediante `ofMany()`.

La relación `ultimaMembresiaAdquirida()` utiliza `fecha_inicio` como criterio principal de selección y emplea `created_at` e `id_membresia_adquirida` como criterios adicionales de desempate.

Esta relación permite consultar la última adquisición sin recuperar innecesariamente todo el historial de membresías del cliente.

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

### ConsultarClientesRequest

Archivo: `app/Http/Requests/Cliente/ConsultarClientesRequest.php`

Valida y autoriza las solicitudes de consulta de clientes.

**Autorización:**

Permite realizar la operación a usuarios autenticados con rol `dueno` o `empleado`.

**Parámetros validados:**

- `buscar`: opcional, de tipo texto y con un máximo de 100 caracteres. Permite buscar clientes por nombres, apellidos, documento o teléfono.
- `activo`: opcional y debe contener un valor booleano válido. Permite filtrar clientes activos o inactivos.
- `estado_membresia`: opcional y debe corresponder a uno de los estados permitidos: `activa`, `por_vencer`, `vencida` o `sin_membresia`.
- `page`: opcional, debe ser un número entero mayor o igual a 1. Permite especificar la página que se desea consultar.

El Form Request centraliza la validación de los parámetros de consulta y evita que se ejecuten búsquedas con valores inválidos.

También proporciona mensajes de validación personalizados en español.

### ConsultarMembresiasClienteRequest

Archivo: `app/Http/Requests/Cliente/ConsultarMembresiasClienteRequest.php`

Valida y autoriza las solicitudes destinadas a consultar la información de membresías de un cliente específico.

**Autorización:**

Permite realizar la operación a usuarios autenticados con rol `dueno` o `empleado`.

**Parámetros validados:**

- `page`: opcional, debe ser un número entero mayor o igual a 1. Permite especificar la página del historial que se desea consultar.

El cliente se identifica mediante el parámetro de ruta `{cliente}`, resuelto por Laravel mediante model binding.

La consulta no exige que el cliente se encuentre activo. Esto permite consultar sus adquisiciones históricas incluso cuando su cuenta ha sido deshabilitada.

El Form Request centraliza la validación del parámetro de paginación y proporciona mensajes personalizados en español.

### ActualizarClienteRequest

Archivo: `app/Http/Requests/Cliente/ActualizarClienteRequest.php`

Valida y autoriza las solicitudes de actualización de información de clientes.

**Autorización:**

Permite realizar la operación a usuarios autenticados con rol `dueno` o `empleado`.

**Campos validados:**

- `nombres`: opcional, obligatorio cuando se envía, de tipo texto y con un máximo de 100 caracteres.
- `apellidos`: opcional, obligatorio cuando se envía, de tipo texto y con un máximo de 100 caracteres.
- `documento`: opcional, obligatorio cuando se envía, de tipo texto, con un máximo de 30 caracteres y único en la tabla `cliente`, excluyendo el registro que se está actualizando.
- `telefono`: opcional, obligatorio cuando se envía, de tipo texto y con un máximo de 20 caracteres.
- `activo`: opcional, obligatorio cuando se envía y debe ser un valor booleano válido.

El uso de la regla `sometimes` permite realizar actualizaciones parciales, conservando los valores de los campos que no se incluyen en la solicitud.

La autorización para el tratamiento de datos personales y su fecha no forman parte de los campos editables de esta operación.

También proporciona mensajes de validación personalizados en español.

### CambiarEstadoClienteRequest

Archivo: `app/Http/Requests/Cliente/CambiarEstadoClienteRequest.php`

Valida y autoriza las solicitudes destinadas exclusivamente a cambiar el estado de un cliente.

**Autorización:**

Permite realizar la operación a usuarios autenticados con rol `dueno` o `empleado`.

**Campos validados:**

- `activo`: obligatorio y debe contener un valor booleano válido.

Este Form Request exige que el estado sea enviado en cada solicitud, evitando cambios de estado sin especificar el nuevo valor.

También proporciona mensajes de validación personalizados en español.

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

### ConsultarClientes

Archivo: `app/Actions/Cliente/ConsultarClientes.php`

Centraliza la lógica de consulta de clientes y aplicación de filtros.

Responsabilidades:

1. Recibir los parámetros previamente validados.
2. Construir la consulta de clientes mediante Eloquent.
3. Cargar anticipadamente la relación `ultimaMembresiaAdquirida`.
4. Aplicar la búsqueda parcial en los campos nombres, apellidos, documento y teléfono.
5. Aplicar el filtro por estado del cliente cuando se proporciona el parámetro `activo`.
6. Aplicar el filtro por estado de la última membresía adquirida.
7. Ordenar los resultados alfabéticamente por nombres y apellidos.
8. Devolver los resultados paginados de cinco en cinco.
9. Conservar los parámetros de consulta mediante `withQueryString()`.

La búsqueda utiliza coincidencias parciales mediante `LIKE`, permitiendo encontrar clientes aunque el término ingresado corresponda solamente a una parte de sus datos.

El filtro por estado de membresía consulta exclusivamente la última adquisición del cliente.

Para determinar los estados se utiliza la fecha de vencimiento:

- `activa`: la fecha de vencimiento es posterior a los próximos diez días.
- `por_vencer`: la fecha de vencimiento se encuentra entre el día actual y los próximos diez días, incluyendo ambos límites.
- `vencida`: la fecha de vencimiento es anterior al día actual.
- `sin_membresia`: el cliente no tiene membresías adquiridas.

La acción utiliza `whereHas()` para filtrar según la relación `ultimaMembresiaAdquirida` y `whereDoesntHave()` para identificar clientes sin adquisiciones registradas.

La paginación conserva los filtros aplicados, permitiendo navegar entre páginas sin perder los criterios de búsqueda.

### ConsultarMembresiasCliente

Archivo: `app/Actions/Cliente/ConsultarMembresiasCliente.php`

Centraliza la consulta de la membresía vigente y el historial de adquisiciones de un cliente específico.

Responsabilidades:

1. Recibir la instancia del cliente que se desea consultar.
2. Obtener la última membresía adquirida mediante la relación `ultimaMembresiaAdquirida`.
3. Cargar anticipadamente la relación `usuarioRegistro` para disponer de la información del usuario administrativo responsable.
4. Comprobar si la última adquisición continúa vigente, comparando su fecha de vencimiento con la fecha actual.
5. Identificar la membresía vigente cuando existe.
6. Consultar las adquisiciones asociadas al cliente y cargar la información de sus usuarios responsables.
7. Ordenar el historial desde la adquisición más reciente.
8. Excluir del historial la membresía vigente cuando existe, evitando duplicarla en la respuesta.
9. Paginar el historial de cinco en cinco registros y conservar los parámetros de consulta.
10. Devolver la membresía vigente y el historial paginado.

La membresía se considera vigente cuando su fecha de vencimiento es igual o posterior a la fecha actual.

Cuando existe una membresía vigente, esta se presenta de manera independiente y se excluye del historial. Cuando no existe, todas las adquisiciones del cliente forman parte del historial.

La consulta no depende del estado activo del cliente, por lo que permite recuperar información histórica de clientes deshabilitados.

### ActualizarCliente

Archivo: `app/Actions/Cliente/ActualizarCliente.php`

Centraliza la operación de actualización de información de clientes.

Responsabilidades:

1. Recibir la instancia del cliente que se desea actualizar.
2. Recibir los datos previamente validados por `ActualizarClienteRequest`.
3. Actualizar únicamente los campos recibidos mediante Eloquent.
4. Devolver la instancia del cliente con la información actualizada.

La acción utiliza `update()` para aplicar los datos recibidos y `refresh()` para devolver el modelo actualizado.

La actualización parcial permite conservar los valores de los atributos que no se incluyen en la solicitud.

### CambiarEstadoCliente

Archivo: `app/Actions/Cliente/CambiarEstadoCliente.php`

Centraliza la operación de cambio de estado de los clientes.

Responsabilidades:

1. Recibir la instancia del cliente cuyo estado se desea modificar.
2. Recibir el nuevo valor booleano del campo `activo`, previamente validado.
3. Actualizar exclusivamente el estado del cliente.
4. Devolver la instancia del cliente con su estado actualizado.

La acción utiliza `update()` para modificar únicamente el atributo `activo` y `refresh()` para devolver el modelo actualizado.

Esta operación no modifica los datos personales ni los registros históricos de membresías adquiridas asociados al cliente.

### EstadoMembresia

Archivo: `app/Support/EstadoMembresia.php`

Centraliza la lógica utilizada para determinar el estado de una membresía adquirida según su fecha de vencimiento.

Esta clase evita duplicar los criterios de clasificación en los recursos que necesitan presentar el estado de una membresía.

La clasificación contempla los siguientes estados:

- `activa`: cuando la fecha de vencimiento es posterior a los próximos diez días.
- `por_vencer`: cuando la fecha de vencimiento se encuentra entre el día actual y los próximos diez días, incluyendo ambos límites.
- `vencida`: cuando la fecha de vencimiento es anterior al día actual.

La comparación se realiza tomando como referencia la fecha actual del backend y considerando únicamente la fecha de vencimiento.

### ClienteController

Archivo: `app/Http/Controllers/ClienteController.php`

Coordina las solicitudes HTTP relacionadas con el registro, consulta, actualización y gestión del estado de clientes.

Implementa los métodos `store()`, `index()`, `update()` y `cambiarEstado()`.

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

**Responsabilidades de `index()`:**

1. Recibir la solicitud mediante `ConsultarClientesRequest`.
2. Obtener los parámetros previamente validados mediante `validated()`.
3. Ejecutar la acción `ConsultarClientes`.
4. Recibir los resultados paginados de la consulta.
5. Transformar los registros mediante `ClienteResource::collection()`.
6. Devolver la colección JSON conservando la estructura de paginación de Laravel.

**Responsabilidades de `update()`:**

1. Recibir la solicitud mediante `ActualizarClienteRequest`.
2. Recibir el cliente correspondiente mediante model binding.
3. Obtener los datos previamente validados.
4. Ejecutar la acción `ActualizarCliente`.
5. Transformar la información actualizada mediante `ClienteResource`.
6. Devolver una respuesta JSON indicando que la actualización se realizó correctamente.

**Responsabilidades de `cambiarEstado()`:**

1. Recibir la solicitud mediante `CambiarEstadoClienteRequest`.
2. Recibir el cliente correspondiente mediante model binding.
3. Obtener el valor validado de `activo`.
4. Ejecutar la acción `CambiarEstadoCliente`.
5. Transformar la información actualizada mediante `ClienteResource`.
6. Devolver una respuesta JSON indicando que el estado se actualizó correctamente.

Los métodos de actualización y cambio de estado delegan las operaciones en sus respectivas Actions, manteniendo separadas las responsabilidades de validación, lógica de aplicación y respuesta HTTP.

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

Adicionalmente, cuando la relación `ultimaMembresiaAdquirida` ha sido cargada, incluye:

- `membresia`: contiene la información de la última membresía adquirida mediante `MembresiaAdquiridaResource`. Si el cliente no tiene adquisiciones, devuelve `null`.
- `estado_membresia`: contiene el estado calculado de la última membresía adquirida.

El estado de la membresía se determina utilizando `EstadoMembresia` y los criterios establecidos para la clasificación.

El Resource utiliza `whenLoaded()` para incluir la información de la membresía únicamente cuando la relación ha sido cargada previamente.

Esto permite reutilizar el mismo recurso tanto en el registro de clientes como en su consulta, sin generar consultas adicionales innecesarias.

No se expone información interna adicional del modelo, como los campos de marcas de tiempo de Eloquent.

### MembresiaAdquiridaController

Archivo: `app/Http/Controllers/MembresiaAdquiridaController.php`

Coordina las solicitudes HTTP relacionadas con el registro de membresías adquiridas y la consulta de membresías de un cliente.

Implementa los métodos `store()` y `porCliente()`.

**Responsabilidades de `store()`:**

1. Recibir la solicitud mediante `RegistrarMembresiaAdquiridaRequest`.
2. Obtener los datos previamente validados.
3. Ejecutar la acción `RegistrarMembresiaAdquirida`.
4. Transformar la adquisición mediante `MembresiaAdquiridaResource`.
5. Devolver una respuesta JSON con el resultado del registro.

**Responsabilidades de `porCliente()`:**

1. Recibir la solicitud mediante `ConsultarMembresiasClienteRequest`.
2. Recibir el cliente correspondiente mediante model binding.
3. Obtener los parámetros previamente validados.
4. Ejecutar la acción `ConsultarMembresiasCliente`.
5. Transformar la membresía vigente mediante `MembresiaAdquiridaResource`, cuando existe.
6. Transformar los registros del historial mediante `MembresiaAdquiridaResource::collection()`.
7. Resolver la colección del Resource mediante `resolve($request)` para obtener los datos ya transformados antes de incorporarlos a la respuesta personalizada.
8. Devolver la membresía vigente, el historial y la información de paginación en una respuesta JSON.

El uso de `resolve($request)` permite obtener el arreglo resultante de la transformación del Resource, en lugar de incluir directamente el objeto de la colección dentro de la respuesta personalizada.

De esta manera, el controlador puede construir una estructura que contiene tanto la membresía vigente como el historial, conservando los enlaces y metadatos de paginación generados por Laravel.

### MembresiaAdquiridaResource

Archivo: `app/Http/Resources/MembresiaAdquiridaResource.php`

Transforma el modelo `MembresiaAdquirida` en una respuesta JSON controlada.

Expone los datos correspondientes a:

- `id_membresia_adquirida`
- `id_cliente`
- `id_tipo_membresia`
- `id_usuario_registro`
- `nombre_tipo_snapshot`
- `duracion_dias_snapshot`
- `fecha_inicio`
- `fecha_vencimiento`
- `valor_pagado`
- `metodo_pago`

Adicionalmente, incorpora:

- `estado`: contiene el estado calculado de la membresía mediante `EstadoMembresia`.
- `usuario_registro`: contiene la información del usuario administrativo responsable de registrar la adquisición, cuando la relación `usuarioRegistro` ha sido cargada.

La información del usuario responsable incluye su identificador, nombre de usuario, rol y estado activo.

La relación se presenta mediante `whenLoaded()`, por lo que solamente se incluye cuando ha sido cargada previamente. Esto permite conservar el comportamiento de las respuestas existentes que utilizan el mismo Resource sin cargar la relación del usuario responsable.

La información histórica del usuario administrativo se conserva incluso cuando su cuenta se encuentra deshabilitada.

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

### 4.1. Registro de clientes

El registro de clientes se realiza mediante una solicitud HTTP `POST`.

#### Endpoint

`POST /api/clientes`

**Middlewares aplicados:**

- `auth:sanctum`: requiere autenticación.
- `usuario.activo`: impide el acceso a cuentas desactivadas.

La autorización por rol se realiza mediante `RegistrarClienteRequest`, que permite únicamente a usuarios con rol `dueno` o `empleado`.

**Nombre de ruta:**

`cliente.store`

#### Proceso de registro

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
11. Si no se solicitó una membresía, el backend devuelve HTTP `201 Created` con la información del cliente.
12. Si se solicitó una membresía, el controlador valida sus datos y ejecuta la acción `RegistrarMembresiaAdquirida` cuando son válidos.
13. El backend devuelve HTTP `201 Created` con el resultado del registro del cliente y, cuando corresponde, los datos de la membresía adquirida.

#### Registro opcional de la primera membresía

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

#### Respuesta exitosa sin membresía

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

#### Respuesta exitosa con membresía

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

#### Respuesta cuando la membresía no puede registrarse

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

### 4.2. Consulta de clientes

La consulta de clientes se realiza mediante una solicitud HTTP `GET`.

#### Endpoint

`GET /api/clientes`

**Middlewares aplicados:**

- `auth:sanctum`: requiere autenticación.
- `usuario.activo`: impide el acceso a cuentas desactivadas.

La autorización por rol se realiza mediante `ConsultarClientesRequest`, que permite únicamente a usuarios con rol `dueno` o `empleado`.

**Nombre de ruta:**

`cliente.index`

#### Parámetros de consulta

La consulta admite los siguientes parámetros opcionales:

| Parámetro | Tipo | Descripción | Valores permitidos |
|---|---|---|---|
| `buscar` | String | Busca coincidencias parciales en nombres, apellidos, documento y teléfono. | Máximo 100 caracteres. |
| `activo` | Boolean | Filtra según el estado del cliente. | `1` o `0`. |
| `estado_membresia` | String | Filtra según el estado de la última membresía adquirida. | `activa`, `por_vencer`, `vencida`, `sin_membresia`. |
| `page` | Integer | Especifica la página que se desea consultar. | Entero mayor o igual a 1. |

Los parámetros pueden combinarse para obtener resultados más específicos.

#### Ejemplos de consulta

**Consultar todos los clientes:**

```http
GET /api/clientes
```

**Buscar clientes por nombre, apellido, documento o teléfono:**

```http
GET /api/clientes?buscar=carlos
```

**Consultar únicamente clientes activos:**

```http
GET /api/clientes?activo=1
```

**Consultar únicamente clientes inactivos:**

```http
GET /api/clientes?activo=0
```

**Consultar clientes con membresías por vencer:**

```http
GET /api/clientes?estado_membresia=por_vencer
```

**Combinar búsqueda y estado del cliente:**

```http
GET /api/clientes?buscar=carlos&activo=1
```

**Combinar estado del cliente y estado de membresía:**

```http
GET /api/clientes?activo=1&estado_membresia=activa
```

**Consultar la segunda página de resultados:**

```http
GET /api/clientes?page=2
```

#### Proceso de consulta

El proceso sigue este orden:

1. El usuario administrativo accede a la funcionalidad de consulta de clientes.
2. El frontend envía una solicitud `GET /api/clientes`, incluyendo opcionalmente los parámetros de búsqueda, filtros y paginación.
3. Laravel comprueba la autenticación mediante `auth:sanctum`.
4. El middleware `VerificarUsuarioActivo` comprueba que la cuenta del usuario continúe activa.
5. `ConsultarClientesRequest` autoriza la operación y valida los parámetros recibidos.
6. `ClienteController` obtiene los parámetros previamente validados.
7. El controlador ejecuta la acción `ConsultarClientes`.
8. La acción construye la consulta y carga la relación `ultimaMembresiaAdquirida`.
9. Se aplican los filtros y criterios de búsqueda proporcionados.
10. Los resultados se ordenan alfabéticamente por nombres y apellidos.
11. La consulta devuelve los registros paginados de cinco en cinco, conservando los parámetros mediante `withQueryString()`.
12. `ClienteResource::collection()` transforma los registros y conserva la información de paginación.
13. El backend devuelve HTTP `200 OK` con los resultados y los metadatos de paginación.

#### Clasificación del estado de membresía

El estado de membresía se calcula a partir de la fecha de vencimiento de la última membresía adquirida del cliente.

| Estado | Condición |
|---|---|
| `activa` | La fecha de vencimiento es posterior a los próximos diez días. |
| `por_vencer` | La fecha de vencimiento se encuentra entre hoy y los próximos diez días, incluyendo ambos límites. |
| `vencida` | La fecha de vencimiento es anterior al día actual. |
| `sin_membresia` | El cliente no tiene membresías adquiridas. |

La clasificación se realiza tomando como referencia la fecha actual del backend y comparando únicamente la fecha de vencimiento.

El sistema siempre considera la última membresía adquirida, incluso cuando esta se encuentra vencida. No utiliza adquisiciones anteriores para determinar el estado actual del cliente.

#### Respuesta exitosa

La consulta devuelve una colección de clientes transformados mediante `ClienteResource`, junto con los enlaces y metadatos de paginación generados por Laravel.

**Ejemplo de respuesta:**

```json
{
    "data": [
        {
            "id_cliente": 1,
            "nombres": "Carlos",
            "apellidos": "Gomez",
            "documento": "1234567890",
            "telefono": "3001234567",
            "activo": true,
            "autorizacion_tratamiento": true,
            "fecha_autorizacion": "2026-11-17 10:30:00",
            "membresia": {
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
            },
            "estado_membresia": "activa"
        }
    ],
    "links": {
        "first": "http://localhost/api/clientes?page=1",
        "last": "http://localhost/api/clientes?page=2",
        "prev": null,
        "next": "http://localhost/api/clientes?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 2,
        "links": [],
        "path": "http://localhost/api/clientes",
        "per_page": 5,
        "to": 5,
        "total": 7
    }
}
```

Los valores son ilustrativos.

Cuando el cliente no tiene membresías adquiridas, el campo `membresia` devuelve `null` y `estado_membresia` devuelve `sin_membresia`.

Cuando no existen resultados, la API devuelve HTTP `200 OK` con una colección vacía y los metadatos de paginación correspondientes.

#### Respuestas de error

| Código HTTP | Situación |
|---|---|
| `401 Unauthorized` | El usuario no está autenticado. |
| `403 Forbidden` | La cuenta del usuario está desactivada o no tiene autorización para realizar la operación. |
| `422 Unprocessable Entity` | Uno o varios parámetros de búsqueda, filtrado o paginación incumplen las reglas de validación. |

### 4.3. Consulta de membresías de un cliente

La consulta de membresías de un cliente se realiza mediante una solicitud HTTP `GET`.

#### Endpoint

`GET /api/clientes/{cliente}/membresias`

**Middlewares aplicados:**

- `auth:sanctum`: requiere autenticación.
- `usuario.activo`: impide el acceso a cuentas desactivadas.

La autorización por rol se realiza mediante `ConsultarMembresiasClienteRequest`, que permite únicamente a usuarios con rol `dueno` o `empleado`.

**Nombre de ruta:**

`cliente.membresias.index`

#### Parámetros de consulta

La consulta admite el siguiente parámetro opcional:

| Parámetro | Tipo | Descripción | Valores permitidos |
|---|---|---|---|
| `page` | Integer | Especifica la página del historial que se desea consultar. | Entero mayor o igual a 1. |

El identificador del cliente se proporciona mediante el parámetro de ruta `{cliente}`.

#### Ejemplos de consulta

**Consultar las membresías de un cliente:**

```http
GET /api/clientes/1/membresias
```

**Consultar la segunda página del historial:**

```http
GET /api/clientes/1/membresias?page=2
```

#### Proceso de consulta

El proceso sigue este orden:

1. El usuario administrativo selecciona un cliente registrado.
2. El frontend envía una solicitud `GET /api/clientes/{cliente}/membresias`, incluyendo opcionalmente el parámetro de paginación.
3. Laravel comprueba la autenticación mediante `auth:sanctum`.
4. El middleware `VerificarUsuarioActivo` comprueba que la cuenta del usuario administrativo continúe activa.
5. Laravel identifica al cliente mediante model binding.
6. `ConsultarMembresiasClienteRequest` autoriza la operación y valida los parámetros recibidos.
7. `MembresiaAdquiridaController` obtiene los datos previamente validados.
8. El controlador ejecuta la acción `ConsultarMembresiasCliente`.
9. La acción consulta la última adquisición y comprueba si continúa vigente.
10. Cuando existe una membresía vigente, la separa del historial para evitar duplicarla.
11. La acción consulta las adquisiciones del cliente, incluyendo la relación `usuarioRegistro`.
12. El historial se ordena desde la adquisición más reciente y se pagina de cinco en cinco.
13. `MembresiaAdquiridaResource` transforma la membresía vigente y los registros del historial.
14. El controlador utiliza `resolve($request)` para obtener los datos transformados de la colección y construir la respuesta JSON personalizada.
15. El backend devuelve HTTP `200 OK` con la membresía vigente, cuando existe, y el historial paginado.

La consulta no exige que el cliente se encuentre activo. Por lo tanto, también es posible consultar las adquisiciones de clientes deshabilitados.

#### Membresía vigente

Cuando el cliente tiene una adquisición vigente, esta se presenta en el campo `membresia_vigente`.

Se considera vigente cuando la fecha de vencimiento es igual o posterior a la fecha actual.

Si el cliente no tiene una membresía vigente, el campo devuelve `null`.

La membresía vigente no se incluye nuevamente dentro del historial.

#### Historial de adquisiciones

El historial contiene las adquisiciones anteriores del cliente y también incluye la última adquisición cuando esta se encuentra vencida.

Los registros se ordenan desde la adquisición más reciente, utilizando la fecha de inicio y criterios adicionales de ordenamiento para mantener resultados consistentes.

El historial se pagina de cinco en cinco registros y conserva los parámetros de consulta mediante `withQueryString()`.

Cuando el cliente no tiene adquisiciones, el historial devuelve una colección vacía.

#### Estado de las membresías

Cada adquisición incluye el campo `estado`, calculado mediante `EstadoMembresia`.

| Estado | Condición |
|---|---|
| `activa` | La fecha de vencimiento es posterior a los próximos diez días. |
| `por_vencer` | La fecha de vencimiento se encuentra entre hoy y los próximos diez días, incluyendo ambos límites. |
| `vencida` | La fecha de vencimiento es anterior al día actual. |

La clasificación utiliza la fecha actual del backend y compara únicamente la fecha de vencimiento.

#### Usuario responsable de la adquisición

Cada adquisición incluye el campo `usuario_registro`, que contiene la información del usuario administrativo responsable de registrar la membresía.

La información contempla:

- `id_usuario`: identificador del usuario administrativo.
- `usuario`: nombre de usuario.
- `rol`: rol del usuario.
- `activo`: estado actual de la cuenta administrativa.

La relación `usuarioRegistro` se carga anticipadamente mediante Eloquent para evitar consultas adicionales al transformar los recursos.

La información del responsable se conserva aunque su cuenta administrativa haya sido deshabilitada.

#### Respuesta exitosa

La API devuelve HTTP `200 OK`.

**Ejemplo de respuesta con membresía vigente e historial:**

```json
{
    "mensaje": "Información de membresías consultada correctamente.",
    "membresia_vigente": {
        "id_membresia_adquirida": 2,
        "id_cliente": 1,
        "id_tipo_membresia": 1,
        "id_usuario_registro": 1,
        "nombre_tipo_snapshot": "Mensual",
        "duracion_dias_snapshot": 30,
        "fecha_inicio": "2026-11-17",
        "fecha_vencimiento": "2026-12-16 23:59:59",
        "valor_pagado": "80000.00",
        "metodo_pago": "efectivo",
        "estado": "activa",
        "usuario_registro": {
            "id_usuario": 1,
            "usuario": "admin",
            "rol": "dueno",
            "activo": true
        }
    },
    "historial": {
        "data": [
            {
                "id_membresia_adquirida": 1,
                "id_cliente": 1,
                "id_tipo_membresia": 1,
                "id_usuario_registro": 1,
                "nombre_tipo_snapshot": "Mensual",
                "duracion_dias_snapshot": 30,
                "fecha_inicio": "2026-10-17",
                "fecha_vencimiento": "2026-11-15 23:59:59",
                "valor_pagado": "80000.00",
                "metodo_pago": "efectivo",
                "estado": "vencida",
                "usuario_registro": {
                    "id_usuario": 1,
                    "usuario": "admin",
                    "rol": "dueno",
                    "activo": true
                }
            }
        ],
        "links": {
            "first": "http://localhost/api/clientes/1/membresias?page=1",
            "last": "http://localhost/api/clientes/1/membresias?page=1",
            "prev": null,
            "next": null
        },
        "meta": {
            "current_page": 1,
            "from": 1,
            "last_page": 1,
            "links": [],
            "path": "http://localhost/api/clientes/1/membresias",
            "per_page": 5,
            "to": 1,
            "total": 1
        }
    }
}
```

Los valores son ilustrativos y representan la estructura de la respuesta.

Cuando el cliente no tiene una membresía vigente, `membresia_vigente` devuelve `null` y el historial conserva las adquisiciones existentes.

Cuando el cliente no tiene adquisiciones registradas, la respuesta incluye `membresia_vigente` con valor `null` y un historial vacío.

#### Respuestas de error

| Código HTTP | Situación |
|---|---|
| `401 Unauthorized` | El usuario no está autenticado. |
| `403 Forbidden` | La cuenta del usuario está desactivada o no tiene autorización para realizar la operación. |
| `404 Not Found` | El cliente solicitado no existe. |
| `422 Unprocessable Entity` | El parámetro de paginación incumple las reglas de validación. |

### 4.4. Actualización de clientes

La actualización de clientes se realiza mediante una solicitud HTTP `PATCH`.

#### Endpoint

`PATCH /api/clientes/{cliente}`

**Middlewares aplicados:**

- `auth:sanctum`: requiere autenticación.
- `usuario.activo`: impide el acceso a cuentas desactivadas.

La autorización por rol se realiza mediante `ActualizarClienteRequest`, que permite únicamente a usuarios con rol `dueno` o `empleado`.

**Nombre de ruta:**

`cliente.update`

#### Proceso de actualización

1. El usuario administrativo selecciona un cliente registrado.
2. El frontend envía una solicitud `PATCH /api/clientes/{cliente}` con los campos que desea modificar.
3. Laravel comprueba la autenticación mediante `auth:sanctum`.
4. El middleware `VerificarUsuarioActivo` comprueba que la cuenta del usuario continúe activa.
5. Laravel identifica el cliente mediante model binding.
6. `ActualizarClienteRequest` autoriza la operación y valida los campos recibidos.
7. El controlador obtiene los datos previamente validados.
8. El controlador ejecuta la acción `ActualizarCliente`.
9. La acción actualiza únicamente los campos recibidos.
10. `ClienteResource` transforma el modelo actualizado.
11. El backend devuelve HTTP `200 OK` con el resultado de la operación.

#### Actualización parcial

La solicitud permite modificar uno o varios campos editables sin necesidad de enviar toda la información del cliente.

Los campos disponibles son:

- `nombres`
- `apellidos`
- `documento`
- `telefono`
- `activo`

El campo `activo` permite modificar el estado del cliente durante la actualización de información.

Los campos que no se envían conservan sus valores originales.

La autorización para el tratamiento de datos personales y su fecha no pueden modificarse mediante este endpoint.

#### Ejemplo de solicitud

```json
{
    "nombres": "Juan Carlos",
    "telefono": "3119876543"
}
```

Los valores son ilustrativos.

#### Respuesta exitosa

La API devuelve HTTP `200 OK`.

```json
{
    "mensaje": "Cliente actualizado correctamente.",
    "cliente": {
        "id_cliente": 1,
        "nombres": "Juan Carlos",
        "apellidos": "Gomez",
        "documento": "1234567890",
        "telefono": "3119876543",
        "activo": true,
        "autorizacion_tratamiento": true,
        "fecha_autorizacion": "2026-11-17 10:30:00"
    }
}
```

Los valores son ilustrativos.

### 4.5. Cambio de estado de clientes

El cambio de estado de clientes se realiza mediante una solicitud HTTP `PATCH`.

#### Endpoint

`PATCH /api/clientes/{cliente}/estado`

**Middlewares aplicados:**

- `auth:sanctum`: requiere autenticación.
- `usuario.activo`: impide el acceso a cuentas desactivadas.

La autorización por rol se realiza mediante `CambiarEstadoClienteRequest`, que permite únicamente a usuarios con rol `dueno` o `empleado`.

**Nombre de ruta:**

`cliente.estado`

#### Proceso de cambio de estado

1. El usuario administrativo selecciona un cliente registrado.
2. El frontend envía una solicitud `PATCH /api/clientes/{cliente}/estado` con el nuevo valor de `activo`.
3. Laravel comprueba la autenticación mediante `auth:sanctum`.
4. El middleware `VerificarUsuarioActivo` comprueba que la cuenta del usuario continúe activa.
5. Laravel identifica el cliente mediante model binding.
6. `CambiarEstadoClienteRequest` autoriza la operación y valida el valor recibido.
7. El controlador obtiene el valor booleano validado.
8. El controlador ejecuta la acción `CambiarEstadoCliente`.
9. La acción modifica exclusivamente el campo `activo`.
10. `ClienteResource` transforma el modelo actualizado.
11. El backend devuelve HTTP `200 OK` con el resultado de la operación.

#### Habilitación y deshabilitación

El campo `activo` determina la disponibilidad del cliente para las operaciones que requieren un cliente habilitado.

- `true`: habilita al cliente.
- `false`: deshabilita al cliente.

Un cliente deshabilitado conserva sus datos personales y sus registros históricos de membresías adquiridas.

El cliente puede habilitarse nuevamente mediante este mismo endpoint, enviando `activo` con valor `true`.

#### Ejemplo de solicitud

```json
{
    "activo": false
}
```

#### Respuesta exitosa

La API devuelve HTTP `200 OK`.

```json
{
    "mensaje": "Estado del cliente actualizado correctamente.",
    "cliente": {
        "id_cliente": 1,
        "nombres": "Carlos",
        "apellidos": "Gomez",
        "documento": "1234567890",
        "telefono": "3001234567",
        "activo": false,
        "autorizacion_tratamiento": true,
        "fecha_autorizacion": "2026-11-17 10:30:00"
    }
}
```

Los valores son ilustrativos.

## 5. Reglas de validación y seguridad

### Autorización por roles

El registro, consulta, actualización, cambio de estado y consulta de membresías de clientes están permitidos a los usuarios con rol `dueno` y `empleado`.

La autorización se comprueba mediante los respectivos Form Requests:

- `RegistrarClienteRequest`.
- `ConsultarClientesRequest`.
- `ConsultarMembresiasClienteRequest`.
- `ActualizarClienteRequest`.
- `CambiarEstadoClienteRequest`.

### Nombres y apellidos

Los nombres y apellidos son obligatorios durante el registro y deben ser valores de tipo texto.

Cada campo admite un máximo de 100 caracteres.

Durante la actualización, estos campos son opcionales, pero si se envían deben contener un valor válido y no pueden estar vacíos.

### Documento

El documento es obligatorio durante el registro, debe ser de tipo texto y admite un máximo de 30 caracteres.

Su valor debe ser único dentro de la tabla `cliente`.

No se permite registrar dos clientes con el mismo documento.

Durante la actualización, el documento también debe ser único. La validación excluye el registro del cliente que se está modificando, permitiendo conservar su propio documento sin generar un error de unicidad.

### Teléfono

El teléfono es obligatorio durante el registro, debe ser de tipo texto y admite un máximo de 20 caracteres.

Durante la actualización, es opcional, pero si se envía debe cumplir las mismas reglas de validación.

### Autorización para el tratamiento de datos personales

La autorización para el tratamiento de datos personales es obligatoria durante el registro.

El campo `autorizacion_tratamiento` debe contener un valor aceptado por la regla `accepted` de Laravel.

No se permite registrar un cliente sin esta autorización.

La fecha en que se concede la autorización se genera automáticamente mediante `now()` en el backend, por lo que el frontend no puede establecerla directamente.

Los campos `autorizacion_tratamiento` y `fecha_autorizacion` no forman parte de los atributos editables de RF9. Por lo tanto, las operaciones de actualización no modifican su información.

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

### Validación de parámetros de consulta

Los parámetros de consulta de clientes se validan mediante `ConsultarClientesRequest`.

Las reglas contemplan:

- `buscar`: debe ser de tipo texto y no superar los 100 caracteres.
- `activo`: debe contener un valor booleano válido.
- `estado_membresia`: debe corresponder a uno de los estados permitidos: `activa`, `por_vencer`, `vencida` o `sin_membresia`.
- `page`: debe ser un número entero mayor o igual a 1.

La consulta de membresías de un cliente utiliza `ConsultarMembresiasClienteRequest`, que valida el parámetro `page` como un número entero mayor o igual a 1.

No se ejecutan consultas cuando los parámetros recibidos incumplen las reglas de validación.

### Actualización de información

La actualización de clientes se realiza mediante `ActualizarClienteRequest`.

Los campos editables son `nombres`, `apellidos`, `documento`, `telefono` y `activo`.

Todos son opcionales, pero cuando se envían deben cumplir sus respectivas reglas de validación.

La operación permite actualizar parcialmente la información, conservando los valores de los campos omitidos.

### Unicidad del documento durante la actualización

El documento debe continuar siendo único dentro de la tabla `cliente`.

Durante la actualización, la validación excluye el registro del cliente que se está modificando mediante `Rule::unique()->ignore()`.

Esto permite conservar el documento actual del cliente, pero impide utilizar el documento perteneciente a otro cliente.

### Gestión del estado del cliente

El estado se puede modificar mediante dos operaciones:

- Actualización de información: permite enviar `activo` opcionalmente junto con otros campos editables.
- Cambio de estado: modifica exclusivamente `activo` mediante el endpoint `/clientes/{cliente}/estado`.

La segunda operación requiere que el campo `activo` esté presente y contenga un valor booleano válido.

### Conservación de información personal e histórica

Las operaciones de actualización y cambio de estado no modifican los campos `autorizacion_tratamiento` y `fecha_autorizacion`.

Tampoco eliminan ni modifican las membresías adquiridas asociadas al cliente.

Deshabilitar un cliente no implica eliminar su información ni sus registros históricos.

### Disponibilidad de clientes

Las operaciones que requieren un cliente habilitado deben comprobar que su estado `activo` sea verdadero.

Un cliente deshabilitado no debe estar disponible para dichas operaciones, pero puede habilitarse nuevamente mediante el endpoint de cambio de estado.

La consulta de membresías de un cliente no requiere que este se encuentre habilitado, ya que su finalidad es permitir la consulta de la información histórica.

### Búsqueda de clientes

La búsqueda se realiza mediante coincidencias parciales en los siguientes campos:

- Nombres.
- Apellidos.
- Documento.
- Teléfono.

El término ingresado se aplica sobre estos campos mediante condiciones `LIKE`, agrupadas para que la búsqueda pueda combinarse correctamente con los demás filtros.

### Filtro por estado del cliente

El parámetro `activo` permite consultar clientes según su estado:

- `1`: clientes activos.
- `0`: clientes inactivos.

La implementación utiliza `array_key_exists()` para identificar si el parámetro fue enviado, permitiendo aplicar correctamente el filtro incluso cuando su valor es `false` o `0`.

### Filtro por estado de membresía

El parámetro `estado_membresia` permite filtrar los clientes según el estado de su última adquisición.

Los estados permitidos son:

- `activa`.
- `por_vencer`.
- `vencida`.
- `sin_membresia`.

La consulta utiliza exclusivamente la relación `ultimaMembresiaAdquirida` para determinar el estado de la membresía.

Los clientes sin adquisiciones se identifican mediante `whereDoesntHave('membresiasAdquiridas')`.

### Clasificación de membresías

La clasificación se realiza tomando como referencia la fecha actual del backend.

- Una membresía se considera `activa` cuando vence después de los próximos diez días.
- Se considera `por_vencer` cuando vence entre hoy y los próximos diez días, incluyendo ambos límites.
- Se considera `vencida` cuando su fecha de vencimiento es anterior al día actual.
- Se considera `sin_membresia` cuando el cliente no tiene adquisiciones registradas.

La comparación se realiza sobre la fecha, sin considerar la hora del vencimiento.

La lógica de clasificación se centraliza en `EstadoMembresia` para reutilizar los mismos criterios en los recursos que presentan el estado de una membresía.

### Paginación

Los resultados de la consulta de clientes se devuelven mediante la paginación de Laravel, con un límite de cinco registros por página.

El historial de adquisiciones también se pagina de cinco en cinco registros.

Se utiliza `withQueryString()` para conservar los parámetros de consulta en los enlaces de navegación.

El parámetro `page` permite consultar una página específica y debe ser un entero mayor o igual a 1.

### Integridad de las operaciones

La creación del cliente y el registro de la primera membresía no se ejecutan dentro de una única transacción que revierta ambas operaciones.

Esto permite que un error en el registro de la membresía no elimine un cliente que ya fue creado correctamente.

La acción `RegistrarMembresiaAdquirida` conserva su propia lógica transaccional, incluyendo las comprobaciones y los bloqueos necesarios para mantener la integridad de la adquisición.

### Protección de rutas

Las operaciones del módulo están protegidas mediante autenticación con Sanctum y verificación del estado de la cuenta administrativa.

La autorización por rol se aplica en el backend mediante los respectivos Form Requests.

El frontend no constituye el mecanismo de seguridad: las restricciones se aplican en el servidor.

### Protección de información personal

El recurso `ClienteResource` controla los atributos que se exponen en las respuestas JSON.

La información se presenta mediante los campos definidos para el módulo, evitando exponer atributos internos adicionales del modelo.

Los recursos de membresías también controlan los datos que se exponen, incluyendo la información del usuario responsable únicamente cuando la relación ha sido cargada.

## 6. Rutas API

Las rutas relacionadas con el módulo de clientes se encuentran en:

`routes/api.php`

Actualmente se encuentran implementadas:

| Método | Endpoint | Nombre | Operación |
|---|---|---|---|
| POST | `/api/clientes` | `cliente.store` | Registrar un cliente y, opcionalmente, su primera membresía. |
| GET | `/api/clientes` | `cliente.index` | Consultar clientes mediante búsqueda, filtros y paginación. |
| GET | `/api/clientes/{cliente}/membresias` | `cliente.membresias.index` | Consultar la membresía vigente y el historial de adquisiciones de un cliente. |
| PATCH | `/api/clientes/{cliente}` | `cliente.update` | Actualizar parcialmente la información del cliente y, opcionalmente, su estado. |
| PATCH | `/api/clientes/{cliente}/estado` | `cliente.estado` | Cambiar exclusivamente el estado del cliente. |

Todas las rutas están protegidas mediante `auth:sanctum` y `usuario.activo`.

La autorización por rol se realiza mediante los respectivos Form Requests, que permiten el acceso a usuarios con rol `dueno` o `empleado`.

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

### Pruebas de consulta de clientes

Archivo: `tests/Feature/Cliente/ConsultarClientesTest.php`

Se implementaron 30 pruebas mediante PHPUnit para verificar los permisos, la búsqueda, los filtros, la clasificación de membresías, la paginación y las combinaciones de parámetros de consulta.

**Escenarios comprobados:**

**1. Consulta y permisos**

- Un dueño puede consultar clientes.
- Un empleado puede consultar clientes.
- Un usuario no autenticado no puede consultar clientes.
- Un usuario inactivo no puede consultar clientes.

**2. Búsqueda de clientes**

- Buscar clientes por nombres.
- Buscar clientes por apellidos.
- Buscar clientes por documento.
- Buscar clientes por teléfono.
- Realizar búsquedas mediante coincidencias parciales.
- Consultar todos los clientes cuando no se proporciona un término de búsqueda.

**3. Estado del cliente**

- Consultar únicamente clientes activos.
- Consultar únicamente clientes inactivos.

**4. Estado de membresía**

- Consultar clientes con membresías activas.
- Consultar clientes con membresías por vencer.
- Consultar clientes con membresías vencidas.
- Consultar clientes sin membresías adquiridas.
- Comprobar la clasificación del estado de membresía según la fecha de vencimiento.
- Verificar los límites de vencimiento: día actual, diez días y once días.
- Comprobar que se consulte el estado de la última membresía adquirida.

**5. Paginación**

- Comprobar que los clientes se paginen de cinco en cinco.
- Consultar los registros correspondientes a la segunda página.
- Verificar que los filtros aplicados se conserven en los enlaces de paginación.

**6. Validación de parámetros**

- Rechazar términos de búsqueda que no sean texto.
- Rechazar términos de búsqueda que superen los 100 caracteres.
- Rechazar valores inválidos para el estado del cliente.
- Rechazar estados de membresía no permitidos.
- Rechazar números de página menores que 1.

**7. Combinación de filtros y casos adicionales**

- Combinar búsqueda y estado del cliente.
- Combinar estado del cliente y estado de membresía.
- Comprobar que una búsqueda sin coincidencias devuelva una colección vacía.
- Comprobar que la consulta funcione cuando no existen clientes registrados.

### Pruebas de consulta de membresías de clientes

Archivo: `tests/Feature/MembresiaAdquirida/ConsultarMembresiasClienteTest.php`

Se implementaron 14 pruebas mediante PHPUnit para verificar los permisos, la consulta de la membresía vigente, el historial de adquisiciones, la clasificación de estados, la información del usuario responsable y la paginación.

**Escenarios comprobados:**

1. Un dueño puede consultar las membresías de un cliente.
2. Un empleado puede consultar las membresías de un cliente.
3. Un usuario no autenticado no puede consultar membresías.
4. Un usuario inactivo no puede consultar membresías.
5. No se pueden consultar membresías de un cliente inexistente.
6. Se pueden consultar las membresías de un cliente inactivo.
7. Un cliente sin membresías adquiridas recibe un historial vacío.
8. La membresía vigente se muestra separada del historial.
9. Se calcula correctamente el estado de las membresías.
10. Se conserva la información del usuario que registró la adquisición.
11. El historial se ordena desde la adquisición más reciente.
12. El historial se pagina de cinco en cinco registros.
13. No se puede consultar una página menor que uno.
14. No se puede consultar una página que no sea entera.

### Pruebas de actualización de clientes

Archivo: `tests/Feature/Cliente/ActualizarClienteTest.php`

Se implementaron 16 pruebas mediante PHPUnit para verificar la actualización parcial de información, la validación de los campos editables, la autorización y la conservación de los registros históricos.

**Escenarios comprobados:**

1. Un dueño puede actualizar un cliente.
2. Un empleado puede actualizar un cliente.
3. Un usuario no autenticado no puede actualizar un cliente.
4. Un usuario inactivo no puede actualizar un cliente.
5. Se pueden actualizar todos los campos simultáneamente.
6. Se pueden actualizar individualmente los campos del cliente.
7. Se permite actualizar con una solicitud vacía.
8. No se permite actualizar con campos obligatorios vacíos.
9. No se permite actualizar con valores que superen las longitudes máximas.
10. No se permite actualizar con valores que no sean cadenas.
11. No se permite utilizar el documento de otro cliente.
12. Se permite conservar el documento actual del cliente.
13. No se permite actualizar con un estado inválido.
14. No se puede actualizar un cliente inexistente.
15. No se permite modificar los datos de autorización de tratamiento.
16. Actualizar un cliente no modifica su historial de membresías.

### Pruebas de cambio de estado de clientes

Archivo: `tests/Feature/Cliente/CambiarEstadoClienteTest.php`

Se implementaron 10 pruebas mediante PHPUnit para verificar la habilitación y deshabilitación de clientes, la autorización de la operación y la conservación de la información asociada.

**Escenarios comprobados:**

1. Un dueño puede deshabilitar un cliente.
2. Un empleado puede deshabilitar un cliente.
3. Un dueño puede habilitar nuevamente un cliente.
4. Un usuario no autenticado no puede cambiar el estado de un cliente.
5. Un usuario inactivo no puede cambiar el estado de un cliente.
6. No se permite cambiar el estado sin enviar el campo `activo`.
7. No se permite cambiar el estado con valores inválidos.
8. No se puede cambiar el estado de un cliente inexistente.
9. Cambiar el estado no modifica los demás datos del cliente.
10. Cambiar el estado no modifica el historial de membresías.

### Ejecución de las pruebas

Para ejecutar las pruebas de registro:

```bash
herd php artisan test --filter=RegistrarClienteTest
```

Para ejecutar las pruebas de consulta:

```bash
herd php artisan test --filter=ConsultarClientesTest
```

Para ejecutar las pruebas de consulta de membresías:

```bash
herd php artisan test --filter=ConsultarMembresiasClienteTest
```

Para ejecutar las pruebas de actualización:

```bash
herd php artisan test --filter=ActualizarClienteTest
```

Para ejecutar las pruebas de cambio de estado:

```bash
herd php artisan test --filter=CambiarEstadoClienteTest
```

Para ejecutar todas las pruebas del módulo de clientes:

```bash
herd php artisan test tests/Feature/Cliente
```

Para ejecutar todas las pruebas del módulo de membresías adquiridas:

```bash
herd php artisan test tests/Feature/MembresiaAdquirida
```

Para ejecutar todas las pruebas del proyecto:

```bash
herd php artisan test
```

### Resultado de las pruebas

Últimos resultados individuales registrados:

**Registro de clientes:**

```text
Tests: 23 passed (184 assertions)
```

**Consulta de clientes:**

```text
Tests: 30 passed (141 assertions)
```

**Actualización de clientes:**

```text
Tests: 16 passed (74 assertions)
```

**Cambio de estado de clientes:**

```text
Tests: 10 passed (31 assertions)
```

**Consulta de membresías de clientes:**

```text
Tests: 14 passed (59 assertions)
```

**Resultado consolidado de las funcionalidades documentadas del módulo de clientes:**

```text
Tests: 93 passed (489 assertions)
```

## 8. Estado del módulo

El módulo de gestión de clientes cuenta con las funcionalidades de registro (RF7), consulta (RF8), actualización y gestión del estado (RF9), y consulta de información de membresías (RF10), implementadas y validadas mediante pruebas automatizadas.

La funcionalidad de registro permite crear clientes con sus datos personales y de contacto, exigir la autorización para el tratamiento de datos personales y establecer automáticamente su estado inicial activo.

También permite registrar opcionalmente la primera membresía del cliente durante la misma solicitud, reutilizando las reglas de validación y la lógica de negocio del módulo de membresías adquiridas.

La funcionalidad de consulta permite obtener los clientes registrados mediante búsquedas parciales y filtros por estado del cliente y estado de la última membresía adquirida.

Además, proporciona la clasificación automática de las membresías según sus fechas de vencimiento y la paginación de cinco registros por página, conservando los filtros aplicados durante la navegación.

La funcionalidad de consulta de membresías permite obtener la membresía vigente de un cliente, cuando existe, y consultar su historial completo de adquisiciones.

El historial se presenta de forma independiente a la membresía vigente, se ordena desde la adquisición más reciente y se pagina de cinco en cinco registros. Cada adquisición incluye la información del usuario administrativo responsable de su registro.

La consulta del historial está disponible incluso para clientes deshabilitados y conserva la información de los usuarios responsables aunque sus cuentas administrativas se encuentren inactivas.

La funcionalidad de actualización de clientes permite modificar parcialmente los datos personales y de contacto, validar la unicidad del documento y gestionar el estado del cliente.

El módulo dispone de dos operaciones independientes: una para actualizar la información del cliente, incluyendo opcionalmente su estado, y otra para cambiar exclusivamente su estado.

Los clientes deshabilitados conservan su información y el historial de membresías adquiridas, y pueden habilitarse nuevamente cuando corresponda.

Los requerimientos RF7, RF8, RF9 y RF10 cuentan con pruebas automatizadas que verifican sus reglas de negocio, validaciones, permisos y respuestas de la API.

El módulo cuenta con 93 pruebas automatizadas y 489 aserciones superadas en las funcionalidades documentadas.