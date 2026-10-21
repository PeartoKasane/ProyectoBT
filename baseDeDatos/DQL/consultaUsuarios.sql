/*
    Espacio donde se deberán aclarar y definir todas las consultas utilizadas dentro del sistema

    Ya que los select tendrán valores que dependerán de lo que ingrese el usuario, indicar siempre cuál es el valor
    que variará.

    Las consultas de este archivo son las mismas que ejecuta UsuarioDAO (Q1 a Q3 de docs/persistencia.md).
*/

/* Q1 buscarUsuario: selecciona un usuario en base a su cédula con una marca por cada rol. En PHP, donde aparece '00000000' debe ser remplazado por :cedula */
SELECT
    u.cedula,
    u.nombre,
    u.apellido,
    u.email,
    u.claveHash,
    u.activo,
    u.sesionActiva,
    u.inicioSesion,
    IF(a.cedula IS NOT NULL, TRUE, FALSE) AS administrador,
    IF(d.cedula IS NOT NULL, TRUE, FALSE) AS docente,
    IF(dir.cedula IS NOT NULL, TRUE, FALSE) AS direccion,
    IF(t.cedula IS NOT NULL, TRUE, FALSE) AS tecnico

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

/* Q2 listarUsuario: selecciona los datos públicos de un usuario con su rol como texto, dirigido a la API. Si tuviera más de un rol se prioriza Administrador, Docente, Dirección, Técnico.
   El CASE se mantiene porque tiene cuatro ramas (IF() solo resuelve dos). En PHP, donde aparece '00000000' debe ser remplazado por :cedula */
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

/* Q3 listarUsuarios: selecciona y muestra los datos públicos de todos los usuarios del sistema con su rol, dirigido al administrador. Es la consulta anterior sin la cláusula WHERE */
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
    ON t.cedula = u.cedula;

/* RF36: habilita (1) o deshabilita (0) una cuenta. En PHP, '1' debe ser remplazado por :activo y '00000000' por :cedula */
UPDATE USUARIO SET activo = 1 WHERE cedula = '00000000';

/* RF37: registra que el usuario inició sesión (sesionActiva = 1 y el momento actual). En PHP, '00000000' debe ser remplazado por :cedula y NOW() por la fecha que genera PHP en :inicioSesion */
UPDATE USUARIO SET sesionActiva = 1, inicioSesion = NOW() WHERE cedula = '00000000';

/* RF37: registra que el usuario cerró sesión (sesionActiva = 0 y se limpia el momento de inicio). En PHP, '00000000' debe ser remplazado por :cedula */
UPDATE USUARIO SET sesionActiva = 0, inicioSesion = NULL WHERE cedula = '00000000';
