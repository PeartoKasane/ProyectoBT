/*
    Espacio donde se deberán aclarar y definir todas las consultas utilizadas dentro del sistema

    Ya que los select tendrán valores que dependerán de lo que ingrese el usuario, indicar siempre cuál es el valor
    que variará.
*/

--Listar equipos de un laboratorio específico con su estado
SELECT e.id_equipo, e.marca, e.modelo, e.nro_serie, e.estado_hardware, l.nombre AS laboratorio
FROM equipos e
INNER JOIN laboratorios l ON e.id_laboratorio_actual = l.id_laboratorio
WHERE l.id_laboratorio = 1;

--Actualizar ubicación de un equipo a otro laboratorio
UPDATE equipos 
SET id_laboratorio_actual = 2 
WHERE id_equipo = 10;

--Crear una nueva reserva de laboratorio
INSERT INTO reservas (id_docente, id_laboratorio, fecha_hora_inicio, fecha_hora_fin, observaciones, estado_reserva)
VALUES (12345678, 1, '2026-08-20 08:00:00', '2026-08-20 10:00:00', 'Clase de Programación I', 'confirmada');

--Verificar la disponibilidad de un laboratorio en una fecha y hora
SELECT id_reserva, estado_reserva 
FROM reservas
WHERE id_laboratorio = 1
  AND estado_reserva IN ('confirmada', 'en_curso')
  AND ('2026-08-20 08:30:00' < fecha_hora_fin AND '2026-08-20 09:30:00' > fecha_hora_inicio);

  --Crear un ticket por falla en un equipo
  INSERT INTO Ticket (id_usuario_reporta, id_equipo_afectado, id_laboratorio_afectado, titulo, descripcion, gravedad, estado)
VALUES (12345678, 5, 1, 'Monitor no enciende', 'El equipo no da señal de video al presionar encendido.', 'media', 'enviado');

--Asignar técnico a un ticket y cambiar estado a 'en_proceso'
UPDATE Ticket 
SET id_tecnico_asignado = 87654321, estado = 'en_proceso'
WHERE id_ticket = 3;

--Laboratorios con mayor cantidad de incidencias registradas
SELECT l.nombre AS laboratorio, COUNT(t.id_ticket) AS total_tickets
FROM laboratorios l
LEFT JOIN Ticket t ON l.id_laboratorio = t.id_laboratorio_afectado
GROUP BY l.id_laboratorio, l.nombre
ORDER BY total_tickets DESC;



/* Selecciona un usuario en base a su cédula, en PHP, donde aparece '00000000' debe ser remplazado por :cedula
SELECT
    u.cedula,
    u.claveHash,
    u.sesionActiva,

    CASE
        WHEN a.cedula IS NOT NULL THEN 1
        ELSE 0
    END AS administrador,

    CASE
        WHEN l.cedula IS NOT NULL THEN 1
        ELSE 0
    END AS logistica

FROM USUARIO AS u

    LEFT JOIN ADMINISTRADOR AS a
    ON a.cedula = u.cedula

    LEFT JOIN LOGISTICA AS l
    ON l.cedula = u.cedula

WHERE u.cedula = '00000000';

/* Selecciona y muestra todos los datos de los usuarios en el sistema, dirigido al administrador */
SELECT
    u.cedula,
    u.nombre,
    u.apellido,

    CASE
        WHEN a.cedula IS NOT NULL THEN 1
        ELSE 0
    END AS administrador,

    CASE
        WHEN l.cedula IS NOT NULL THEN 1
        ELSE 0
    END AS logistica

FROM USUARIO AS u

LEFT JOIN ADMINISTRADOR AS a
    ON a.cedula = u.cedula

LEFT JOIN LOGISTICA AS l
    ON l.cedula = u.cedula