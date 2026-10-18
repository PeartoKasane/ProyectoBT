/*
    Estructura de tablas para el módulo de inventario tecnológico (RF1 a RF8).
    Reemplaza el listado fijo de salas/equipos que existía en JavaScript (SelectSala.js)
    por tablas reales, siguiendo el mismo estilo que USUARIO/ADMINISTRADOR/DOCENTE/DIRECCION.
*/

CREATE TABLE SALA (
    id INT NOT NULL AUTO_INCREMENT,
    tipo ENUM('Laboratorio', 'Taller') NOT NULL,
    numero INT NOT NULL,

    CONSTRAINT pk_sala
        PRIMARY KEY (id),

    CONSTRAINT uq_sala_tipo_numero
        UNIQUE (tipo, numero)
);

-- Un equipo pertenece siempre a una única sala (RF7: asociar múltiples equipos a un laboratorio determinado)
CREATE TABLE EQUIPO (
    id INT NOT NULL AUTO_INCREMENT,
    numero INT NOT NULL,
    idSala INT NOT NULL,
    estado ENUM('Funcionando', 'Incidencia', 'Fuera de servicio') NOT NULL DEFAULT 'Funcionando',
    incidencia VARCHAR(255) DEFAULT NULL,

    CONSTRAINT pk_equipo
        PRIMARY KEY (id),

    CONSTRAINT uq_equipo_sala_numero
        UNIQUE (idSala, numero)
);

ALTER TABLE EQUIPO
    ADD CONSTRAINT fk_equipo_sala
    FOREIGN KEY (idSala)
    REFERENCES SALA (id)
    ON DELETE CASCADE;

-- Historial de utilización y modificaciones de cada equipo (RF5)
CREATE TABLE HISTORIAL_EQUIPO (
    id INT NOT NULL AUTO_INCREMENT,
    equipo_id INT NOT NULL,
    -- Se guarda NULL si el usuario que hizo el cambio fue eliminado más adelante, para no perder el historial
    cedula CHAR(8) DEFAULT NULL,
    accion VARCHAR(30) NOT NULL,
    detalle VARCHAR(255) DEFAULT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_historial_equipo
        PRIMARY KEY (id)
);

ALTER TABLE HISTORIAL_EQUIPO
    ADD CONSTRAINT fk_historialEquipo_equipo
    FOREIGN KEY (equipo_id)
    REFERENCES EQUIPO (id)
    ON DELETE CASCADE;

ALTER TABLE HISTORIAL_EQUIPO
    ADD CONSTRAINT fk_historialEquipo_usuario
    FOREIGN KEY (cedula)
    REFERENCES USUARIO (cedula)
    ON DELETE SET NULL;
