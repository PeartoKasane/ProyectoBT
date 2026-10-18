/*
    Espacio donde se deberán colocar todas las sentencias utilizadas para crear las tablas.
*/

CREATE TABLE USUARIO (
    cedula CHAR(8) NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    claveHash VARCHAR(255) NOT NULL,
    -- RF36: permite deshabilitar una cuenta sin eliminarla (por ejemplo, una licencia larga)
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    sesionActiva BOOLEAN NOT NULL DEFAULT FALSE,
    -- Momento en que se inició la sesión activa; sirve para vencerla sola si nadie cerró sesión explícitamente
    inicioSesion DATETIME DEFAULT NULL,

    CONSTRAINT pk_usuario
        PRIMARY KEY (cedula)
);

-- Igual que ADMINISTRADOR/LOGISTICA en sistema-elvis-tek: una tabla por rol, vinculada 1 a 1 con USUARIO
CREATE TABLE ADMINISTRADOR (
    cedula CHAR(8) NOT NULL,

    CONSTRAINT pk_administrador
        PRIMARY KEY (cedula)
);

CREATE TABLE DOCENTE (
    cedula CHAR(8) NOT NULL,

    CONSTRAINT pk_docente
        PRIMARY KEY (cedula)
);

CREATE TABLE DIRECCION (
    cedula CHAR(8) NOT NULL,

    CONSTRAINT pk_direccion
        PRIMARY KEY (cedula)
);

-- El técnico gestiona tickets y solicitudes, pero no usuarios (a diferencia del administrador)
CREATE TABLE TECNICO (
    cedula CHAR(8) NOT NULL,

    CONSTRAINT pk_tecnico
        PRIMARY KEY (cedula)
);


ALTER TABLE ADMINISTRADOR
    ADD CONSTRAINT fk_administrador_usuario
    FOREIGN KEY (cedula)
    REFERENCES USUARIO (cedula);

ALTER TABLE DOCENTE
    ADD CONSTRAINT fk_docente_usuario
    FOREIGN KEY (cedula)
    REFERENCES USUARIO (cedula);

ALTER TABLE DIRECCION
    ADD CONSTRAINT fk_direccion_usuario
    FOREIGN KEY (cedula)
    REFERENCES USUARIO (cedula);

ALTER TABLE TECNICO
    ADD CONSTRAINT fk_tecnico_usuario
    FOREIGN KEY (cedula)
    REFERENCES USUARIO (cedula);
