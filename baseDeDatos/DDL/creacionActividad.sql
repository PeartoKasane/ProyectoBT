/*
    Estructura de la tabla del registro de actividad de los usuarios (RNF4).
    Lo escribe Actividad::programarRegistro() en cada operación POST, PUT, PATCH o DELETE completada con éxito.
*/

CREATE TABLE ACTIVIDAD (
    id INT NOT NULL AUTO_INCREMENT,
    /* Se guarda NULL si el usuario que hizo la operación fue eliminado más adelante, para no perder el registro */
    cedula CHAR(8) DEFAULT NULL,
    metodo VARCHAR(10) NOT NULL,
    ruta VARCHAR(30) NOT NULL,
    recurso VARCHAR(30) DEFAULT NULL,
    detalle VARCHAR(255) DEFAULT NULL,
    estado SMALLINT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_actividad
        PRIMARY KEY (id)
);

ALTER TABLE ACTIVIDAD
    ADD CONSTRAINT fk_actividad_usuario
    FOREIGN KEY (cedula)
    REFERENCES USUARIO (cedula)
    ON DELETE SET NULL;
