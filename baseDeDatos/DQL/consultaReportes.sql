/*
    Consultas de agregación para el módulo de reportes (RF39 a RF43).
    En PHP, ':desde' y ':hasta' se reemplazan por el rango de fechas elegido en el filtro.
*/

/* RF42: resumen general para apoyo en la toma de decisiones */
SELECT
    (SELECT COUNT(*) FROM USUARIO WHERE activo = TRUE) AS usuariosActivos,
    (SELECT COUNT(*) FROM EQUIPO) AS totalEquipos,
    (SELECT COUNT(*) FROM TICKET WHERE estado != 'Terminado') AS ticketsPendientes,
    (SELECT COUNT(*) FROM SOLICITUD WHERE estado != 'Resuelta') AS solicitudesPendientes,
    (SELECT COUNT(*) FROM PRESTAMO WHERE estado = 'Activo') AS prestamosActivos;

/* RF39: cantidad de tickets por estado, en el rango de fechas filtrado */
SELECT estado, COUNT(*) AS total
FROM TICKET
WHERE fecha BETWEEN :desde AND :hasta
GROUP BY estado;

/* RF39: cantidad de tickets por prioridad, en el rango de fechas filtrado */
SELECT prioridad, COUNT(*) AS total
FROM TICKET
WHERE fecha BETWEEN :desde AND :hasta
GROUP BY prioridad;

/* RF40: equipos con más incidencias reportadas (uso de equipos y recursos) */
SELECT
    e.numero AS numeroEquipo, s.tipo AS tipoSala, s.numero AS numeroSala, COUNT(*) AS totalIncidencias
FROM TICKET_EQUIPO AS te
JOIN EQUIPO AS e ON e.id = te.equipo_id
JOIN SALA AS s ON s.id = e.idSala
JOIN TICKET AS t ON t.id = te.ticket_id
WHERE te.incidencia IS NOT NULL AND t.fecha BETWEEN :desde AND :hasta
GROUP BY e.id
ORDER BY totalIncidencias DESC
LIMIT 10;

/* RF40: salas con más tickets (uso de las salas) */
SELECT s.tipo AS tipoSala, s.numero AS numeroSala, COUNT(*) AS totalTickets
FROM TICKET AS t
JOIN SALA AS s ON s.id = t.idSala
WHERE t.fecha BETWEEN :desde AND :hasta
GROUP BY s.id
ORDER BY totalTickets DESC;

/* RF41: préstamos y devoluciones en el rango de fechas filtrado */
SELECT
    COUNT(*) AS totalPrestamos,
    SUM(estado = 'Activo') AS activos,
    SUM(estado = 'Finalizado') AS finalizados,
    AVG(CASE WHEN fechaHoraFin IS NOT NULL THEN TIMESTAMPDIFF(HOUR, fechaHoraInicio, fechaHoraFin) END) AS promedioHoras
FROM PRESTAMO
WHERE DATE(fechaHoraInicio) BETWEEN :desde AND :hasta;

/* RF41: equipos más prestados */
SELECT e.numero AS numeroEquipo, s.tipo AS tipoSala, s.numero AS numeroSala, COUNT(*) AS totalPrestamos
FROM PRESTAMO_EQUIPO AS pe
JOIN EQUIPO AS e ON e.id = pe.equipo_id
JOIN SALA AS s ON s.id = e.idSala
JOIN PRESTAMO AS p ON p.id = pe.prestamo_id
WHERE DATE(p.fechaHoraInicio) BETWEEN :desde AND :hasta
GROUP BY e.id
ORDER BY totalPrestamos DESC
LIMIT 10;
