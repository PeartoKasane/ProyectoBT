/*
    Espacio donde se deberán colocar todas las insercciones utilizadas en la primer ejecución del programa para el testeo.
*/

INSERT INTO USUARIO (cedula, nombre, apellido, email, claveHash) VALUES ('11111111', 'Admin', 'SGRSI', 'admin@pearto.com', '$2y$12$ki0bVkt8cnZuR4v6aJvhhelaeQc1/4fec2txUcuG1Ybr4cvnhg2sS');
INSERT INTO USUARIO (cedula, nombre, apellido, email, claveHash) VALUES ('22222222', 'Carlos', 'Docente', 'carlos@pearto.com', '$2y$12$ki0bVkt8cnZuR4v6aJvhhelaeQc1/4fec2txUcuG1Ybr4cvnhg2sS');
INSERT INTO USUARIO (cedula, nombre, apellido, email, claveHash) VALUES ('33333333', 'Ana', 'Director', 'ana@pearto.com', '$2y$12$ki0bVkt8cnZuR4v6aJvhhelaeQc1/4fec2txUcuG1Ybr4cvnhg2sS');
INSERT INTO USUARIO (cedula, nombre, apellido, email, claveHash) VALUES ('44444444', 'Marcos', 'Tecnico', 'marcos@pearto.com', '$2y$12$ki0bVkt8cnZuR4v6aJvhhelaeQc1/4fec2txUcuG1Ybr4cvnhg2sS');

INSERT INTO ADMINISTRADOR (cedula) VALUES ('11111111');
INSERT INTO DOCENTE (cedula) VALUES ('22222222');
INSERT INTO DIRECCION (cedula) VALUES ('33333333');
INSERT INTO TECNICO (cedula) VALUES ('44444444');

-- Usuarios de prueba adicionales para verificar las reglas del sistema (misma clave: "clave1234567")
-- 55555555: segundo docente. 66666666: cuenta deshabilitada (RF36) y sin correo. 77777777: segundo administrador.
INSERT INTO USUARIO (cedula, nombre, apellido, email, claveHash) VALUES ('55555555', 'Lucía', 'Docente', 'lucia@pearto.com', '$2y$12$ki0bVkt8cnZuR4v6aJvhhelaeQc1/4fec2txUcuG1Ybr4cvnhg2sS');
INSERT INTO USUARIO (cedula, nombre, apellido, email, claveHash, activo) VALUES ('66666666', 'Pedro', 'Suspendido', NULL, '$2y$12$ki0bVkt8cnZuR4v6aJvhhelaeQc1/4fec2txUcuG1Ybr4cvnhg2sS', FALSE);
INSERT INTO USUARIO (cedula, nombre, apellido, email, claveHash) VALUES ('77777777', 'Laura', 'Admin', 'laura@pearto.com', '$2y$12$ki0bVkt8cnZuR4v6aJvhhelaeQc1/4fec2txUcuG1Ybr4cvnhg2sS');

INSERT INTO DOCENTE (cedula) VALUES ('55555555');
INSERT INTO DOCENTE (cedula) VALUES ('66666666');
INSERT INTO ADMINISTRADOR (cedula) VALUES ('77777777');

/*
    "clave1234567" ~ "$2y$12$ki0bVkt8cnZuR4v6aJvhhelaeQc1/4fec2txUcuG1Ybr4cvnhg2sS"

    El hash se recuperó con el siguiente script para crear los primeros usuarios en el sistema
    Nota: en el futuro se deberían cargar por un usuario administrador desde procesarAltaUsuario.php

    <?php
        $return = password_hash('clave1234567', PASSWORD_DEFAULT);
        echo($return);
    ?>
*/
