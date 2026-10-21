/*
    Consultas del módulo de inventario (InventarioDAO, Q4 a Q11 de docs/persistencia.md).
*/

/* Q4 listarInventario: lista todos los equipos ordenados por sala y número (el controlador los agrupa dentro de cada sala) */
SELECT id, numero, idSala, estado, incidencia FROM EQUIPO ORDER BY idSala, numero;

/* Q5 listarSalas: lista todas las salas */
SELECT id, tipo, numero FROM SALA ORDER BY tipo, numero;

/* Q6 listarSala: busca una sala determinada, en PHP ':idSala' se remplaza por el id real */
SELECT id, tipo, numero FROM SALA WHERE id = :idSala;

/* Q7 listarEquiposPorSala: lista los equipos de una sala determinada, en PHP ':idSala' se remplaza por el id real */
SELECT id, numero, idSala, estado, incidencia FROM EQUIPO WHERE idSala = :idSala ORDER BY numero;

/* Q8 listarEquipo: busca un equipo determinado, en PHP ':idEquipo' se remplaza por el id real */
SELECT id, numero, idSala, estado, incidencia FROM EQUIPO WHERE id = :idEquipo;

/* Q9 listarHistorialPorEquipo: lista el historial de un equipo determinado, del más reciente al más antiguo, en PHP ':idEquipo' se remplaza por el id real */
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

/* Q10 existeSala: verifica si ya existe una sala con ese tipo y número, en PHP ':tipo' y ':numero' se remplazan por los valores ingresados */
SELECT id FROM SALA WHERE tipo = :tipo AND numero = :numero;

/* Q10 existeOtraSala: igual que la anterior pero ignorando la sala que se está modificando, en PHP ':idSala' se remplaza por el id de esa sala */
SELECT id FROM SALA WHERE tipo = :tipo AND numero = :numero AND id <> :idSala;

/* Q11 existeEquipo: verifica si ya existe un equipo con ese número dentro de la sala, en PHP ':idSala' y ':numero' se remplazan por los valores ingresados */
SELECT id FROM EQUIPO WHERE idSala = :idSala AND numero = :numero;

/* Busca un evento del historial de un equipo (para corregirlo o eliminarlo, solo administrador), en PHP ':idEvento' se remplaza por el id real */
SELECT id, equipo_id, accion, detalle FROM HISTORIAL_EQUIPO WHERE id = :idEvento;
