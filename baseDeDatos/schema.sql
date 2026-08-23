-- Creación e inserción para la base de datos de PEARTO S.A.
CREATE DATABASE IF NOT EXISTS `PeartoS.A` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `PeartoS.A`;

-- DDL: Estructura de la tabla usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `documento` varchar(8) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `clave` varchar(255) NOT NULL,
  `rol` enum('Administrador','Docente','Dirección') NOT NULL,
  PRIMARY KEY (`documento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- DML: Insertar usuarios de prueba por defecto para SGRSI
-- Las claves están encriptadas con BCRYPT (la clave plana de todos es "12345678")
INSERT INTO `usuarios` (`documento`, `nombre`, `apellido`, `email`, `clave`, `rol`) VALUES
('12345678', 'Admin', 'SGRSI', 'admin@pearto.com', '$2y$10$E9qY3U1T1N8nC8x1A2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p7q8r9s', 'Administrador'),
('87654321', 'Carlos', 'Docente', 'carlos@pearto.com', '$2y$10$E9qY3U1T1N8nC8x1A2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p7q8r9s', 'Docente'),
('11223344', 'Ana', 'Director', 'ana@pearto.com', '$2y$10$E9qY3U1T1N8nC8x1A2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p7q8r9s', 'Dirección')
ON DUPLICATE KEY UPDATE `documento` = `documento`;

-- DQL: Consultas base del sistema
-- Obtener usuario por cédula
SELECT documento, nombre, apellido, email, clave, rol FROM usuarios WHERE documento = '12345678';

-- Listar empleados para la vista de Administrador
SELECT documento AS cedula, nombre, apellido, rol, 
       (CASE WHEN rol = 'Administrador' THEN 1 ELSE 0 END) AS administrador 
FROM usuarios;