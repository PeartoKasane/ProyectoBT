/*
    Consultas del módulo de préstamos (PrestamoDAO, Q24 a Q28 de docs/persistencia.md).
*/

/* Q24 listarPrestamos: lista los préstamos junto con la solicitud de origen y quién los gestionó, del más reciente al más antiguo */
SELECT
    p.id, p.solicitanteNombre, p.solicitanteTipoDocumento, p.solicitanteDocumento,
    p.fechaHoraInicio, p.fechaHoraFin, p.estado,
    r.nombre AS responsableNombre, r.apellido AS responsableApellido,
    s.id AS idSolicitud
FROM PRESTAMO AS p
JOIN USUARIO AS r ON r.cedula = p.cedulaResponsable
JOIN SOLICITUD AS s ON s.id = p.solicitud_id
ORDER BY p.fechaHoraInicio DESC;

/* Q24 listarPrestamo y listarPrestamoActivoPorSolicitud: la consulta anterior con "WHERE p.id = :idPrestamo" (un préstamo puntual)
   o con "WHERE p.solicitud_id = :idSolicitud AND p.estado = 'Activo'" (el préstamo sin devolver de una solicitud). El WHERE reemplaza al ORDER BY */

/* Q25 listarEquiposPorPrestamo: equipos incluidos en un préstamo, en PHP ':idPrestamo' se reemplaza por el id real */
SELECT pe.id, pe.noSeUso, e.numero AS numeroEquipo, sala.tipo AS tipoSala, sala.numero AS numeroSala
FROM PRESTAMO_EQUIPO AS pe
JOIN EQUIPO AS e ON e.id = pe.equipo_id
JOIN SALA AS sala ON sala.id = e.idSala
WHERE pe.prestamo_id = :idPrestamo;

/* Q26 listarIdsEquiposPrestados: ids de los equipos que están en un préstamo activo en este momento */
SELECT pe.equipo_id
FROM PRESTAMO_EQUIPO AS pe
JOIN PRESTAMO AS p ON p.id = pe.prestamo_id
WHERE p.estado = 'Activo';

/* Q27 listarEquiposDisponibles: equipos que no están en ningún préstamo activo, ordenados por sala y número */
SELECT sala.id AS idSala, sala.tipo, sala.numero AS numeroSala, e.id AS idEquipo, e.numero AS numeroEquipo
FROM EQUIPO AS e
JOIN SALA AS sala ON sala.id = e.idSala
WHERE e.id NOT IN (
    SELECT pe.equipo_id
    FROM PRESTAMO_EQUIPO AS pe
    JOIN PRESTAMO AS p ON p.id = pe.prestamo_id
    WHERE p.estado = 'Activo'
)
ORDER BY sala.tipo, sala.numero, e.numero;

/* Q28 existeEquipo: valida que el equipo exista, en PHP ':idEquipo' se reemplaza por el id real */
SELECT id FROM EQUIPO WHERE id = :idEquipo;
