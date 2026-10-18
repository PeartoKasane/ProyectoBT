/*
    Consultas del registro de actividad (RNF4).
*/

-- Últimas operaciones registradas, con el usuario que las realizó (filtros opcionales por cédula y rango de fechas)
SELECT a.id, a.cedula, u.nombre, u.apellido, a.metodo, a.ruta, a.recurso, a.detalle, a.estado, a.fecha
FROM ACTIVIDAD AS a
LEFT JOIN USUARIO AS u ON u.cedula = a.cedula
WHERE (:cedula IS NULL OR a.cedula = :cedula)
    AND DATE(a.fecha) BETWEEN :desde AND :hasta
ORDER BY a.fecha DESC, a.id DESC
LIMIT :limite;
