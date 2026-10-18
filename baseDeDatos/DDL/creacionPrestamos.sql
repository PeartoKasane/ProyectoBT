/*
    Estructura de tablas para el módulo de préstamos y devoluciones (RF9 a RF16).
    Todo préstamo nace de una Solicitud de tipo 'Préstamo' ya generada por un docente;
    administrador o técnico son quienes registran la entrega real de los equipos y su devolución.
*/

CREATE TABLE PRESTAMO (
    id INT NOT NULL AUTO_INCREMENT,
    solicitud_id INT NOT NULL,
    -- RF12, RF13: identificación de quien retira los equipos (puede ser un estudiante, no necesariamente un usuario del sistema)
    solicitanteNombre VARCHAR(100) NOT NULL,
    solicitanteTipoDocumento ENUM('Cédula', 'Pasaporte', 'Otro') NOT NULL,
    solicitanteDocumento VARCHAR(20) NOT NULL,
    -- Quién gestionó la entrega (administrador o técnico)
    cedulaResponsable CHAR(8) NOT NULL,
    -- RF11: fecha y hora de inicio y finalización del préstamo
    fechaHoraInicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fechaHoraFin DATETIME DEFAULT NULL,
    estado ENUM('Activo', 'Finalizado') NOT NULL DEFAULT 'Activo',

    CONSTRAINT pk_prestamo
        PRIMARY KEY (id)
);

-- RF15: un mismo préstamo puede incluir varios equipos. RF14: "No se usó" se marca al devolver.
CREATE TABLE PRESTAMO_EQUIPO (
    id INT NOT NULL AUTO_INCREMENT,
    prestamo_id INT NOT NULL,
    equipo_id INT NOT NULL,
    noSeUso BOOLEAN NOT NULL DEFAULT FALSE,

    CONSTRAINT pk_prestamo_equipo
        PRIMARY KEY (id)
);


ALTER TABLE PRESTAMO
    ADD CONSTRAINT fk_prestamo_solicitud
    FOREIGN KEY (solicitud_id)
    REFERENCES SOLICITUD (id);

ALTER TABLE PRESTAMO
    ADD CONSTRAINT fk_prestamo_responsable
    FOREIGN KEY (cedulaResponsable)
    REFERENCES USUARIO (cedula);

ALTER TABLE PRESTAMO_EQUIPO
    ADD CONSTRAINT fk_prestamoEquipo_prestamo
    FOREIGN KEY (prestamo_id)
    REFERENCES PRESTAMO (id)
    ON DELETE CASCADE;

ALTER TABLE PRESTAMO_EQUIPO
    ADD CONSTRAINT fk_prestamoEquipo_equipo
    FOREIGN KEY (equipo_id)
    REFERENCES EQUIPO (id);
