/*
    Consultas del módulo de solicitudes (SolicitudDAO, Q20 a Q23 y Q36 de docs/persistencia.md).
*/

/* Q20 listarSolicitudes: lista las solicitudes junto con el docente, la sala y el técnico asignado (si tiene), de la más reciente a la más antigua */
SELECT
    s.id, s.tipo, s.programa, s.detalle, s.fecha, s.hora, s.estado,
    s.idSala, s.cedulaDocente, s.cedulaAsignado,
    sala.tipo AS tipoSala, sala.numero AS numeroSala,
    doc.nombre AS docenteNombre, doc.apellido AS docenteApellido,
    asig.nombre AS asignadoNombre, asig.apellido AS asignadoApellido
FROM SOLICITUD AS s
LEFT JOIN SALA AS sala ON sala.id = s.idSala
JOIN USUARIO AS doc ON doc.cedula = s.cedulaDocente
LEFT JOIN USUARIO AS asig ON asig.cedula = s.cedulaAsignado
ORDER BY s.fechaCreacion DESC;

/* Q20 listarSolicitudesPorDocente y listarSolicitud: la consulta anterior con "WHERE s.cedulaDocente = :cedulaDocente" (solo las de un docente)
   o con "WHERE s.id = :idSolicitud" (una solicitud puntual, sin ORDER BY). El WHERE va antes del ORDER BY */

/* Q21 listarComentarios: comentarios de una solicitud con su autor, en PHP ':idSolicitud' se reemplaza por el id real */
SELECT sc.id, sc.comentario, sc.fecha, sc.cedula, u.nombre, u.apellido
FROM SOLICITUD_COMENTARIO AS sc
LEFT JOIN USUARIO AS u ON u.cedula = sc.cedula
WHERE sc.solicitud_id = :idSolicitud
ORDER BY sc.fecha DESC;

/* Q22 listarSalas, existeSala, listarTecnicos y esTecnico: son las mismas consultas Q5, Q18 y Q17 de consultaInventario.sql y consultaTickets.sql */

/* Q23 tienePrestamos: verifica si la solicitud tiene préstamos asociados (impide eliminarla), en PHP ':idSolicitud' se reemplaza por el id real */
SELECT id FROM PRESTAMO WHERE solicitud_id = :idSolicitud LIMIT 1;

/* Busca un comentario de solicitud (para modificarlo o eliminarlo), en PHP ':idComentario' se reemplaza por el id real */
SELECT id, solicitud_id, cedula, comentario FROM SOLICITUD_COMENTARIO WHERE id = :idComentario;

/* Q36 listarHistorial (RF31): acciones realizadas sobre una solicitud, en PHP ':idSolicitud' se reemplaza por el id real */
SELECT h.id, h.accion, h.detalle, h.fecha, u.nombre, u.apellido
FROM HISTORIAL_SOLICITUD AS h
LEFT JOIN USUARIO AS u ON u.cedula = h.cedula
WHERE h.solicitud_id = :idSolicitud
ORDER BY h.fecha DESC, h.id DESC;

/* Busca un evento del historial de una solicitud (para corregirlo o eliminarlo, solo administrador), en PHP ':idEvento' se reemplaza por el id real */
SELECT id, solicitud_id, accion, detalle FROM HISTORIAL_SOLICITUD WHERE id = :idEvento;
