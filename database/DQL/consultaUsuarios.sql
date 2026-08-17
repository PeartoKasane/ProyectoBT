/*
    Espacio donde se deberán aclarar y definir todas las consultas utilizadas dentro del sistema

    Ya que los select tendrán valores que dependerán de lo que ingrese el usuario, indicar siempre cuál es el valor
    que variará.
*/

/* Selecciona un usuario en base a su cédula, en PHP, donde aparece '00000000' debe ser remplazado por :cedula*/
SELECT
    u.cedula,
    u.claveHash,
    u.sesionActiva,

    CASE
        WHEN a.cedula IS NOT NULL THEN 1
        ELSE 0
    END AS administrador,

    CASE
        WHEN l.cedula IS NOT NULL THEN 1
        ELSE 0
    END AS logistica

FROM USUARIO AS u

    LEFT JOIN ADMINISTRADOR AS a
    ON a.cedula = u.cedula

    LEFT JOIN LOGISTICA AS l
    ON l.cedula = u.cedula

WHERE u.cedula = '00000000';

/* Selecciona y muestra todos los datos de los usuarios en el sistema, dirigido al administrador */
SELECT
    u.cedula,
    u.nombre,
    u.apellido,

    CASE
        WHEN a.cedula IS NOT NULL THEN 1
        ELSE 0
    END AS administrador,

    CASE
        WHEN l.cedula IS NOT NULL THEN 1
        ELSE 0
    END AS logistica

FROM USUARIO AS u

LEFT JOIN ADMINISTRADOR AS a
    ON a.cedula = u.cedula

LEFT JOIN LOGISTICA AS l
    ON l.cedula = u.cedula