/*
    Espacio donde se deberán colocar todas las insercciones utilizadas en la primer ejecución del programa para el testeo.
*/
--Registrar un nuevo laboratorio y equipo
INSERT INTO laboratorio (nombre_laboratorio, ubicacion, activo)
VALUES ('Lab Redes 1', 'Edificio A - Piso 2', TRUE);

INSERT INTO equipo (id_equipo, tipo, estado, nombre_laboratorio)
VALUES (101, 'Switch Cisco 2960', 'Operativo', 'Lab Redes 1');

--Crear un ticket de incidencia sobre un equipo
INSERT INTO ticket (id_incidencia, descripcion, fecha, estado, gravedad, prioridad, id_equipo)
VALUES (5001, 'Fallo en puerto Gigabit 1/0/1', CURRENT_DATE, 'Pendiente', 'Alta', 'Alta', 101);

--Actualizar el estado del equipo al reportar una falla
UPDATE equipo
SET estado = 'En Mantenimiento'
WHERE id_equipo = 101;

--Marcar ticket como resuelto asignando el técnico que lo atiende
UPDATE ticket
SET estado = 'Resuelto', doc_tecnico = '12345678'
WHERE id_incidencia = 5001;

--Cancelar una solicitud de preparación de laboratorio
DELETE FROM solicitud_preparacion
WHERE id_solicitud = 302 AND estado = 'Cancelada';


/*
INSERT INTO USUARIO (cedula, nombre, apellido, claveHash) VALUES ('11111111', 'Leandro', 'López', '$2y$12$ki0bVkt8cnZuR4v6aJvhhelaeQc1/4fec2txUcuG1Ybr4cvnhg2sS');

INSERT INTO USUARIO (cedula, nombre, apellido, claveHash) VALUES ('22222222', 'Pepito', 'Alcachofas', '$2y$12$ki0bVkt8cnZuR4v6aJvhhelaeQc1/4fec2txUcuG1Ybr4cvnhg2sS');


    "clave1234567" ~ "$2y$12$ki0bVkt8cnZuR4v6aJvhhelaeQc1/4fec2txUcuG1Ybr4cvnhg2sS"

    El hash se recuperó con el siguiente script para crear el primer usuario en el sistema
    Nota: En el futuro se deberían cargar por un usuario administrador

    <?php
        $return = password_hash('clave1234567', PASSWORD_DEFAULT);
        echo($return);
    ?>
*/