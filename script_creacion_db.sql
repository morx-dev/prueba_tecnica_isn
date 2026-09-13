-- ============================================================================
-- Proyecto: Sistema de Inventario de Herramientas - Prueba Hogares ISN
-- ============================================================================

CREATE DATABASE IF NOT EXISTS hogares_isn_inventario
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE hogares_isn_inventario;

-- ============================================================================
-- TABLA: talleres
-- Los 5 talleres de la cadena. Todo el sistema esta distribuido por taller.
-- ============================================================================
CREATE TABLE talleres (
    id_taller       INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100) NOT NULL,
    direccion       VARCHAR(200),
    telefono        VARCHAR(20),
    estado          TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = activo, 0 = inactivo',
    fecha_creacion  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================================
-- TABLA: roles
-- Catalogo de roles para el control de usuarios del sistema.
-- ============================================================================
CREATE TABLE roles (
    id_rol      INT AUTO_INCREMENT PRIMARY KEY,
    nombre_rol  VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(150)
) ENGINE=InnoDB;

-- ============================================================================
-- TABLA: usuarios
-- Usuarios que acceden al sistema (administradores, encargados de taller, etc.)
-- ============================================================================
CREATE TABLE usuarios (
    id_usuario      INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(120) NOT NULL,
    usuario         VARCHAR(50) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL COMMENT 'Password almacenado con password_hash() de PHP',
    id_rol          INT NOT NULL,
    id_taller       INT NULL COMMENT 'NULL si el usuario es administrador global',
    estado          TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = activo, 0 = inactivo',
    fecha_creacion  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuarios_rol
        FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_usuarios_taller
        FOREIGN KEY (id_taller) REFERENCES talleres(id_taller)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================================
-- TABLA: mecanicos
-- Mecanicos de cada taller, responsables de las herramientas asignadas.
-- ============================================================================
CREATE TABLE mecanicos (
    id_mecanico     INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(120) NOT NULL,
    codigo_empleado VARCHAR(30) NOT NULL UNIQUE,
    id_taller       INT NOT NULL,
    telefono        VARCHAR(20),
    estado          TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = activo, 0 = inactivo',
    fecha_ingreso   DATE,
    CONSTRAINT fk_mecanicos_taller
        FOREIGN KEY (id_taller) REFERENCES talleres(id_taller)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================================
-- TABLA: herramientas
-- Inventario de herramientas. El campo "estado" indica su situacion actual.
-- ============================================================================
CREATE TABLE herramientas (
    id_herramienta  INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100) NOT NULL,
    medida          VARCHAR(50),
    precio_compra   DECIMAL(10,2) NOT NULL,
    id_taller       INT NOT NULL COMMENT 'Taller donde esta ubicada actualmente',
    estado          ENUM('disponible','asignada','obsoleta','reciclada') NOT NULL DEFAULT 'disponible',
    fecha_ingreso   DATE NOT NULL,
    fecha_creacion  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_herramientas_taller
        FOREIGN KEY (id_taller) REFERENCES talleres(id_taller)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================================
-- TABLA: asignaciones
-- Historial de asignacion de herramientas a mecanicos.
-- Una fila con fecha_devolucion = NULL representa una asignacion activa.
-- ============================================================================
CREATE TABLE asignaciones (
    id_asignacion       INT AUTO_INCREMENT PRIMARY KEY,
    id_herramienta      INT NOT NULL,
    id_mecanico         INT NOT NULL,
    id_usuario_registro INT NOT NULL COMMENT 'Usuario que registro la asignacion',
    fecha_asignacion    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_devolucion    DATETIME NULL,
    estado              ENUM('activa','finalizada') NOT NULL DEFAULT 'activa',
    observaciones       VARCHAR(255),
    CONSTRAINT fk_asignaciones_herramienta
        FOREIGN KEY (id_herramienta) REFERENCES herramientas(id_herramienta)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_asignaciones_mecanico
        FOREIGN KEY (id_mecanico) REFERENCES mecanicos(id_mecanico)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_asignaciones_usuario
        FOREIGN KEY (id_usuario_registro) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================================
-- TABLA: herramientas_obsoletas
-- Registro de herramientas que un mecanico solicito reemplazar.
-- estado = 'en_bodega' mientras no se ha enviado a reciclar.
-- ============================================================================
CREATE TABLE herramientas_obsoletas (
    id_obsoleto           INT AUTO_INCREMENT PRIMARY KEY,
    id_herramienta        INT NOT NULL UNIQUE COMMENT 'Una herramienta solo puede marcarse obsoleta una vez',
    id_mecanico_solicito  INT NULL COMMENT 'Mecanico que solicito el reemplazo',
    id_usuario_registro   INT NOT NULL COMMENT 'Usuario que registro la obsolescencia',
    fecha_obsolescencia   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    motivo                VARCHAR(255),
    estado                ENUM('en_bodega','reciclado') NOT NULL DEFAULT 'en_bodega',
    CONSTRAINT fk_obsoletas_herramienta
        FOREIGN KEY (id_herramienta) REFERENCES herramientas(id_herramienta)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_obsoletas_mecanico
        FOREIGN KEY (id_mecanico_solicito) REFERENCES mecanicos(id_mecanico)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_obsoletas_usuario
        FOREIGN KEY (id_usuario_registro) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================================
-- TABLA: herramientas_recicladas
-- Registro de lo que efectivamente se envio a reciclar, con su valor estimado.
-- ============================================================================
CREATE TABLE herramientas_recicladas (
    id_reciclaje         INT AUTO_INCREMENT PRIMARY KEY,
    id_obsoleto          INT NOT NULL UNIQUE,
    id_usuario_registro  INT NOT NULL,
    fecha_reciclaje      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    peso_kg              DECIMAL(10,2) NULL,
    valor_estimado       DECIMAL(10,2) NULL,
    observaciones        VARCHAR(255),
    CONSTRAINT fk_recicladas_obsoleto
        FOREIGN KEY (id_obsoleto) REFERENCES herramientas_obsoletas(id_obsoleto)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_recicladas_usuario
        FOREIGN KEY (id_usuario_registro) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================================
-- DATOS INICIALES (seed) - roles base del sistema
-- ============================================================================
INSERT INTO roles (nombre_rol, descripcion) VALUES
    ('Administrador', 'Acceso total al sistema, gestiona todos los talleres'),
    ('Encargado de Taller', 'Gestiona el inventario y mecanicos de su propio taller');

-- ============================================================================
-- INDICES adicionales recomendados para consultas frecuentes
-- ============================================================================
CREATE INDEX idx_herramientas_estado ON herramientas(estado);
CREATE INDEX idx_herramientas_taller ON herramientas(id_taller);
CREATE INDEX idx_asignaciones_estado ON asignaciones(estado);
CREATE INDEX idx_asignaciones_mecanico ON asignaciones(id_mecanico);
CREATE INDEX idx_obsoletas_estado ON herramientas_obsoletas(estado);