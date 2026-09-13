# Diccionario de datos — Sistema de Inventario de Herramientas (Hogares ISN)

Base de datos: `hogares_isn_inventario`

## Tabla: talleres
| Campo | Tipo | Nulo | Llave | Descripción |
|---|---|---|---|---|
| id_taller | INT | No | PK | Identificador único del taller |
| nombre | VARCHAR(100) | No | | Nombre del taller |
| direccion | VARCHAR(200) | Sí | | Dirección física del taller |
| telefono | VARCHAR(20) | Sí | | Teléfono de contacto |
| estado | TINYINT(1) | No | | 1 = activo, 0 = inactivo |
| fecha_creacion | DATETIME | No | | Fecha de registro del taller |

## Tabla: roles
| Campo | Tipo | Nulo | Llave | Descripción |
|---|---|---|---|---|
| id_rol | INT | No | PK | Identificador único del rol |
| nombre_rol | VARCHAR(50) | No | UNIQUE | Nombre del rol (Administrador, Encargado de Taller) |
| descripcion | VARCHAR(150) | Sí | | Descripción del alcance del rol |

## Tabla: usuarios
| Campo | Tipo | Nulo | Llave | Descripción |
|---|---|---|---|---|
| id_usuario | INT | No | PK | Identificador único del usuario |
| nombre_completo | VARCHAR(120) | No | | Nombre completo del usuario |
| usuario | VARCHAR(50) | No | UNIQUE | Nombre de usuario para login |
| password_hash | VARCHAR(255) | No | | Contraseña cifrada (password_hash de PHP) |
| id_rol | INT | No | FK -> roles.id_rol | Rol asignado al usuario |
| id_taller | INT | Sí | FK -> talleres.id_taller | Taller al que pertenece (NULL si es admin global) |
| estado | TINYINT(1) | No | | 1 = activo, 0 = inactivo |
| fecha_creacion | DATETIME | No | | Fecha de creación del usuario |

## Tabla: mecanicos
| Campo | Tipo | Nulo | Llave | Descripción |
|---|---|---|---|---|
| id_mecanico | INT | No | PK | Identificador único del mecánico |
| nombre_completo | VARCHAR(120) | No | | Nombre completo del mecánico |
| codigo_empleado | VARCHAR(30) | No | UNIQUE | Código interno de empleado |
| id_taller | INT | No | FK -> talleres.id_taller | Taller al que pertenece |
| telefono | VARCHAR(20) | Sí | | Teléfono de contacto |
| estado | TINYINT(1) | No | | 1 = activo, 0 = inactivo |
| fecha_ingreso | DATE | Sí | | Fecha de ingreso a la empresa |

## Tabla: herramientas
| Campo | Tipo | Nulo | Llave | Descripción |
|---|---|---|---|---|
| id_herramienta | INT | No | PK | Identificador único de la herramienta |
| nombre | VARCHAR(100) | No | | Nombre de la herramienta |
| medida | VARCHAR(50) | Sí | | Medida de la herramienta |
| precio_compra | DECIMAL(10,2) | No | | Precio de compra |
| id_taller | INT | No | FK -> talleres.id_taller | Taller donde está ubicada actualmente |
| estado | ENUM | No | | disponible / asignada / obsoleta / reciclada |
| fecha_ingreso | DATE | No | | Fecha en que ingresó al inventario |
| fecha_creacion | DATETIME | No | | Fecha de registro en el sistema |

## Tabla: asignaciones
| Campo | Tipo | Nulo | Llave | Descripción |
|---|---|---|---|---|
| id_asignacion | INT | No | PK | Identificador único de la asignación |
| id_herramienta | INT | No | FK -> herramientas.id_herramienta | Herramienta asignada |
| id_mecanico | INT | No | FK -> mecanicos.id_mecanico | Mecánico responsable |
| id_usuario_registro | INT | No | FK -> usuarios.id_usuario | Usuario que registró la asignación |
| fecha_asignacion | DATETIME | No | | Fecha en que se asignó la herramienta |
| fecha_devolucion | DATETIME | Sí | | Fecha de devolución (NULL = asignación activa) |
| estado | ENUM | No | | activa / finalizada |
| observaciones | VARCHAR(255) | Sí | | Notas adicionales |

## Tabla: herramientas_obsoletas
| Campo | Tipo | Nulo | Llave | Descripción |
|---|---|---|---|---|
| id_obsoleto | INT | No | PK | Identificador único del registro |
| id_herramienta | INT | No | FK -> herramientas.id_herramienta, UNIQUE | Herramienta marcada como obsoleta |
| id_mecanico_solicito | INT | Sí | FK -> mecanicos.id_mecanico | Mecánico que solicitó el reemplazo |
| id_usuario_registro | INT | No | FK -> usuarios.id_usuario | Usuario que registró la obsolescencia |
| fecha_obsolescencia | DATETIME | No | | Fecha en que se marcó como obsoleta |
| motivo | VARCHAR(255) | Sí | | Motivo del reemplazo |
| estado | ENUM | No | | en_bodega / reciclado |

## Tabla: herramientas_recicladas
| Campo | Tipo | Nulo | Llave | Descripción |
|---|---|---|---|---|
| id_reciclaje | INT | No | PK | Identificador único del registro de reciclaje |
| id_obsoleto | INT | No | FK -> herramientas_obsoletas.id_obsoleto, UNIQUE | Registro de obsolescencia relacionado |
| id_usuario_registro | INT | No | FK -> usuarios.id_usuario | Usuario que registró el reciclaje |
| fecha_reciclaje | DATETIME | No | | Fecha en que se envió a reciclar |
| peso_kg | DECIMAL(10,2) | Sí | | Peso del metal enviado a reciclar |
| valor_estimado | DECIMAL(10,2) | Sí | | Valor estimado del reciclaje |
| observaciones | VARCHAR(255) | Sí | | Notas adicionales |