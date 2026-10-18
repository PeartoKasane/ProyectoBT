-- Creación e inserción para la base de datos de PEARTO S.A.
-- Estructura de roles normalizada al estilo de sistema-elvis-tek (una tabla por rol en vez de un ENUM).
--
-- Se puede ejecutar todas las veces que haga falta, tanto en un servidor nuevo como sobre la base que ya está en uso:
--  - Las tablas se crean con IF NOT EXISTS (no se borra ni modifica nada existente).
--  - El procedimiento y los triggers se vuelven a crear con DROP ... IF EXISTS.
--  - Los datos de prueba solo se insertan si todavía no existen.
-- El nombre de la base (PeartoS.A) es el mismo que DB_NOMBRE en el archivo .env.
CREATE DATABASE IF NOT EXISTS `PeartoS.A` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `PeartoS.A`;

-- DDL: Estructura de las tablas
CREATE TABLE IF NOT EXISTS `USUARIO` (
    `cedula` CHAR(8) NOT NULL,
    `nombre` VARCHAR(50) NOT NULL,
    `apellido` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    -- Se completa sola mediante los triggers de más abajo, a partir de las tablas ADMINISTRADOR, DOCENTE, DIRECCION y TECNICO
    `rol` VARCHAR(20) NOT NULL DEFAULT 'Sin rol',
    `claveHash` VARCHAR(255) NOT NULL,
    `activo` BOOLEAN NOT NULL DEFAULT TRUE,
    -- Columna calculada: no se guarda ni se escribe, siempre refleja el valor de activo
    `estado` VARCHAR(13) AS (IF(`activo`, 'Activo', 'Deshabilitado')) VIRTUAL,
    `sesionActiva` BOOLEAN NOT NULL DEFAULT FALSE,
    `inicioSesion` DATETIME DEFAULT NULL,
    CONSTRAINT `pk_usuario` PRIMARY KEY (`cedula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ADMINISTRADOR` (
    `cedula` CHAR(8) NOT NULL,
    CONSTRAINT `pk_administrador` PRIMARY KEY (`cedula`),
    CONSTRAINT `fk_administrador_usuario` FOREIGN KEY (`cedula`) REFERENCES `USUARIO` (`cedula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `DOCENTE` (
    `cedula` CHAR(8) NOT NULL,
    CONSTRAINT `pk_docente` PRIMARY KEY (`cedula`),
    CONSTRAINT `fk_docente_usuario` FOREIGN KEY (`cedula`) REFERENCES `USUARIO` (`cedula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `DIRECCION` (
    `cedula` CHAR(8) NOT NULL,
    CONSTRAINT `pk_direccion` PRIMARY KEY (`cedula`),
    CONSTRAINT `fk_direccion_usuario` FOREIGN KEY (`cedula`) REFERENCES `USUARIO` (`cedula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- El técnico gestiona tickets y solicitudes, pero no usuarios (a diferencia del administrador)
CREATE TABLE IF NOT EXISTS `TECNICO` (
    `cedula` CHAR(8) NOT NULL,
    CONSTRAINT `pk_tecnico` PRIMARY KEY (`cedula`),
    CONSTRAINT `fk_tecnico_usuario` FOREIGN KEY (`cedula`) REFERENCES `USUARIO` (`cedula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Rol del usuario: se recalcula en USUARIO.rol cada vez que se agrega o quita un registro de una tabla de rol
DROP PROCEDURE IF EXISTS `sp_actualizar_rol`;
CREATE PROCEDURE `sp_actualizar_rol`(IN p_cedula CHAR(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci)
    UPDATE `USUARIO` SET `rol` = CASE
        WHEN EXISTS (SELECT 1 FROM `ADMINISTRADOR` WHERE `cedula` = p_cedula) THEN 'Administrador'
        WHEN EXISTS (SELECT 1 FROM `DOCENTE`       WHERE `cedula` = p_cedula) THEN 'Docente'
        WHEN EXISTS (SELECT 1 FROM `DIRECCION`     WHERE `cedula` = p_cedula) THEN 'Dirección'
        WHEN EXISTS (SELECT 1 FROM `TECNICO`       WHERE `cedula` = p_cedula) THEN 'Técnico'
        ELSE 'Sin rol'
    END
    WHERE `cedula` = p_cedula;

DROP TRIGGER IF EXISTS `trg_administrador_ai`;
DROP TRIGGER IF EXISTS `trg_administrador_ad`;
DROP TRIGGER IF EXISTS `trg_docente_ai`;
DROP TRIGGER IF EXISTS `trg_docente_ad`;
DROP TRIGGER IF EXISTS `trg_direccion_ai`;
DROP TRIGGER IF EXISTS `trg_direccion_ad`;
DROP TRIGGER IF EXISTS `trg_tecnico_ai`;
DROP TRIGGER IF EXISTS `trg_tecnico_ad`;

CREATE TRIGGER `trg_administrador_ai` AFTER INSERT ON `ADMINISTRADOR` FOR EACH ROW CALL `sp_actualizar_rol`(NEW.`cedula`);
CREATE TRIGGER `trg_administrador_ad` AFTER DELETE ON `ADMINISTRADOR` FOR EACH ROW CALL `sp_actualizar_rol`(OLD.`cedula`);
CREATE TRIGGER `trg_docente_ai`       AFTER INSERT ON `DOCENTE`       FOR EACH ROW CALL `sp_actualizar_rol`(NEW.`cedula`);
CREATE TRIGGER `trg_docente_ad`       AFTER DELETE ON `DOCENTE`       FOR EACH ROW CALL `sp_actualizar_rol`(OLD.`cedula`);
CREATE TRIGGER `trg_direccion_ai`     AFTER INSERT ON `DIRECCION`     FOR EACH ROW CALL `sp_actualizar_rol`(NEW.`cedula`);
CREATE TRIGGER `trg_direccion_ad`     AFTER DELETE ON `DIRECCION`     FOR EACH ROW CALL `sp_actualizar_rol`(OLD.`cedula`);
CREATE TRIGGER `trg_tecnico_ai`       AFTER INSERT ON `TECNICO`       FOR EACH ROW CALL `sp_actualizar_rol`(NEW.`cedula`);
CREATE TRIGGER `trg_tecnico_ad`       AFTER DELETE ON `TECNICO`       FOR EACH ROW CALL `sp_actualizar_rol`(OLD.`cedula`);

-- DML: Insertar usuarios de prueba por defecto para SGRSI
-- Las claves están encriptadas con BCRYPT (la clave plana de todos es "clave1234567")
INSERT INTO `USUARIO` (`cedula`, `nombre`, `apellido`, `email`, `claveHash`) VALUES
('11111111', 'Admin', 'SGRSI', 'admin@pearto.com', '$2y$12$ki0bVkt8cnZuR4v6aJvhhelaeQc1/4fec2txUcuG1Ybr4cvnhg2sS'),
('22222222', 'Carlos', 'Ponez', 'carlos@pearto.com', '$2y$12$ki0bVkt8cnZuR4v6aJvhhelaeQc1/4fec2txUcuG1Ybr4cvnhg2sS'),
('33333333', 'Ana', 'Bolivia', 'ana@pearto.com', '$2y$12$ki0bVkt8cnZuR4v6aJvhhelaeQc1/4fec2txUcuG1Ybr4cvnhg2sS'),
('44444444', 'Marcos', 'Sarma', 'marcos@pearto.com', '$2y$12$ki0bVkt8cnZuR4v6aJvhhelaeQc1/4fec2txUcuG1Ybr4cvnhg2sS')
ON DUPLICATE KEY UPDATE `cedula` = `cedula`;

INSERT IGNORE INTO `ADMINISTRADOR` (`cedula`) VALUES ('11111111');
INSERT IGNORE INTO `DOCENTE` (`cedula`) VALUES ('22222222');
INSERT IGNORE INTO `DIRECCION` (`cedula`) VALUES ('33333333');
INSERT IGNORE INTO `TECNICO` (`cedula`) VALUES ('44444444');

-- Usuarios de prueba adicionales para verificar las reglas del sistema (misma clave: "clave1234567")
-- 55555555: segundo docente. 66666666: cuenta deshabilitada (RF36) y sin correo. 77777777: segundo administrador.
-- El rol se completa solo (triggers) al insertar en ADMINISTRADOR, DOCENTE, DIRECCION o TECNICO.
INSERT INTO `USUARIO` (`cedula`, `nombre`, `apellido`, `email`, `claveHash`, `activo`) VALUES
('55555555', 'Lucía', 'Parnos', 'lucia@pearto.com', '$2y$12$ki0bVkt8cnZuR4v6aJvhhelaeQc1/4fec2txUcuG1Ybr4cvnhg2sS', TRUE),
('66666666', 'Pedro', 'Tenpos', NULL, '$2y$12$ki0bVkt8cnZuR4v6aJvhhelaeQc1/4fec2txUcuG1Ybr4cvnhg2sS', FALSE),
('77777777', 'Laura', 'Naidm', 'laura@pearto.com', '$2y$12$ki0bVkt8cnZuR4v6aJvhhelaeQc1/4fec2txUcuG1Ybr4cvnhg2sS', TRUE)
ON DUPLICATE KEY UPDATE `cedula` = `cedula`;

INSERT IGNORE INTO `DOCENTE` (`cedula`) VALUES ('55555555');
INSERT IGNORE INTO `DOCENTE` (`cedula`) VALUES ('66666666');
INSERT IGNORE INTO `ADMINISTRADOR` (`cedula`) VALUES ('77777777');

-- Módulo de inventario tecnológico (RF1 a RF8)
CREATE TABLE IF NOT EXISTS `SALA` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `tipo` ENUM('Laboratorio', 'Taller') NOT NULL,
    `numero` INT NOT NULL,
    CONSTRAINT `pk_sala` PRIMARY KEY (`id`),
    CONSTRAINT `uq_sala_tipo_numero` UNIQUE (`tipo`, `numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `EQUIPO` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `numero` INT NOT NULL,
    `idSala` INT NOT NULL,
    `estado` ENUM('Funcionando', 'Incidencia', 'Fuera de servicio') NOT NULL DEFAULT 'Funcionando',
    `incidencia` VARCHAR(255) DEFAULT NULL,
    CONSTRAINT `pk_equipo` PRIMARY KEY (`id`),
    CONSTRAINT `uq_equipo_sala_numero` UNIQUE (`idSala`, `numero`),
    CONSTRAINT `fk_equipo_sala` FOREIGN KEY (`idSala`) REFERENCES `SALA` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Historial de utilización y modificaciones de cada equipo (RF5)
CREATE TABLE IF NOT EXISTS `HISTORIAL_EQUIPO` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `equipo_id` INT NOT NULL,
    `cedula` CHAR(8) DEFAULT NULL,
    `accion` VARCHAR(30) NOT NULL,
    `detalle` VARCHAR(255) DEFAULT NULL,
    `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `pk_historial_equipo` PRIMARY KEY (`id`),
    CONSTRAINT `fk_historialEquipo_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `EQUIPO` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_historialEquipo_usuario` FOREIGN KEY (`cedula`) REFERENCES `USUARIO` (`cedula`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `SALA` (`tipo`, `numero`)
SELECT * FROM (SELECT 'Laboratorio' AS tipo, 1 AS numero
    UNION ALL SELECT 'Laboratorio', 2
    UNION ALL SELECT 'Laboratorio', 3
    UNION ALL SELECT 'Laboratorio', 4
    UNION ALL SELECT 'Laboratorio', 5
    UNION ALL SELECT 'Laboratorio', 6
    UNION ALL SELECT 'Taller', 1
    UNION ALL SELECT 'Taller', 2
    UNION ALL SELECT 'Taller', 3) AS salasIniciales
WHERE NOT EXISTS (SELECT 1 FROM `SALA`);

-- Módulo de tickets de incidencias (RF17-RF26)
CREATE TABLE IF NOT EXISTS `TICKET` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `idSala` INT NOT NULL,
    `fecha` DATE NOT NULL,
    `horaEntrada` TIME NOT NULL,
    `horaSalida` TIME NOT NULL,
    `asignatura` VARCHAR(80) NOT NULL,
    `cedulaDocente` CHAR(8) NOT NULL,
    `grupo` VARCHAR(20) NOT NULL,
    `turno` ENUM('Mañana', 'Vespertino', 'Nocturno') NOT NULL,
    `prioridad` TINYINT NOT NULL DEFAULT 2,
    `estado` ENUM('Pendiente', 'Evaluando', 'En proceso', 'Terminado') NOT NULL DEFAULT 'Pendiente',
    `cedulaAsignado` CHAR(8) DEFAULT NULL,
    `fechaCreacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `pk_ticket` PRIMARY KEY (`id`),
    CONSTRAINT `fk_ticket_sala` FOREIGN KEY (`idSala`) REFERENCES `SALA` (`id`),
    CONSTRAINT `fk_ticket_docente` FOREIGN KEY (`cedulaDocente`) REFERENCES `USUARIO` (`cedula`),
    CONSTRAINT `fk_ticket_asignado` FOREIGN KEY (`cedulaAsignado`) REFERENCES `USUARIO` (`cedula`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `TICKET_EQUIPO` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `ticket_id` INT NOT NULL,
    `equipo_id` INT NOT NULL,
    `utilizado` BOOLEAN NOT NULL DEFAULT TRUE,
    `estudiante` VARCHAR(100) DEFAULT NULL,
    `incidencia` ENUM('No prende', 'Teclado roto', 'Mouse roto', 'Monitor roto', 'Otro problema') DEFAULT NULL,
    `descripcion` VARCHAR(255) DEFAULT NULL,
    CONSTRAINT `pk_ticket_equipo` PRIMARY KEY (`id`),
    CONSTRAINT `fk_ticketEquipo_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `TICKET` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ticketEquipo_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `EQUIPO` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `TICKET_COMENTARIO` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `ticket_id` INT NOT NULL,
    `cedula` CHAR(8) DEFAULT NULL,
    `comentario` VARCHAR(500) NOT NULL,
    `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `pk_ticket_comentario` PRIMARY KEY (`id`),
    CONSTRAINT `fk_ticketComentario_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `TICKET` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ticketComentario_usuario` FOREIGN KEY (`cedula`) REFERENCES `USUARIO` (`cedula`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Módulo de solicitudes (RF27-RF31)
CREATE TABLE IF NOT EXISTS `SOLICITUD` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `tipo` ENUM('Preparación de laboratorio', 'Préstamo') NOT NULL,
    `cedulaDocente` CHAR(8) NOT NULL,
    `idSala` INT DEFAULT NULL,
    `programa` VARCHAR(100) DEFAULT NULL,
    `detalle` VARCHAR(255) NOT NULL,
    `fecha` DATE NOT NULL,
    `hora` TIME NOT NULL,
    `estado` ENUM('Pendiente', 'En proceso', 'Resuelta') NOT NULL DEFAULT 'Pendiente',
    `cedulaAsignado` CHAR(8) DEFAULT NULL,
    `fechaCreacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `pk_solicitud` PRIMARY KEY (`id`),
    CONSTRAINT `fk_solicitud_docente` FOREIGN KEY (`cedulaDocente`) REFERENCES `USUARIO` (`cedula`),
    CONSTRAINT `fk_solicitud_sala` FOREIGN KEY (`idSala`) REFERENCES `SALA` (`id`),
    CONSTRAINT `fk_solicitud_asignado` FOREIGN KEY (`cedulaAsignado`) REFERENCES `USUARIO` (`cedula`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `SOLICITUD_COMENTARIO` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `solicitud_id` INT NOT NULL,
    `cedula` CHAR(8) DEFAULT NULL,
    `comentario` VARCHAR(500) NOT NULL,
    `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `pk_solicitud_comentario` PRIMARY KEY (`id`),
    CONSTRAINT `fk_solicitudComentario_solicitud` FOREIGN KEY (`solicitud_id`) REFERENCES `SOLICITUD` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_solicitudComentario_usuario` FOREIGN KEY (`cedula`) REFERENCES `USUARIO` (`cedula`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Módulo de préstamos y devoluciones (RF9-RF16)
CREATE TABLE IF NOT EXISTS `PRESTAMO` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `solicitud_id` INT NOT NULL,
    `solicitanteNombre` VARCHAR(100) NOT NULL,
    `solicitanteTipoDocumento` ENUM('Cédula', 'Pasaporte', 'Otro') NOT NULL,
    `solicitanteDocumento` VARCHAR(20) NOT NULL,
    `cedulaResponsable` CHAR(8) NOT NULL,
    `fechaHoraInicio` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fechaHoraFin` DATETIME DEFAULT NULL,
    `estado` ENUM('Activo', 'Finalizado') NOT NULL DEFAULT 'Activo',
    CONSTRAINT `pk_prestamo` PRIMARY KEY (`id`),
    CONSTRAINT `fk_prestamo_solicitud` FOREIGN KEY (`solicitud_id`) REFERENCES `SOLICITUD` (`id`),
    CONSTRAINT `fk_prestamo_responsable` FOREIGN KEY (`cedulaResponsable`) REFERENCES `USUARIO` (`cedula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `PRESTAMO_EQUIPO` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `prestamo_id` INT NOT NULL,
    `equipo_id` INT NOT NULL,
    `noSeUso` BOOLEAN NOT NULL DEFAULT FALSE,
    CONSTRAINT `pk_prestamo_equipo` PRIMARY KEY (`id`),
    CONSTRAINT `fk_prestamoEquipo_prestamo` FOREIGN KEY (`prestamo_id`) REFERENCES `PRESTAMO` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_prestamoEquipo_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `EQUIPO` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Historial de acciones de cada solicitud (RF31). Lo escribe SolicitudDAO::registrarEvento() al crear o modificar una solicitud
CREATE TABLE IF NOT EXISTS `HISTORIAL_SOLICITUD` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `solicitud_id` INT NOT NULL,
    `cedula` CHAR(8) DEFAULT NULL,
    `accion` VARCHAR(30) NOT NULL,
    `detalle` VARCHAR(255) DEFAULT NULL,
    `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `pk_historial_solicitud` PRIMARY KEY (`id`),
    CONSTRAINT `fk_historialSolicitud_solicitud` FOREIGN KEY (`solicitud_id`) REFERENCES `SOLICITUD` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_historialSolicitud_usuario` FOREIGN KEY (`cedula`) REFERENCES `USUARIO` (`cedula`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Registro de actividad de los usuarios para trazabilidad (RNF4). Lo escribe Actividad::programarRegistro() en cada operación que modifica datos
CREATE TABLE IF NOT EXISTS `ACTIVIDAD` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `cedula` CHAR(8) DEFAULT NULL,
    `metodo` VARCHAR(10) NOT NULL,
    `ruta` VARCHAR(30) NOT NULL,
    `recurso` VARCHAR(30) DEFAULT NULL,
    `detalle` VARCHAR(255) DEFAULT NULL,
    `estado` SMALLINT NOT NULL,
    `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `pk_actividad` PRIMARY KEY (`id`),
    CONSTRAINT `fk_actividad_usuario` FOREIGN KEY (`cedula`) REFERENCES `USUARIO` (`cedula`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Mantenimiento: libera las sesiones que quedaron marcadas como activas (RF37), por ejemplo por cerrar la pestaña sin "Cerrar sesión".
-- Si se ejecuta este archivo con usuarios conectados, deberán volver a iniciar sesión.
UPDATE `USUARIO` SET `sesionActiva` = FALSE, `inicioSesion` = NULL WHERE `sesionActiva` = TRUE;

-- DQL: Consultas base del sistema (equivalentes a las de baseDeDatos/DQL/consultaUsuarios.sql)
-- Obtener usuario por cédula junto con sus roles
-- SELECT u.cedula, u.nombre, u.apellido, u.email, u.claveHash, u.activo, u.sesionActiva, u.inicioSesion,
--        CASE WHEN a.cedula IS NOT NULL THEN 1 ELSE 0 END AS administrador,
--        CASE WHEN d.cedula IS NOT NULL THEN 1 ELSE 0 END AS docente,
--        CASE WHEN dir.cedula IS NOT NULL THEN 1 ELSE 0 END AS direccion,
--        CASE WHEN t.cedula IS NOT NULL THEN 1 ELSE 0 END AS tecnico
-- FROM USUARIO u
-- LEFT JOIN ADMINISTRADOR a ON a.cedula = u.cedula
-- LEFT JOIN DOCENTE d ON d.cedula = u.cedula
-- LEFT JOIN DIRECCION dir ON dir.cedula = u.cedula
-- LEFT JOIN TECNICO t ON t.cedula = u.cedula
-- WHERE u.cedula = '11111111';
