/*
    Consultas del módulo de préstamos.
*/

/* Lista los préstamos junto con la solicitud de origen y quién los gestionó */
SELECT
    p.id, p.solicitanteNombre, p.solicitanteTipoDocumento, p.solicitanteDocumento,
    p.fechaHoraInicio, p.fechaHoraFin, p.estado,
    r.nombre AS responsableNombre, r.apellido AS responsableApellido,
    s.id AS idSolicitud
FROM PRESTAMO AS p
JOIN USUARIO AS r ON r.cedula = p.cedulaResponsable
JOIN SOLICITUD AS s ON s.id = p.solicitud_id
ORDER BY p.fechaHoraInicio DESC;

/* Equipos incluidos en un préstamo, en PHP ':idPrestamo' se reemplaza por el id real */
SELECT
    pe.id, pe.noSeUso, e.numero AS numeroEquipo, sala.tipo AS tipoSala, sala.numero AS numeroSala
FROM PRESTAMO_EQUIPO AS pe
JOIN EQUIPO AS e ON e.id = pe.equipo_id
JOIN SALA AS sala ON sala.id = e.idSala
WHERE pe.prestamo_id = :idPrestamo;

/* Préstamo activo (todavía sin devolver) asociado a una solicitud, si existe */
SELECT id FROM PRESTAMO WHERE solicitud_id = :idSolicitud AND estado = 'Activo';
