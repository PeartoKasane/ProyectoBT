/*
    Consultas del módulo de solicitudes.
*/

/* Lista las solicitudes junto con el docente, la sala y el técnico asignado (si tiene) */
SELECT
    s.id, s.tipo, s.programa, s.detalle, s.fecha, s.hora, s.estado,
    sala.tipo AS tipoSala, sala.numero AS numeroSala,
    doc.nombre AS docenteNombre, doc.apellido AS docenteApellido,
    asig.nombre AS asignadoNombre, asig.apellido AS asignadoApellido
FROM SOLICITUD AS s
LEFT JOIN SALA AS sala ON sala.id = s.idSala
JOIN USUARIO AS doc ON doc.cedula = s.cedulaDocente
LEFT JOIN USUARIO AS asig ON asig.cedula = s.cedulaAsignado
ORDER BY s.fechaCreacion DESC;

/* Comentarios de una solicitud, en PHP ':idSolicitud' se reemplaza por el id real */
SELECT
    sc.id, sc.comentario, sc.fecha, u.nombre, u.apellido
FROM SOLICITUD_COMENTARIO AS sc
LEFT JOIN USUARIO AS u ON u.cedula = sc.cedula
WHERE sc.solicitud_id = :idSolicitud
ORDER BY sc.fecha DESC;

-- Historial de acciones de una solicitud (RF31)
SELECT h.id, h.accion, h.detalle, h.fecha, u.nombre, u.apellido
FROM HISTORIAL_SOLICITUD AS h
LEFT JOIN USUARIO AS u ON u.cedula = h.cedula
WHERE h.solicitud_id = :idSolicitud
ORDER BY h.fecha DESC, h.id DESC;
