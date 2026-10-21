# Permisos y control de acceso

Cubre RF35 (permisos según tipo de usuario), RF38 (los administradores modifican permisos) y su relación con RF34 y RF36. La matriz se construyó leyendo `app/controlador/*Controller.php`; si el código cambia, el código es la fuente de verdad.

## 1. Roles

Hay cuatro roles, cada uno en su propia tabla (`ADMINISTRADOR`, `DOCENTE`, `DIRECCION`, `TECNICO`). Al iniciar sesión se cargan en `$_SESSION` las claves `administrador`, `docente`, `direccion` y `tecnico` (booleanas). La interfaz permite asignar **un solo rol por usuario**. El rol principal que se informa al frontend sigue el orden: administrador, dirección, docente, técnico.

## 2. Matriz de permisos

Convenciones: **X** = permitido; **(propio)** = solo sobre sus propios registros; **(Pendiente)** = solo mientras el registro está en estado Pendiente; **-** = denegado (el servidor responde 403).

### 2.1 Usuarios (`?ruta=usuarios`)

| Operación | Admin | Dirección | Docente | Técnico |
|---|---|---|---|---|
| Listar / consultar (GET) | X | - | - | - |
| Alta (POST) | X | - | - | - |
| Modificar datos y rol (PUT) | X | - | - | - |
| Habilitar / deshabilitar (PATCH) | X (no a sí mismo) | - | - | - |
| Eliminar (DELETE) | X (no a sí mismo) | - | - | - |

### 2.2 Inventario (`?ruta=inventario`)

| Operación | Admin | Dirección | Docente | Técnico |
|---|---|---|---|---|
| Leer salas, equipos e historial de un equipo | X | X | X | X |
| Alta, modificación y baja de salas | X | - | - | - |
| Alta, modificación de estado y baja de equipos | X | - | - | - |
| Corregir o eliminar un evento del historial de un equipo (PUT / DELETE `recurso=historial`) | X | - | - | - |

### 2.3 Tickets (`?ruta=tickets`)

| Operación | Admin | Dirección | Docente | Técnico |
|---|---|---|---|---|
| Listar y ver detalle | todos | todos | (propio) | todos |
| Registrar ticket (POST) | - | - | X | - |
| Editar ticket (PUT) | X | - | (propio)(Pendiente) | - |
| Eliminar ticket (DELETE) | X | - | - | - |
| Cambiar estado (PATCH) | X | - | - | X |
| Asignar responsable (PATCH) | X, a cualquier técnico | - | - | X, solo a sí mismo |
| Comentar (POST `recurso=comentarios`) | X | X | (propio) | X |
| Editar / eliminar comentario | X (cualquiera) | autor | autor | autor |

"Autor" = quien escribió el comentario. Si el autor fue eliminado, solo el administrador puede operar el comentario.

### 2.4 Solicitudes (`?ruta=solicitudes`)

| Operación | Admin | Dirección | Docente | Técnico |
|---|---|---|---|---|
| Listar y ver detalle | todas | todas | (propio) | todas |
| Listar salas para el formulario (`recurso=salas`) | - | - | X | - |
| Registrar solicitud (POST) | - | - | X | - |
| Editar solicitud (PUT) | X | - | (propio)(Pendiente) | - |
| Eliminar solicitud (DELETE) | X | - | (propio)(Pendiente) | - |
| Cambiar estado (PATCH) | X | - | - | X |
| Asignar responsable (PATCH) | X, a cualquier técnico | - | - | X, solo a sí mismo |
| Comentar | X | X | (propio) | X |
| Editar / eliminar comentario | X (cualquiera) | autor | autor | autor |
| Ver préstamo activo asociado (en el detalle) | X | - | - | X |
| Corregir o eliminar un evento del historial (PUT / DELETE `recurso=historial`) | X | - | - | - |

Restricciones adicionales: no se edita una solicitud con préstamo activo (409) ni se elimina una con préstamos registrados (409).

### 2.5 Préstamos (`?ruta=prestamos`)

El acceso a la ruta exige administrador, técnico o dirección; el docente recibe 403 en cualquier operación.

| Operación | Admin | Dirección | Docente | Técnico |
|---|---|---|---|---|
| Listar y ver detalle (GET) | X | X | - | X |
| Equipos disponibles (`recurso=equiposDisponibles`) | X | - | - | X |
| Registrar entrega (POST) | X | - | - | X |
| Registrar devolución (PATCH) | X | - | - | X |
| Editar datos del solicitante (PUT, solo Activos) | X | - | - | X |
| Eliminar préstamo (DELETE) | X | - | - | - |

### 2.6 Reportes (`?ruta=reportes`)

| Operación | Admin | Dirección | Docente | Técnico |
|---|---|---|---|---|
| Reportes y estadísticas (GET) | X | X | - | X |
| Resumen de personal (`recurso=personal`) | - | X | - | - |

Reportes es solo lectura (cualquier método distinto de GET responde 405).

### 2.7 Sesión

| Ruta | Quién | Notas |
|---|---|---|
| `login` (POST) | público | Rechaza cuentas deshabilitadas, con sesión activa o sin rol. |
| `sesion` (GET) | cualquier sesión iniciada | Devuelve cédula, nombre, apellido, correo y rol. |
| `logout` (POST) | sesión iniciada | Exige token CSRF. |

### 2.8 Registro de actividad (`?ruta=actividad`)

Solo administrador (`Sesion::verificarRol("administrador")`); los demás roles reciben 401 sin sesión o 403 "Acceso denegado: rol incorrecto".

| Operación | Admin | Dirección | Docente | Técnico |
|---|---|---|---|---|
| Consultar (GET) | X | - | - | - |
| Corregir el detalle de un registro (PUT) | X | - | - | - |
| Eliminar un registro (DELETE) | X | - | - | - |

PUT y DELETE exigen token CSRF, igual que el resto de las operaciones que modifican datos.

## 3. Administración de usuarios: roles y habilitación (RF34, RF36, RF38)

Todo se hace en `administrador.html` (solo administrador, ver sección 4.1 del [manual de usuario](manualUsuario.md)).

**Cambiar el rol de un usuario**
1. En la tabla de empleados, **Modificar** en la fila del usuario.
2. Elegir el nuevo rol (Administrador, Docente, Dirección o Técnico).
3. Completar de nuevo contraseña y confirmación (el formulario las exige; la nueva pasa a ser la contraseña de la cuenta) y **Confirmar**.

Internamente (`UsuarioDAO::modificarUsuario`), en una transacción se actualizan los datos, se eliminan las filas del usuario en las cuatro tablas de rol y se inserta en la del rol elegido. Si algo falla se revierte todo.

**Habilitar / deshabilitar una cuenta (RF36)**: botón **Deshabilitar** / **Habilitar** en la fila. Cambia la columna `USUARIO.activo`; no borra datos. Un administrador no puede deshabilitarse ni eliminarse a sí mismo (422).

**Alta y baja**: **Alta de empleado** crea la cuenta con su rol (contraseña de al menos 12 caracteres, guardada con `password_hash`). **Eliminar** falla si el usuario tiene tickets, solicitudes o préstamos asociados; en ese caso corresponde deshabilitar.

**Cuándo se aplica cada cambio** (según el código actual):

| Cambio | Efecto |
|---|---|
| Cuenta deshabilitada | Bloquea el **siguiente** inicio de sesión (403). No cierra una sesión ya abierta, que sigue válida hasta cerrarse o vencer. |
| Cambio de rol | Los roles se guardan en `$_SESSION` al iniciar sesión, por lo que el nuevo rol rige desde el **próximo** inicio de sesión del usuario. |

## 4. Cómo se aplica el control

El control se hace siempre en el servidor, en cada petición; ocultar botones en el HTML es solo una ayuda visual.

1. **Sesión (RF33)**. `public/index.php` inicia la sesión PHP y deriva a `app/rutas/api.php`. Cada controlador comprueba que exista `$_SESSION["cedula"]`; si no, responde 401 "Acceso denegado: sesión no iniciada". El frontend, ante un 401, borra sus datos locales y vuelve al login.
2. **Rol**. Dos mecanismos:
   - `Sesion::verificarRol($rol)` (usado en `UsuarioController`, solo `"administrador"`): 401 si no hay sesión, 403 "Acceso denegado: rol incorrecto" si falta el rol.
   - Verificación propia en los demás controladores (`verificarAcceso()` para exigir al menos un rol o los roles de la ruta, y `tieneRol(...)` en cada operación para el control fino, incluida la propiedad de los registros del docente y del autor de un comentario).
3. **Token CSRF**. Al iniciar sesión `Token::generarTokenCSRF()` crea 32 bytes aleatorios (hexadecimal) en `$_SESSION["csrfToken"]` y los devuelve al navegador, que los guarda en `sessionStorage`. `solicitarApi()` (`api.js`) envía el token en la cabecera `X-CSRF-Token` en toda petición que no sea GET. Los controladores llaman a `Token::verificarCSRF()` en POST, PUT, PATCH y DELETE y comparan con `hash_equals`; si falta o no coincide, 403 "Solicitud rechazada". Las consultas GET no lo requieren.
4. **Cuenta y sesión única (RF36, RF37)**. En el login se rechazan cuentas deshabilitadas (403) y usuarios con sesión activa (409). La sesión activa se guarda en `USUARIO.sesionActiva` / `inicioSesion` y se considera vencida a las 8 horas. `Sesion::iniciar` regenera el identificador de sesión (evita fijación de sesión).
5. **Contraseñas**: se almacenan con `password_hash` y se verifican con `password_verify`; el login devuelve el mismo mensaje para cédula inexistente y contraseña errónea.
6. **Códigos de error**: 401 sin sesión, 403 rol incorrecto o CSRF, 404 no existe, 405 método no permitido, 409 conflicto, 422 validación.

Orden de las comprobaciones: sesión y rol de acceso a la ruta (401/403), token CSRF si el método modifica datos (403), rol propio de la operación (por ejemplo "solo un docente puede registrar tickets", 403), validación de datos (400/422) y reglas de negocio (404/409). En `InventarioController` el rol de administrador para escribir se verifica antes del CSRF.
