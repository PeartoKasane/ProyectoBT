/*
    Estructura de tablas para el módulo de gestión de incidencias mediante tickets (RF17 a RF26).
    Un ticket es la planilla que completa un docente al finalizar el uso de una sala: qué grupo,
    asignatura y turno, y el detalle de cada equipo utilizado (si tuvo o no una incidencia).
*/

CREATE TABLE TICKET (
    id INT NOT NULL AUTO_INCREMENT,
    idSala INT NOT NULL,
    fecha DATE NOT NULL,
    horaEntrada TIME NOT NULL,
    horaSalida TIME NOT NULL,
    asignatura VARCHAR(80) NOT NULL,
    cedulaDocente CHAR(8) NOT NULL,
    grupo VARCHAR(20) NOT NULL,
    turno ENUM('Mañana', 'Vespertino', 'Nocturno') NOT NULL,
    -- 0 = más urgente (RF19: clasificar según gravedad). Se calcula solo a partir de las incidencias cargadas.
    prioridad TINYINT NOT NULL DEFAULT 2,
    -- RF21: estados del ticket
    estado ENUM('Pendiente', 'Evaluando', 'En proceso', 'Terminado') NOT NULL DEFAULT 'Pendiente',
    -- RF22: solo un técnico o administrador puede quedar asignado; NULL mientras nadie lo tomó
    cedulaAsignado CHAR(8) DEFAULT NULL,
    fechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_ticket
        PRIMARY KEY (id)
);

-- Detalle de cada equipo declarado en la planilla del ticket (RF17, RF19)
CREATE TABLE TICKET_EQUIPO (
    id INT NOT NULL AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    equipo_id INT NOT NULL,
    utilizado BOOLEAN NOT NULL DEFAULT TRUE,
    estudiante VARCHAR(100) DEFAULT NULL,
    -- NULL si el equipo no tuvo incidencia en esa sesión
    incidencia ENUM('No prende', 'Teclado roto', 'Mouse roto', 'Monitor roto', 'Otro problema') DEFAULT NULL,
    descripcion VARCHAR(255) DEFAULT NULL,

    CONSTRAINT pk_ticket_equipo
        PRIMARY KEY (id)
);

-- RF23: comentarios y diagnósticos sobre la incidencia
CREATE TABLE TICKET_COMENTARIO (
    id INT NOT NULL AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    cedula CHAR(8) DEFAULT NULL,
    comentario VARCHAR(500) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_ticket_comentario
        PRIMARY KEY (id)
);


ALTER TABLE TICKET
    ADD CONSTRAINT fk_ticket_sala
    FOREIGN KEY (idSala)
    REFERENCES SALA (id);

ALTER TABLE TICKET
    ADD CONSTRAINT fk_ticket_docente
    FOREIGN KEY (cedulaDocente)
    REFERENCES USUARIO (cedula);

ALTER TABLE TICKET
    ADD CONSTRAINT fk_ticket_asignado
    FOREIGN KEY (cedulaAsignado)
    REFERENCES USUARIO (cedula)
    ON DELETE SET NULL;

ALTER TABLE TICKET_EQUIPO
    ADD CONSTRAINT fk_ticketEquipo_ticket
    FOREIGN KEY (ticket_id)
    REFERENCES TICKET (id)
    ON DELETE CASCADE;

ALTER TABLE TICKET_EQUIPO
    ADD CONSTRAINT fk_ticketEquipo_equipo
    FOREIGN KEY (equipo_id)
    REFERENCES EQUIPO (id);

ALTER TABLE TICKET_COMENTARIO
    ADD CONSTRAINT fk_ticketComentario_ticket
    FOREIGN KEY (ticket_id)
    REFERENCES TICKET (id)
    ON DELETE CASCADE;

ALTER TABLE TICKET_COMENTARIO
    ADD CONSTRAINT fk_ticketComentario_usuario
    FOREIGN KEY (cedula)
    REFERENCES USUARIO (cedula)
    ON DELETE SET NULL;
