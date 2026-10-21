/*
    Consultas del registro de actividad (ActividadDAO, RNF4, Q37 de docs/persistencia.md).
*/

/* Q37 listar: últimas operaciones registradas, con el usuario que las realizó, dentro de un rango de fechas.
   En PHP, ':desde' y ':hasta' se reemplazan por el rango (AAAA-MM-DD) y ':limite' por la cantidad máxima de filas.
   El filtro por usuario es opcional: si se informa una cédula, PHP agrega "AND a.cedula = :cedula" antes del ORDER BY */
SELECT a.id, a.cedula, u.nombre, u.apellido, a.metodo, a.ruta, a.recurso, a.detalle, a.estado, a.fecha
FROM ACTIVIDAD AS a
LEFT JOIN USUARIO AS u ON u.cedula = a.cedula
WHERE DATE(a.fecha) BETWEEN :desde AND :hasta
ORDER BY a.fecha DESC, a.id DESC
LIMIT :limite;

/* Busca un registro de actividad (para corregirlo o eliminarlo, solo administrador), en PHP ':idActividad' se reemplaza por el id real */
SELECT id, cedula, metodo, ruta, recurso, detalle, estado, fecha FROM ACTIVIDAD WHERE id = :idActividad;
