# API del sistema

Todo el backend se expone por un único entrypoint, `public/index.php`, que recibe la ruta en la URL: `index.php?ruta=<recurso>`.
`app/rutas/api.php` decide qué controlador atiende cada ruta. La carpeta `public/` contiene únicamente archivos `.html` (en `paginas/`), `assets/` y ese entrypoint.

## Formato de la comunicación

- Siempre JSON, en ambos sentidos: el frontend envía `Accept: application/json` y, cuando hay cuerpo, `Content-Type: application/json`.
- Contrato único de toda respuesta, correcta o con error: `{"status": <código HTTP>, "message": "texto", "data": <información o null>}`.
- Los métodos `POST`, `PUT`, `PATCH` y `DELETE` requieren la cabecera `X-CSRF-Token` con el token recibido en el login.
- Todo `fetch` está en `assets/js/api.js` (`solicitarApi`). Cualquier falla (red, respuesta que no es JSON, error del servidor) se lanza como `ErrorApi`.
- El cierre de sesión está en un único script, `assets/js/sesion.js`, incluido por todas las páginas protegidas.

## Estructura

| Carpeta / archivo | Función |
|---|---|
| `public/index.php` | Entrypoint único. Inicia la sesión y carga las rutas. Captura cualquier `Throwable` y responde 500. |
| `app/rutas/api.php` | Endpoints: relaciona cada `ruta` con su controlador. |
| `app/controlador/*Controller.php` | Un controlador por recurso con `gestionar($metodo)`. |
| `app/modelo/*DAO.php` | Un DAO por entidad (usuarios, tickets, inventario, solicitudes, préstamos, reportes). |
| `app/modelo/ConectorPDO.php` | Singleton de la conexión PDO. |
| `app/nucleo/` | `Conexion`, `Sesion` y `Token` (CSRF), compartidos por los controladores. |
| `app/vista/RespuestaJson.php` | Arma todas las respuestas. |

## Rutas

| `?ruta=` | Métodos | Roles | Descripción |
|---|---|---|---|
| `login` | POST | público | `{cedula, clave}` → `csrfToken`, `cedula`, `rol` |
| `logout` | POST | sesión | Cierra la sesión (RF37). Con `?recurso=salida` lo envía solo el navegador (`pagehide`) al abandonar la página: libera la sesión en la base y, si no llega otra petición en 30 s, la sesión PHP queda vencida |
| `sesion` | GET | sesión | Datos del usuario logueado: `cedula, nombre, apellido, email, rol` |
| `usuarios` | GET, POST, PUT, PATCH, DELETE | administrador | Ver la sección de usuarios del documento anterior: `?cedula=` para consultar uno |
| `inventario` | GET, POST, PUT, DELETE | lectura: todos; escritura: administrador | `?idEquipo=`; `?recurso=salas\|equipos` en POST, PUT y DELETE |
| `tickets` | GET, POST, PUT, PATCH, DELETE | según rol | `?id=`; `?recurso=comentarios` para comentarios; PATCH = estado o asignación |
| `solicitudes` | GET, POST, PUT, PATCH, DELETE | los 4 roles (el docente solo ve las suyas) | `?id=` (el detalle incluye `comentarios` e `historial`), `?recurso=salas\|comentarios`; estado y asignación: administrador y técnico |
| `prestamos` | GET, POST, PUT, PATCH, DELETE | administrador, técnico, dirección (alta y devolución: administrador y técnico) | `?id=`, `?recurso=equiposDisponibles&idSolicitud=`; devolución: `{id, noSeUso:[...]}` |
| `actividad` | GET | administrador | Registro de actividad (RNF4): `?desde=&hasta=&cedula=&limite=` |
| `reportes` | GET | administrador, técnico, dirección | `?desde=&hasta=` (AAAA-MM-DD); `?recurso=personal` solo dirección |

Las consultas, el álgebra relacional, el DML y la cobertura CRUD por tabla están en `persistencia.md`.

## Códigos de error habituales

400 JSON inválido · 401 sesión no iniciada · 403 rol incorrecto o CSRF · 404 no existe · 405 método no permitido · 409 conflicto (sesión activa, préstamo ya activo, equipo ya prestado) · 422 validación · 500 error interno.

## Cómo probarla

1. Servir `public/` con un servidor PHP y abrir `paginas/login.html` con un usuario de `baseDeDatos/DML/inserccionUsuarios.sql`.
2. Cada rol es llevado a su panel: `administrador.html`, `direccion.html`, `docente.html` o `tecnico.html`.
