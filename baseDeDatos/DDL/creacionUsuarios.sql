CREATE TABLE USUARIO (
    cedula CHAR(8) NOT NULL,
    claveHash VARCHAR(255) NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT pk_usuario
        PRIMARY KEY (cedula)
);

CREATE TABLE ADMINISTRADOR (
    cedula CHAR(8) NOT NULL,

    CONSTRAINT pk_administrador
        PRIMARY KEY (cedula)
);

CREATE TABLE LOGISTICA (
    cedula CHAR(8) NOT NULL,

    CONSTRAINT pk_logistica
        PRIMARY KEY (cedula)
);

CREATE TABLE CARGO (
    cedula CHAR(8) NOT NULL,
    cargo VARCHAR(50) NOT NULL,

    CONSTRAINT pk_cargo
        PRIMARY KEY (cedula, cargo)
);


ALTER TABLE ADMINISTRADOR
    ADD CONSTRAINT fk_administrador_usuario
    FOREIGN KEY (cedula)
    REFERENCES USUARIO (cedula);

ALTER TABLE LOGISTICA
    ADD CONSTRAINT fk_logistica_usuario
    FOREIGN KEY (cedula)
    REFERENCES USUARIO (cedula);

ALTER TABLE CARGO
    ADD CONSTRAINT fk_cargo_logistica
    FOREIGN KEY (cedula)
    REFERENCES LOGISTICA (cedula);