# Sistema de Inventario de Herramientas — Hogares ISN

# LINK DEL REPOSITORIO DE GITHUB: https://github.com/morx-dev/prueba_tecnica_isn.git

Documentación técnica interna del proyecto, desarrollado como prueba técnica para optar a una plaza de desarrollador en Hogares ISN.

## Índice

1. [Introducción](#1-introducción)
2. [Objetivo del sistema](#2-objetivo-del-sistema)
3. [Alcance](#3-alcance)
4. [Tecnologías utilizadas](#4-tecnologías-utilizadas)
5. [Requisitos](#5-requisitos)
6. [Arquitectura](#6-arquitectura)
7. [Estructura del proyecto](#7-estructura-del-proyecto)
8. [Base de datos](#8-base-de-datos)
9. [Módulos](#9-módulos)
10. [Roles y permisos](#10-roles-y-permisos)
11. [Rutas del sistema](#11-rutas-del-sistema)
12. [Autenticación](#12-autenticación)
13. [Reglas de negocio](#13-reglas-de-negocio)
14. [Validaciones](#14-validaciones)
15. [Manejo de errores](#15-manejo-de-errores)
16. [Instalación](#16-instalación)
17. [Configuración](#17-configuración)

---

## 1. Introducción

Una cadena de talleres de servicio automotriz distribuida en 5 sedes gestionaba su inventario de herramientas de forma completamente manual: la ubicación de cada herramienta y el mecánico responsable se llevaban en un control físico. Esto hacía lento e impreciso saber, en cualquier momento, cuántas herramientas existían, quién las tenía, y qué pasaba con las que ya no servían.

Este sistema digitaliza ese control de punta a punta: desde de una herramienta en el inventario, su asignación a un mecánico, hasta su baja por obsolescencia y su reciclaje final — sin depender de ningún framework, como parte de las restricciones técnicas de la prueba.

## 2. Objetivo del sistema

Reemplazar el control manual por una aplicación web que permita, para una cadena de talleres:

- Mantener un inventario centralizado de herramientas (nombre, medida, precio de compra, taller al que pertenecen).
- Registrar qué mecánico tiene actualmente cada herramienta, con historial de asignaciones anteriores.
- Dar de baja herramientas obsoletas cuando un mecánico solicita su reemplazo, sin perder el registro de por qué y cuándo se hizo.
- Registrar el reciclaje de esas herramientas obsoletas, incluyendo el valor estimado recuperado.
- Administrar los mecánicos y talleres de la cadena.
- Controlar el acceso al sistema mediante usuarios con roles diferenciados (Administrador / Encargado de Taller).

## 3. Alcance

**Incluido en esta entrega:**
- CRUD completo de Talleres, Mecánicos, Herramientas y Usuarios.
- Flujo de asignación y devolución de herramientas a mecánicos.
- Flujo de obsolescencia (marcar herramienta como obsoleta) y reciclaje (registrar su valor recuperado).
- Control de acceso basado en roles y en pertenencia a un taller (un Encargado de Taller solo opera sobre su propio taller).
- Autenticación con sesiones PHP nativas y contraseñas cifradas.

**Fuera de alcance (no solicitado por el enunciado):**
- API pública o integración con sistemas externos.
- Reportes exportables (PDF/Excel) del inventario.
- Notificaciones automáticas (correo, SMS) ante eventos como obsolescencia o bajo inventario.
- Interfaz responsive optimizada para dispositivos móviles.

**Restricciones técnicas del proyecto** (impuestas por el enunciado, no por decisión propia):
- PHP 7, orientado a objetos, sin frameworks.
- Metodología MVC implementada manualmente.
- JavaScript puro (sin librerías ni frameworks de frontend).
- Hoja de estilos CSS propia (sin Bootstrap ni similares).
- Base de datos MySQL / MariaDB.

## 4. Tecnologías utilizadas

| Tecnología | Uso en el proyecto |
|---|---|
| PHP 7.4 (POO) | Lenguaje backend, orientado a objetos, con `declare(strict_types=1)` en todos los archivos |
| Apache (imagen `php:7.4-apache`) | Servidor web, con `mod_rewrite` habilitado para rutas amigables |
| MySQL / MariaDB 10.5 | Motor de base de datos |
| PDO (PHP Data Objects) | Acceso a datos, siempre con *prepared statements* — nunca SQL concatenado |
| JavaScript (vanilla, ES6) | Confirmaciones antes de acciones destructivas, auto-cierre de alertas, buscador en vivo, validación de formularios en cliente |
| CSS propio | Hoja de estilos única (`public/css/style.css`), sin frameworks |
| Docker + Docker Compose | Entorno de desarrollo reproducible (contenedor `web` para PHP/Apache, contenedor `db` para MariaDB) |
| MySQL Workbench | Diseño del modelo EER y consultas de verificación durante el desarrollo |

## 5. Requisitos

Para levantar el proyecto en un entorno nuevo se necesita:

- Docker Desktop (o Docker Engine + Docker Compose) instalado y corriendo.
- Puerto **8081** libre en el host (servicio web).
- Puerto **3307** libre en el host (MariaDB, mapeado desde el 3306 del contenedor).
- Un navegador web moderno.
- (Opcional) MySQL Workbench u otro cliente SQL, para inspeccionar la base de datos directamente.

No se requiere tener PHP, Apache ni MySQL instalados de forma local — todo corre dentro de los contenedores.

## 6. Arquitectura

El proyecto sigue el patrón **MVC (Modelo-Vista-Controlador) implementado a mano**, sin ningún framework, cumpliendo la restricción del enunciado.

**Flujo de una petición:**

1. Toda petición entra por `public/index.php` (front controller), gracias a la reescritura de URLs en `public/.htaccess`.
2. `index.php` carga `config/bootstrap.php`, que:
   - Inicia la sesión (`session_start()`).
   - Registra un autoloader (`spl_autoload_register`) que busca la clase solicitada por nombre exacto de archivo dentro de `config/`, `core/`, `models/` y `controllers/` — sin depender de `strtolower()` (para funcionar igual en un servidor Linux, que distingue mayúsculas de minúsculas) ni de namespaces.
3. `index.php` interpreta la URL (`/controlador/metodo/parametros`) e instancia el controlador correspondiente, llamando al método indicado.
4. El **controlador**:
   - Valida sesión y permisos (`Auth::requerirLogin()` / `Auth::requerirRol()` / `Auth::perteneceATaller()`).
   - Recibe y valida los datos de entrada (`$_POST`, `$_GET`).
   - Delega el acceso a datos a uno o varios **modelos**.
   - Decide qué **vista** renderizar, pasándole las variables que esa vista necesita.
5. El **modelo** habla con la base de datos exclusivamente vía PDO con *prepared statements*. Los modelos que modifican más de una tabla a la vez (asignar/devolver herramienta, marcar obsoleta, reciclar) lo hacen dentro de una **transacción** (`beginTransaction` / `commit` / `rollBack`), para que nunca quede un cambio a medias.
6. La **vista** es un archivo PHP con HTML, sin lógica de negocio — solo recorre los datos que el controlador le entregó. Las vistas comparten dos parciales: `views/partials/navbar.php` (menú y usuario logueado) y `views/partials/footer.php` (carga del script JS único).

**Componentes de soporte:**
- `core/Auth.php`: helper estático para todo lo relacionado a sesión, roles y pertenencia a un taller.
- `public/css/style.css` y `public/js/main.js`: un solo archivo de cada uno, compartido por todas las vistas.

## 7. Estructura del proyecto

```
evaluacion_tecnica/
├── config/
│   ├── bootstrap.php        # Autoload de clases + inicio de sesión
│   └── database.php         # Clase Database: conexión PDO centralizada
├── controllers/
│   ├── TallerController.php
│   ├── MecanicoController.php
│   ├── HerramientaController.php
│   ├── ObsolescenciaController.php
│   └── UsuarioController.php
├── core/
│   └── Auth.php             # Helper estático de autenticación y permisos
├── models/
│   ├── TallerModel.php
│   ├── MecanicoModel.php
│   ├── HerramientaModel.php
│   ├── AsignacionModel.php
│   ├── HerramientaObsoletaModel.php
│   ├── HerramientaRecicladaModel.php
│   └── UsuarioModel.php
├── views/
│   ├── partials/
│   │   ├── navbar.php
│   │   └── footer.php
│   ├── taller/
│   ├── mecanico/
│   ├── herramienta/
│   ├── obsolescencia/
│   └── usuario/
├── public/                  # DocumentRoot de Apache (ver Dockerfile)
│   ├── css/style.css
│   ├── js/main.js
│   ├── index.php            # Front controller / router
│   └── .htaccess            # Reescritura de URLs
├── docker-compose.yml
├── Dockerfile
├── diccionario_datos.md     # Diccionario de datos
└── README.md                # Este documento
```

## 8. Base de datos

Motor: MySQL / MariaDB. Base de datos: `hogares_isn_inventario`. El script de creación completo está en `script_creacion_db.sql`, y el detalle campo por campo en `diccionario_datos.md`. El modelo EER se entrega como imagen exportada desde MySQL Workbench.

| Tabla | Propósito |
|---|---|
| `talleres` | Los talleres de la cadena. Tabla raíz de la que dependen usuarios, mecánicos y herramientas |
| `roles` | Catálogo de roles del sistema (Administrador, Encargado de Taller) |
| `usuarios` | Cuentas de acceso al sistema, con contraseña cifrada y rol/taller asociado |
| `mecanicos` | Mecánicos de cada taller, responsables de las herramientas que tienen asignadas |
| `herramientas` | Inventario. Su columna `estado` (`disponible` / `asignada` / `obsoleta` / `reciclada`) refleja en qué punto del ciclo de vida está cada una |
| `asignaciones` | Historial de préstamos de herramientas a mecánicos (una fila por cada vez que se asignó) |
| `herramientas_obsoletas` | Registro de herramientas dadas de baja, con quién la solicitó y por qué |
| `herramientas_recicladas` | Registro del reciclaje efectivo de un obsoleto, con su valor estimado |

## 9. Módulos

- **Talleres**: alta, edición y activación/desactivación (borrado lógico) de las sedes de la cadena.
- **Mecánicos**: alta, edición y activación/desactivación, siempre ligados a un taller.
- **Herramientas**: alta y edición del catálogo, más las acciones de **asignar** y **devolver** una herramienta a/de un mecánico.
- **Obsolescencia y reciclaje**: marcar una herramienta como obsoleta (cerrando su asignación activa si la tenía) y, después, registrar su reciclaje con peso y valor estimado. Incluye una vista de "bodega" (pendientes) y un historial de ya reciclados.
- **Usuarios**: login, logout, y administración de cuentas (solo Administrador).

## 10. Roles y permisos

El sistema tiene dos roles: **Administrador** (`id_rol = 1`) y **Encargado de Taller** (`id_rol = 2`).

| Módulo | Administrador | Encargado de Taller |
|---|---|---|
| Talleres | Ve y gestiona todos | Solo puede *ver* el listado |
| Usuarios | Acceso total | Sin acceso |
| Mecánicos | Ve y gestiona todos, de cualquier taller | Ve y gestiona solo los de su propio taller |
| Herramientas | Ve y gestiona todas, de cualquier taller | Ve y gestiona solo las de su propio taller |
| Asignar herramienta | A cualquier mecánico activo de cualquier taller | Solo a mecánicos activos de su propio taller |
| Obsolescencia / Reciclaje | Sobre cualquier herramienta | Solo sobre las de su propio taller |

La restricción no es solo visual: cada acción del controlador vuelve a validar el permiso en el servidor (`Auth::requerirRol()`, `Auth::perteneceATaller()`), así que entrar por URL directa a un recurso ajeno responde con **403 Forbidden**, no solo se oculta el botón en la vista.

## 11. Rutas del sistema

El router (`public/index.php`) interpreta la URL como `/controlador/metodo/parametro`. No hay una API REST — todas las rutas devuelven vistas HTML.

| Ruta | Acción |
|---|---|
| `/usuario/login` | Formulario de acceso (GET/POST) — única ruta pública |
| `/usuario/logout` | Cierra la sesión |
| `/usuario/index`, `/usuario/crear`, `/usuario/desactivar/{id}`, `/usuario/activar/{id}` | Gestión de usuarios (solo Administrador) |
| `/taller/index`, `/taller/crear`, `/taller/editar/{id}`, `/taller/desactivar/{id}`, `/taller/activar/{id}` | Gestión de talleres |
| `/mecanico/index`, `/mecanico/crear`, `/mecanico/editar/{id}`, `/mecanico/desactivar/{id}`, `/mecanico/activar/{id}` | Gestión de mecánicos |
| `/herramienta/index`, `/herramienta/crear`, `/herramienta/editar/{id}`, `/herramienta/asignar/{id}`, `/herramienta/devolver/{id}` | Inventario y asignación de herramientas |
| `/obsolescencia/index`, `/obsolescencia/marcar/{id}`, `/obsolescencia/reciclar/{id}`, `/obsolescencia/historial` | Obsolescencia y reciclaje |

## 12. Autenticación

- Las contraseñas se guardan con `password_hash()` (`PASSWORD_BCRYPT`) y se verifican con `password_verify()` — nunca en texto plano ni con hashes reversibles.
- Al iniciar sesión correctamente se llama a `session_regenerate_id(true)`, para prevenir *session fixation*.
- La sesión guarda `id_usuario`, `nombre_completo`, `id_rol` e `id_taller`.
- `core/Auth.php` centraliza toda la lógica de sesión:
  - `Auth::estaLogueado()`
  - `Auth::requerirLogin()` — corta la ejecución y redirige a `/usuario/login` si no hay sesión.
  - `Auth::requerirRol(array $roles)` — además exige que el rol esté en la lista permitida (403 si no).
  - `Auth::esAdministrador()`
  - `Auth::perteneceATaller(int $idTaller)` — un administrador pertenece a cualquier taller; un encargado, solo al suyo.
  - `Auth::usuarioActual()` — datos del usuario logueado, usados por ejemplo en el navbar.

## 13. Reglas de negocio

- **Ciclo de vida de una herramienta**: `disponible` → `asignada` → `obsoleta` → `reciclada`. Son estados secuenciales y no se puede saltar hacia atrás (una herramienta reciclada no vuelve a estar disponible).
- **Marcar obsoleta cierra la asignación activa automáticamente**: si la herramienta estaba asignada, no hace falta "devolverla" primero — el sistema detecta la asignación activa, la cierra, y usa a ese mecánico como el "solicitante" del reemplazo en el mismo paso.
- **Borrado lógico, no físico**: talleres, mecánicos y usuarios se desactivan (`estado = 0`), nunca se eliminan de la base de datos — se preserva el historial de asignaciones y auditoría.
- **No se puede desactivar un mecánico con herramientas asignadas activas**: primero hay que reasignar o devolver esas herramientas.
- **Un Encargado de Taller no puede mover mecánicos ni herramientas fuera de su propio taller**, ni siquiera manipulando el formulario — el campo `id_taller` para ese rol nunca sale del `$_POST`, sale de su sesión.
- **Solo se asigna a mecánicos activos** del mismo taller que la herramienta.

## 14. Validaciones

Todas las validaciones se hacen **en el servidor** (dentro de cada controlador), independientemente de la validación `required` de HTML5 o la de JavaScript en el cliente, que son solo de experiencia de usuario:

- Campos obligatorios no vacíos (nombre, dirección, usuario, contraseña, etc.).
- `codigo_empleado` (mecánicos) y `usuario` (usuarios) deben ser únicos — con exclusión del propio registro al editar, para no marcarlo como "duplicado de sí mismo".
- Contraseña con mínimo 6 caracteres.
- Precio de compra de una herramienta mayor a cero.
- Debe seleccionarse un taller y, según el módulo, un rol o un mecánico válidos.
- Una herramienta solo puede asignarse si su estado actual es `disponible`; solo puede marcarse obsoleta si está en `disponible` o `asignada`; un obsoleto solo puede reciclarse si sigue `en_bodega`.

## 15. Manejo de errores

- **Conexión a base de datos**: si `Database::getConnection()` falla, se registra el detalle con `error_log()` y se corta la ejecución con un mensaje genérico al usuario (nunca se expone el error real de PDO).
- **Operaciones multi-tabla**: envueltas en transacciones PDO; ante una `PDOException` se hace `rollBack()`, se registra el error con `error_log()`, y el controlador informa al usuario mediante un mensaje flash de error, sin dejar datos a medio guardar.
- **Accesos no autorizados**: `Auth::requerirRol()` y las validaciones de `perteneceATaller()` responden con código HTTP **403** y un mensaje claro cuando alguien intenta una acción o un recurso fuera de su permiso.
- **Recursos inexistentes**: si un `id` en la URL no corresponde a ningún registro, se redirige al listado correspondiente con un mensaje flash de error, en vez de mostrar un error de base de datos.
- **Mensajes flash**: patrón consistente en todos los controladores (`guardarMensajeFlash()` / `obtenerMensajeFlash()` vía `$_SESSION['flash']`) para mostrar confirmaciones y errores de negocio después de un redirect.

## 16. Instalación

1. Cloná o descomprimí el proyecto en tu máquina.
2. Verificá que los puertos **8081** y **3307** estén libres.
3. Desde la raíz del proyecto, construí y levantá los contenedores:
   ```
   docker compose up -d --build
   ```
4. Verificá que el contenedor de PHP quedó en la versión correcta:
   ```
   docker compose exec web php -v
   ```
   Debe mostrar PHP 7.4.x.
5. La base de datos `hogares_isn_inventario` se crea automáticamente al levantar el contenedor `db` (variable `MYSQL_DATABASE` en `docker-compose.yml`). Importá el script de estructura y datos:
   ```
   docker compose exec -T db mysql -uroot -prootpassword hogares_isn_inventario < script_creacion_db.sql
   ```
6. **Creación del primer usuario administrador** (paso obligatorio la primera vez): la ruta `/usuario/crear` exige estar logueado como Administrador, así que el primer usuario debe insertarse directamente en la base de datos, con una contraseña generada por `password_hash()`. Ver la sección de Configuración para el detalle de este paso.
7. Accedé a `http://localhost:8081/usuario/login` e iniciá sesión con el usuario administrador creado en el paso anterior.

## 17. Configuración

**Credenciales de base de datos** (`config/database.php`):
```php
host: localhost
db_name: hogares_isn_inventario
username: root
password: (vacío)
```
Estos valores deben coincidir con el `MYSQL_DATABASE` definido en `docker-compose.yml`.

**Puertos** (`docker-compose.yml`):
- `8081` → servicio web (Apache/PHP), mapeado al puerto 80 del contenedor.
- `3307` → MariaDB, mapeado al puerto 3306 del contenedor (para conectarse desde MySQL Workbench u otro cliente externo, usando `localhost:3307`).

**Creación del primer usuario administrador**, dado que `/usuario/crear` requiere sesión de Administrador (ver Instalación, paso 6):

1. Generá un hash de contraseña válido ejecutando temporalmente en PHP:
   ```php
   echo password_hash('tu_contraseña', PASSWORD_BCRYPT);
   ```
2. Insertalo directamente en la base de datos:
   ```sql
   INSERT INTO usuarios (nombre_completo, usuario, password_hash, id_rol, id_taller, estado, fecha_creacion)
   VALUES ('Administrador General', 'admin', 'HASH_GENERADO_AQUI', 1, NULL, 1, NOW());
   ```
3. A partir de este primer usuario, el resto de las cuentas se crean normalmente desde `/usuario/crear`.