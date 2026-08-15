/*
    Espacio donde se deberán colocar todas las sentencias utilizadas para crear las tablas.
*/


 --tabla de usuarios
CREATE TABLE usuarios (
    documento INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
     claveHash VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('administrador','docente','direccion') NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    UNIQUE (email)
    unique (documento)
) ENGINE=InnoDB;

--Tabla de Laboratorios
CREATE TABLE laboratorios (
    id_laboratorio INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL, -- Ej: Lab A, Salón 105
    ubicacion VARCHAR(100),
    estado_salon ENUM('activo', 'mantenimiento', 'inactivo') DEFAULT 'activo'
) ENGINE=InnoDB;

--Tabla de Equipos
CREATE TABLE equipos (
    id_equipo INT AUTO_INCREMENT PRIMARY KEY,
    marca VARCHAR(50),
    modelo VARCHAR(50),
    nro_serie VARCHAR(100) UNIQUE,
    estado_hardware ENUM('funcional', 'en_reparacion','critico') DEFAULT 'funcional',
    id_laboratorio_actual INT,
    FOREIGN KEY (id_laboratorio_actual) REFERENCES laboratorios(id_laboratorio) ON DELETE SET NULL
) ENGINE=InnoDB;

--Tabla de Préstamos
CREATE TABLE reservas (
    id_reserva INT AUTO_INCREMENT PRIMARY KEY,
    id_docente INT NOT NULL,
    id_laboratorio INT NOT NULL,
    fecha_hora_inicio DATETIME NOT NULL,
    fecha_hora_fin DATETIME NOT NULL,
    observaciones TEXT,
    estado_reserva ENUM('confirmada', 'en_curso', 'finalizada', 'cancelada') DEFAULT 'confirmada',
    FOREIGN KEY (id_docente) REFERENCES usuarios(documento),
    FOREIGN KEY (id_laboratorio) REFERENCES laboratorios(id_laboratorio)
) ENGINE=InnoDB;

--registro de quién utilizó cada máquina
CREATE TABLE registro_uso_equipos (
    id_reserva INT NOT NULL,
    id_equipo INT NOT NULL,
    PRIMARY KEY (id_reserva, id_equipo),
    FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva) ON DELETE CASCADE,
    FOREIGN KEY (id_equipo) REFERENCES equipos(id_equipo) ON DELETE CASCADE
) ENGINE=InnoDB;

/*
CREATE TABLE USUARIO (
    cedula CHAR(8) NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    claveHash VARCHAR(255) NOT NULL,
    sesionActiva BOOLEAN NOT NULL DEFAULT FALSE,

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