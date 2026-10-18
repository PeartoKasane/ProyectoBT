/*
    Registro de actividad de los usuarios (RNF4).
*/

-- Registro de actividad de los usuarios para trazabilidad (RNF4)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
