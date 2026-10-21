/*
    Consultas de agregación para el módulo de reportes (ReporteDAO, RF39 a RF43, Q29 a Q35 de docs/persistencia.md).
    En PHP, ':desde' y ':hasta' se reemplazan por el rango de fechas elegido en el filtro (AAAA-MM-DD).
*/

/* Q29 obtenerResumenGeneral, RF42: resumen general para apoyo en la toma de decisiones */
SELECT
    (SELECT COUNT(*) FROM USUARIO WHERE activo = TRUE) AS usuariosActivos,
    (SELECT COUNT(*) FROM EQUIPO) AS totalEquipos,
    (SELECT COUNT(*) FROM TICKET WHERE estado != 'Terminado') AS ticketsPendientes,
    (SELECT COUNT(*) FROM SOLICITUD WHERE estado != 'Resuelta') AS solicitudesPendientes,
    (SELECT COUNT(*) FROM PRESTAMO WHERE estado = 'Activo') AS prestamosActivos;

/* Q30 contarTicketsPorEstado, RF39: cantidad de tickets por estado, en el rango de fechas filtrado */
SELECT estado, COUNT(*) AS total
FROM TICKET
WHERE fecha BETWEEN :desde AND :hasta
GROUP BY estado;

/* Q30 contarTicketsPorPrioridad, RF39: cantidad de tickets por prioridad, en el rango de fechas filtrado */
SELECT prioridad, COUNT(*) AS total
FROM TICKET
WHERE fecha BETWEEN :desde AND :hasta
GROUP BY prioridad;

/* Q31 listarEquiposConMasIncidencias, RF40: equipos con más incidencias reportadas (uso de equipos y recursos) */
SELECT
    e.numero AS numeroEquipo, s.tipo AS tipoSala, s.numero AS numeroSala, COUNT(*) AS totalIncidencias
FROM TICKET_EQUIPO AS te
JOIN EQUIPO AS e ON e.id = te.equipo_id
JOIN SALA AS s ON s.id = e.idSala
JOIN TICKET AS t ON t.id = te.ticket_id
WHERE te.incidencia IS NOT NULL AND t.fecha BETWEEN :desde AND :hasta
GROUP BY e.id, e.numero, s.tipo, s.numero
ORDER BY totalIncidencias DESC
LIMIT 10;

/* Q32 listarSalasConMasTickets, RF40: salas con más tickets (uso de las salas) */
SELECT s.tipo AS tipoSala, s.numero AS numeroSala, COUNT(*) AS totalTickets
FROM TICKET AS t
JOIN SALA AS s ON s.id = t.idSala
WHERE t.fecha BETWEEN :desde AND :hasta
GROUP BY s.id, s.tipo, s.numero
ORDER BY totalTickets DESC;

/* Q33 obtenerMetricasPrestamos, RF41: préstamos y devoluciones en el rango de fechas filtrado.
   AVG() ignora los NULL: un préstamo sin devolver (fechaHoraFin NULL) no entra en el promedio de horas, por eso no hace falta un CASE */
SELECT
    COUNT(*) AS totalPrestamos,
    SUM(estado = 'Activo') AS activos,
    SUM(estado = 'Finalizado') AS finalizados,
    AVG(TIMESTAMPDIFF(HOUR, fechaHoraInicio, fechaHoraFin)) AS promedioHoras
FROM PRESTAMO
WHERE DATE(fechaHoraInicio) BETWEEN :desde AND :hasta;

/* Q34 listarEquiposMasPrestados, RF41: equipos más prestados */
SELECT e.numero AS numeroEquipo, s.tipo AS tipoSala, s.numero AS numeroSala, COUNT(*) AS totalPrestamos
FROM PRESTAMO_EQUIPO AS pe
JOIN EQUIPO AS e ON e.id = pe.equipo_id
JOIN SALA AS s ON s.id = e.idSala
JOIN PRESTAMO AS p ON p.id = pe.prestamo_id
WHERE DATE(p.fechaHoraInicio) BETWEEN :desde AND :hasta
GROUP BY e.id, e.numero, s.tipo, s.numero
ORDER BY totalPrestamos DESC
LIMIT 10;

/* Q35 listarPersonalConRoles: todos los usuarios con una marca por cada rol que poseen, para el panel de dirección. No depende del rango de fechas */
SELECT
    u.cedula,
    u.nombre,
    u.apellido,
    IF(a.cedula IS NOT NULL, TRUE, FALSE) AS administrador,
    IF(d.cedula IS NOT NULL, TRUE, FALSE) AS docente,
    IF(dir.cedula IS NOT NULL, TRUE, FALSE) AS direccion,
    IF(t.cedula IS NOT NULL, TRUE, FALSE) AS tecnico
FROM USUARIO AS u
LEFT JOIN ADMINISTRADOR AS a ON a.cedula = u.cedula
LEFT JOIN DOCENTE AS d ON d.cedula = u.cedula
LEFT JOIN DIRECCION AS dir ON dir.cedula = u.cedula
LEFT JOIN TECNICO AS t ON t.cedula = u.cedula;
