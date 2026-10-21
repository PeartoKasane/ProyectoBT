/*
    Consultas del módulo de tickets de incidencias (TicketDAO, Q12 a Q19 de docs/persistencia.md).
*/

/* Q12 listarTickets: lista los tickets junto con la sala, el docente y el técnico asignado (si tiene), los más urgentes primero */
SELECT
    t.id, t.idSala, t.fecha, t.horaEntrada, t.horaSalida, t.asignatura, t.grupo, t.turno,
    t.prioridad, t.estado, t.cedulaDocente, t.cedulaAsignado,
    s.tipo AS tipoSala, s.numero AS numeroSala,
    doc.nombre AS docenteNombre, doc.apellido AS docenteApellido,
    asig.nombre AS asignadoNombre, asig.apellido AS asignadoApellido
FROM TICKET AS t
JOIN SALA AS s ON s.id = t.idSala
JOIN USUARIO AS doc ON doc.cedula = t.cedulaDocente
LEFT JOIN USUARIO AS asig ON asig.cedula = t.cedulaAsignado
ORDER BY t.prioridad ASC, t.fechaCreacion DESC;

/* Q13 listarTicketsPorDocente: la consulta anterior solo con los tickets de un docente, ordenados del más reciente al más antiguo.
   En PHP, se agrega "WHERE t.cedulaDocente = :cedulaDocente" antes del ORDER BY y este pasa a ser "ORDER BY t.fechaCreacion DESC" */

/* Q14 listarTicket: la consulta de Q12 para un ticket puntual. En PHP, se agrega "WHERE t.id = :idTicket" y se quita el ORDER BY */

/* Q15 listarEquiposPorTicket: detalle de los equipos declarados en un ticket, en PHP ':idTicket' se reemplaza por el id real */
SELECT
    te.id, te.equipo_id, te.utilizado, te.estudiante, te.incidencia, te.descripcion,
    e.numero AS numeroEquipo
FROM TICKET_EQUIPO AS te
JOIN EQUIPO AS e ON e.id = te.equipo_id
WHERE te.ticket_id = :idTicket;

/* Q16 listarComentarios: comentarios de un ticket con su autor, del más reciente al más antiguo, en PHP ':idTicket' se reemplaza por el id real */
SELECT
    tc.id, tc.cedula, tc.comentario, tc.fecha,
    u.nombre, u.apellido
FROM TICKET_COMENTARIO AS tc
LEFT JOIN USUARIO AS u ON u.cedula = tc.cedula
WHERE tc.ticket_id = :idTicket
ORDER BY tc.fecha DESC;

/* Q17 listarTecnicos: técnicos disponibles para asignar un ticket */
SELECT u.cedula, u.nombre, u.apellido
FROM USUARIO AS u
JOIN TECNICO AS t ON t.cedula = u.cedula
ORDER BY u.nombre;

/* Q17 esTecnico: verifica que una cédula pertenezca a un técnico, en PHP ':cedula' se reemplaza por la cédula a verificar */
SELECT cedula FROM TECNICO WHERE cedula = :cedula;

/* Q18 existeSala: verifica que la sala del ticket exista, en PHP ':idSala' se reemplaza por el id elegido en el formulario */
SELECT id FROM SALA WHERE id = :idSala;

/* Q18 equipoPerteneceASala: verifica que el equipo de la planilla pertenezca a la sala elegida, en PHP se reemplazan ':idEquipo' y ':idSala' */
SELECT id FROM EQUIPO WHERE id = :idEquipo AND idSala = :idSala;

/* Q19 guardarEquipos (conteo): incidencias de un equipo en las últimas semanas, sirve para calcular la prioridad (RF24).
   En PHP, ':idEquipo' se reemplaza por el id del equipo y ':semanas' por la cantidad de semanas de reincidencia */
SELECT COUNT(*) AS total
FROM TICKET_EQUIPO AS te
JOIN TICKET AS t ON t.id = te.ticket_id
WHERE te.equipo_id = :idEquipo
    AND te.incidencia IS NOT NULL
    AND t.fechaCreacion >= DATE_SUB(NOW(), INTERVAL :semanas WEEK);

/* Busca un comentario de ticket (para modificarlo o eliminarlo), en PHP ':idComentario' se reemplaza por el id real */
SELECT id, ticket_id, cedula, comentario FROM TICKET_COMENTARIO WHERE id = :idComentario;
