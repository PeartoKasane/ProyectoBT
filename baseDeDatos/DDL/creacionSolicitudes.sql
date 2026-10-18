/*
    Estructura de tablas para el módulo de solicitudes (RF27 a RF31).
    Un docente puede solicitar la preparación de un laboratorio (instalación de programas)
    o el préstamo de equipos; administrador y técnico gestionan la solicitud.
*/

CREATE TABLE SOLICITUD (
    id INT NOT NULL AUTO_INCREMENT,
    tipo ENUM('Preparación de laboratorio', 'Préstamo') NOT NULL,
    cedulaDocente CHAR(8) NOT NULL,
    idSala INT DEFAULT NULL,
    -- Solo se completa cuando tipo = 'Preparación de laboratorio'
    programa VARCHAR(100) DEFAULT NULL,
    detalle VARCHAR(255) NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    -- RF29: estados de la solicitud
    estado ENUM('Pendiente', 'En proceso', 'Resuelta') NOT NULL DEFAULT 'Pendiente',
    -- RF30: solo un técnico o administrador puede quedar asignado; NULL mientras nadie la tomó
    cedulaAsignado CHAR(8) DEFAULT NULL,
    fechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_solicitud
        PRIMARY KEY (id)
);

-- RF31: comentarios sobre el seguimiento de la solicitud
CREATE TABLE SOLICITUD_COMENTARIO (
    id INT NOT NULL AUTO_INCREMENT,
    solicitud_id INT NOT NULL,
    cedula CHAR(8) DEFAULT NULL,
    comentario VARCHAR(500) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_solicitud_comentario
        PRIMARY KEY (id)
);


ALTER TABLE SOLICITUD
    ADD CONSTRAINT fk_solicitud_docente
    FOREIGN KEY (cedulaDocente)
    REFERENCES USUARIO (cedula);

ALTER TABLE SOLICITUD
    ADD CONSTRAINT fk_solicitud_sala
    FOREIGN KEY (idSala)
    REFERENCES SALA (id);

ALTER TABLE SOLICITUD
    ADD CONSTRAINT fk_solicitud_asignado
    FOREIGN KEY (cedulaAsignado)
    REFERENCES USUARIO (cedula)
    ON DELETE SET NULL;

ALTER TABLE SOLICITUD_COMENTARIO
    ADD CONSTRAINT fk_solicitudComentario_solicitud
    FOREIGN KEY (solicitud_id)
    REFERENCES SOLICITUD (id)
    ON DELETE CASCADE;

ALTER TABLE SOLICITUD_COMENTARIO
    ADD CONSTRAINT fk_solicitudComentario_usuario
    FOREIGN KEY (cedula)
    REFERENCES USUARIO (cedula)
    ON DELETE SET NULL;

-- Historial de acciones de cada solicitud (RF31)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
