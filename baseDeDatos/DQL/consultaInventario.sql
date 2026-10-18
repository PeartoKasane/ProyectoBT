/*
    Consultas base del módulo de inventario.
*/

/* Lista todas las salas */
SELECT id, tipo, numero FROM SALA ORDER BY tipo, numero;

/* Lista los equipos de una sala determinada, en PHP ':idSala' se remplaza por el id real */
SELECT id, numero, estado, incidencia FROM EQUIPO WHERE idSala = :idSala ORDER BY numero;

/* Lista todos los equipos junto a los datos de su sala */
SELECT
    e.id,
    e.numero,
    e.estado,
    e.incidencia,
    s.id AS idSala,
    s.tipo AS tipoSala,
    s.numero AS numeroSala
FROM EQUIPO AS e
INNER JOIN SALA AS s ON s.id = e.idSala
ORDER BY s.tipo, s.numero, e.numero;

/* Lista el historial de un equipo determinado, del más reciente al más antiguo */
SELECT
    h.id,
    h.accion,
    h.detalle,
    h.fecha,
    u.nombre,
    u.apellido
FROM HISTORIAL_EQUIPO AS h
LEFT JOIN USUARIO AS u ON u.cedula = h.cedula
WHERE h.equipo_id = :idEquipo
ORDER BY h.fecha DESC;
