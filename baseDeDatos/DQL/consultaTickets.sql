/*
    Consultas del módulo de tickets de incidencias.
*/

/* Lista los tickets junto con la sala, el docente y el técnico asignado (si tiene) */
SELECT
    t.id,
    t.fecha,
    t.horaEntrada,
    t.horaSalida,
    t.asignatura,
    t.grupo,
    t.turno,
    t.prioridad,
    t.estado,
    s.tipo AS tipoSala,
    s.numero AS numeroSala,
    doc.nombre AS docenteNombre,
    doc.apellido AS docenteApellido,
    asig.nombre AS asignadoNombre,
    asig.apellido AS asignadoApellido
FROM TICKET AS t
JOIN SALA AS s ON s.id = t.idSala
JOIN USUARIO AS doc ON doc.cedula = t.cedulaDocente
LEFT JOIN USUARIO AS asig ON asig.cedula = t.cedulaAsignado
ORDER BY t.prioridad ASC, t.fechaCreacion DESC;

/* Detalle de los equipos declarados en un ticket, en PHP ':idTicket' se reemplaza por el id real */
SELECT
    te.id,
    te.utilizado,
    te.estudiante,
    te.incidencia,
    te.descripcion,
    e.numero AS numeroEquipo
FROM TICKET_EQUIPO AS te
JOIN EQUIPO AS e ON e.id = te.equipo_id
WHERE te.ticket_id = :idTicket;

/* Comentarios de un ticket, del más reciente al más antiguo */
SELECT
    tc.id,
    tc.comentario,
    tc.fecha,
    u.nombre,
    u.apellido
FROM TICKET_COMENTARIO AS tc
LEFT JOIN USUARIO AS u ON u.cedula = tc.cedula
WHERE tc.ticket_id = :idTicket
ORDER BY tc.fecha DESC;
