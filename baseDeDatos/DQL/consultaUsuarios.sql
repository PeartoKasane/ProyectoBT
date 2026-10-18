/*
    Espacio donde se deberán aclarar y definir todas las consultas utilizadas dentro del sistema

    Ya que los select tendrán valores que dependerán de lo que ingrese el usuario, indicar siempre cuál es el valor
    que variará.
*/

/* Selecciona un usuario en base a su cédula, en PHP, donde aparece '00000000' debe ser remplazado por :cedula */
SELECT
    u.cedula,
    u.nombre,
    u.apellido,
    u.email,
    u.claveHash,
    u.activo,
    u.sesionActiva,
    u.inicioSesion,

    CASE
        WHEN a.cedula IS NOT NULL THEN 1
        ELSE 0
    END AS administrador,

    CASE
        WHEN d.cedula IS NOT NULL THEN 1
        ELSE 0
    END AS docente,

    CASE
        WHEN dir.cedula IS NOT NULL THEN 1
        ELSE 0
    END AS direccion,

    CASE
        WHEN t.cedula IS NOT NULL THEN 1
        ELSE 0
    END AS tecnico

FROM USUARIO AS u

    LEFT JOIN ADMINISTRADOR AS a
    ON a.cedula = u.cedula

    LEFT JOIN DOCENTE AS d
    ON d.cedula = u.cedula

    LEFT JOIN DIRECCION AS dir
    ON dir.cedula = u.cedula

    LEFT JOIN TECNICO AS t
    ON t.cedula = u.cedula

WHERE u.cedula = '00000000';

/* Selecciona y muestra todos los datos de los usuarios en el sistema, dirigido al administrador */
SELECT
    u.cedula,
    u.nombre,
    u.apellido,
    u.email,
    u.activo,

    CASE
        WHEN a.cedula IS NOT NULL THEN 1
        ELSE 0
    END AS administrador,

    CASE
        WHEN d.cedula IS NOT NULL THEN 1
        ELSE 0
    END AS docente,

    CASE
        WHEN dir.cedula IS NOT NULL THEN 1
        ELSE 0
    END AS direccion,

    CASE
        WHEN t.cedula IS NOT NULL THEN 1
        ELSE 0
    END AS tecnico

FROM USUARIO AS u

LEFT JOIN ADMINISTRADOR AS a
    ON a.cedula = u.cedula

LEFT JOIN DOCENTE AS d
    ON d.cedula = u.cedula

LEFT JOIN DIRECCION AS dir
    ON dir.cedula = u.cedula

LEFT JOIN TECNICO AS t
    ON t.cedula = u.cedula;

/* Selecciona los datos públicos de un usuario con su rol como texto, dirigido a la API. Si tuviera más de un rol se prioriza Administrador, Docente, Dirección, Técnico.
   En PHP, donde aparece '00000000' debe ser remplazado por :cedula. Para listar a todos los usuarios se quita la cláusula WHERE */
SELECT
    u.cedula,
    u.nombre,
    u.apellido,
    u.email,
    u.activo,

    CASE
        WHEN a.cedula IS NOT NULL THEN 'Administrador'
        WHEN d.cedula IS NOT NULL THEN 'Docente'
        WHEN dir.cedula IS NOT NULL THEN 'Dirección'
        WHEN t.cedula IS NOT NULL THEN 'Técnico'
        ELSE ''
    END AS rol

FROM USUARIO AS u

LEFT JOIN ADMINISTRADOR AS a
    ON a.cedula = u.cedula

LEFT JOIN DOCENTE AS d
    ON d.cedula = u.cedula

LEFT JOIN DIRECCION AS dir
    ON dir.cedula = u.cedula

LEFT JOIN TECNICO AS t
    ON t.cedula = u.cedula

WHERE u.cedula = '00000000';

/* RF36: habilita (1) o deshabilita (0) una cuenta. En PHP, '1' debe ser remplazado por :activo y '00000000' por :cedula */
UPDATE USUARIO SET activo = 1 WHERE cedula = '00000000';

/* RF37: registra que el usuario inició sesión (sesionActiva = 1 y el momento actual). En PHP, '00000000' debe ser remplazado por :cedula */
UPDATE USUARIO SET sesionActiva = 1, inicioSesion = NOW() WHERE cedula = '00000000';

/* RF37: registra que el usuario cerró sesión (sesionActiva = 0 y se limpia el momento de inicio). En PHP, '00000000' debe ser remplazado por :cedula */
UPDATE USUARIO SET sesionActiva = 0, inicioSesion = NULL WHERE cedula = '00000000';
