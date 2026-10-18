# Manual de usuario - SGRSI

Sistema de Gestión y Recursos de Soporte Informático (PeartoS.A). Cubre RNF30: acceso, navegación y uso de las funcionalidades principales.

> Las secciones 9 y 10 (Historial de una solicitud y Registro de actividad) describen funciones que se incorporan en paralelo a esta documentación. Verifique que su versión del sistema las incluya.

## 1. Roles

Cada cuenta tiene un único rol, asignado por un administrador: **Administrador**, **Dirección**, **Docente** o **Técnico**. El rol determina qué botones y pantallas aparecen. Qué puede hacer cada rol, operación por operación, está en [`permisos.md`](permisos.md).

## 2. Acceso al sistema

### 2.1 Iniciar sesión

1. Abra `login.html` (la dirección raíz del sistema redirige ahí).
2. Ingrese la **cédula** (exactamente 8 dígitos, sin puntos ni guiones, no puede empezar con 0) y la **contraseña** (mínimo 12 caracteres).
3. Pulse **Ingresar**. El sistema lo lleva a su pantalla de inicio según el rol:

| Rol | Pantalla de inicio |
|---|---|
| Administrador | `administrador.html` - Datos de empleados |
| Dirección | `direccion.html` - Resumen general de personal |
| Docente | `docente.html` - Mis datos |
| Técnico | `tecnico.html` - Mis datos |

### 2.2 Cuenta deshabilitada y sesión única

- Una cuenta **deshabilitada** por un administrador no puede ingresar aunque la contraseña sea correcta.
- Solo se permite **una sesión activa por usuario**. Si ya hay una abierta, el sistema rechaza el nuevo ingreso. La sesión se libera al cerrarla con **Cerrar sesión**; si el navegador se cerró sin hacerlo, se considera vencida sola a las **8 horas** de haber iniciado.

### 2.3 Cerrar sesión

Use el botón rojo **Cerrar sesión** de la barra superior (disponible en todas las pantallas). Libera la sesión y vuelve al login. Si la sesión se pierde (por ejemplo, el servidor responde "sesión no iniciada"), el sistema vuelve solo al login.

Cerrar la sesión de esta forma es importante: si solo cierra la pestaña, no podrá ingresar de nuevo hasta que pasen las 8 horas.

## 3. Navegación

Todas las pantallas tienen la misma barra superior oscura. Los botones que no corresponden a su rol no se muestran.

| Botón | Lo ven | Destino |
|---|---|---|
| Inicio | todos (cada rol ve el suyo) | su pantalla de inicio (tabla 2.1) |
| Inventario | todos | `inventario.html` |
| Tickets | todos | `tickets.html` |
| Solicitudes | todos | `solicitudes.html` |
| Préstamos | administrador, dirección, técnico | `prestamos.html` |
| Reportes | administrador, dirección, técnico | `reportes.html` |
| Actividad | solo administrador | `actividad.html` (ver sección 10) |
| Cerrar sesión | todos | vuelve al login |

El botón de la pantalla actual aparece resaltado (en blanco). Que un botón esté oculto es solo una ayuda visual: el servidor vuelve a verificar el rol en cada operación.

Convenciones de las pantallas:
- Los resultados de cada operación se muestran en una franja verde (éxito) o roja (error) arriba de la tabla o dentro de la ventana abierta.
- Las altas y modificaciones se hacen en ventanas emergentes (modales). Los errores de validación aparecen dentro de la ventana, que queda abierta para corregir el dato.
- Las eliminaciones piden confirmación.
- Los campos de texto rechazan lenguaje ofensivo (mensaje "contiene lenguaje inapropiado").

## 4. Panel de inicio por rol

### 4.1 Administrador: gestión de usuarios (`administrador.html`)

Tabla "Listado de empleados registrados" con cédula, nombre, apellido, rol y estado (Activo / Deshabilitado).

- **Alta de empleado**: abre el formulario con cédula (8 dígitos), nombre, apellido, correo (opcional), contraseña (mínimo 12 caracteres), confirmar contraseña y rol (Administrador, Docente, Dirección o Técnico). Pulse **Confirmar**.
- **Modificar** (por fila): carga los datos del empleado; la cédula no se puede cambiar. Se pueden cambiar nombre, apellido, correo y **rol**. El formulario exige ingresar de nuevo la contraseña (y su confirmación): al guardar, esa pasa a ser la contraseña de la cuenta.
- **Deshabilitar / Habilitar** (por fila): bloquea o restablece el acceso sin borrar la cuenta ni su historial. Un usuario deshabilitado no puede iniciar sesión.
- **Eliminar** (por fila, con confirmación): borra la cuenta. Si el usuario ya tiene tickets, solicitudes o préstamos asociados, el sistema no permite borrarlo; en ese caso use Deshabilitar.
- Su propia fila no muestra **Deshabilitar** ni **Eliminar**.

Notas:
- El cambio de rol de un usuario con sesión abierta se aplica cuando vuelve a iniciar sesión.
- Deshabilitar una cuenta impide nuevos ingresos; no cierra una sesión ya abierta.

### 4.2 Dirección: resumen de personal (`direccion.html`)

Tabla de solo lectura "Resumen institucional por rol": cédula, nombre, apellido y rol de cada empleado. Dirección no modifica usuarios. Además de esta pantalla puede consultar Inventario, Tickets, Solicitudes, Préstamos y Reportes (ver secciones siguientes).

### 4.3 Docente (`docente.html`) y Técnico (`tecnico.html`): "Mis datos"

Muestran la cédula, nombre, apellido y correo de la cuenta (o "Sin registrar" si no tiene correo). No hay acciones. El trabajo se hace desde Tickets, Solicitudes (y, para el técnico, Préstamos y Reportes).

## 5. Inventario (`inventario.html`)

Todos los roles consultan. Se muestra una tarjeta por sala (Laboratorio o Taller) con la tabla de sus equipos: número, estado (Funcionando, Incidencia, Fuera de servicio), descripción de la incidencia y acciones.

**Todos los roles:**
- **Ver historial** (por equipo): abre una ventana con los datos actuales del equipo y la tabla de eventos (fecha, acción, detalle, usuario).

**Solo administrador:**
- **Agregar sala**: tipo (Laboratorio o Taller) y número (entero positivo). No admite una sala igual a una existente.
- **Agregar equipo** (botón superior o dentro de una sala): sala, número de equipo y estado. Si el estado es **Incidencia** debe describir la incidencia (hasta 255 caracteres). Requiere haber creado antes una sala.
- **Modificar sala** (tipo y número) y **Eliminar sala** (elimina también todos sus equipos; confirmación).
- **Modificar** equipo: solo se cambian el **estado** y la incidencia; sala y número quedan fijos.
- **Eliminar** equipo (confirmación). Si el equipo o la sala tienen tickets o préstamos asociados no se puede eliminar.

## 6. Tickets de incidencias (`tickets.html`)

Un ticket es la planilla de uso de una sala, donde el docente informa qué equipos usó y qué incidencias hubo.

**Qué ve cada rol en la tabla:**
- Administrador, técnico y dirección: todos los tickets.
- Docente: solo los suyos.

Columnas: N°, fecha, sala, asignatura, grupo, prioridad (Urgente, Alta, Normal), estado, docente y asignado a. La prioridad se calcula sola según las incidencias declaradas: "No prende" es Urgente, "Teclado roto" es Alta y el resto, Normal. Si un equipo repite incidencias en pocas semanas la prioridad sube. Los tickets se ordenan de más urgente a menos urgente.

### 6.1 Registrar un ticket (solo docente)

1. Pulse **Nuevo ticket**.
2. Complete la planilla: sala, fecha, hora de entrada, hora de salida (posterior a la de entrada), asignatura, grupo y turno (Mañana, Vespertino, Nocturno).
3. Al elegir la sala aparecen sus equipos. Marque **Usado** en los que se utilizaron; para cada uno puede indicar estudiante, tipo de incidencia (No prende, Teclado roto, Mouse roto, Monitor roto, Otro problema) y una descripción opcional. Si marca un equipo usado con incidencia, el nombre del estudiante es obligatorio.
4. Pulse **Guardar ticket**. Nace en estado **Pendiente**.

### 6.2 Ver detalle

**Ver detalle** abre una ventana con los datos del ticket, la tabla "Equipos de la planilla" y los comentarios.

### 6.3 Modificar y eliminar

- **Modificar** (misma planilla): lo ve el administrador siempre, y el docente dueño solo mientras el ticket siga **Pendiente**. Los equipos enviados reemplazan a los anteriores.
- **Eliminar** (confirmación): solo administrador; borra también los equipos y comentarios del ticket.

### 6.4 Estado y asignación (administrador y técnico)

Dentro del detalle:
- **Cambiar estado**: Pendiente, Evaluando, En proceso o Terminado, y **Actualizar**.
- **Asignar responsable**: el administrador elige un técnico de la lista y pulsa **Asignar**. El técnico solo tiene el botón **Autoasignarme**.

### 6.5 Comentarios

En el detalle, escriba hasta 500 caracteres y pulse **Comentar**. Lo pueden hacer todos los roles (el docente solo en sus propios tickets). Cada comentario muestra autor y fecha. El **autor** y el **administrador** ven los botones **Editar** (el formulario pasa a "Guardar cambios", con **Cancelar edición**) y **Eliminar**.

## 7. Solicitudes (`solicitudes.html`)

Pedidos del docente al área técnica: **Preparación de laboratorio** o **Préstamo** de equipos.

**Qué ve cada rol:** administrador, técnico y dirección ven todas; el docente solo las suyas. Estados: Pendiente, En proceso, Resuelta.

### 7.1 Crear una solicitud (solo docente)

1. **Nueva solicitud**.
2. Elija el tipo.
   - *Preparación de laboratorio*: la sala y el programa que necesita son obligatorios.
   - *Préstamo*: la sala es opcional ("No aplica / a definir") y no lleva programa.
3. Complete descripción (hasta 255 caracteres), fecha y hora, y pulse **Enviar solicitud**.

### 7.2 Editar y eliminar

Botones **Editar** y **Eliminar** (con confirmación): los ve el administrador siempre y el docente dueño mientras la solicitud esté **Pendiente**. No se puede editar una solicitud con un préstamo activo (primero se registra la devolución), ni eliminar una que tenga préstamos registrados.

### 7.3 Detalle, estado, asignación y comentarios

- **Ver detalle**: tipo, sala, fecha, hora, programa, docente, asignado, estado y detalle.
- Administrador y técnico: **Cambiar estado** (Pendiente, En proceso, Resuelta) y **Asignar responsable** (el administrador elige un técnico; el técnico usa **Autoasignarme**).
- Comentarios: igual que en tickets (500 caracteres; editar y eliminar los propios, el administrador todos). El docente solo comenta sus solicitudes.
- En solicitudes de tipo **Préstamo**, administrador y técnico ven un recuadro con un acceso directo: **Registrar entrega de equipos** si aún no hay equipos entregados, o **Registrar devolución** si hay un préstamo activo. Ambos llevan a la pantalla Préstamos.

## 8. Préstamos (`prestamos.html`)

Visible para administrador, técnico y dirección. Dirección solo consulta; administrador y técnico gestionan.

Tabla "Préstamos y devoluciones registrados": N°, solicitante, documento, inicio, fin, estado (Activo / Finalizado), gestionado por y enlace a la solicitud (abre su detalle).

### 8.1 Registrar una entrega (administrador y técnico)

No hay botón de alta en esta pantalla: la entrega se inicia desde el detalle de una solicitud de tipo Préstamo (**Registrar entrega de equipos**).

1. Complete nombre completo de quien retira, tipo de documento (Cédula, Pasaporte, Otro) y número de documento.
2. Marque los equipos a entregar, agrupados por sala (solo aparecen equipos que no están en otro préstamo activo). Debe elegir al menos uno.
3. Pulse **Registrar entrega**. Una solicitud no puede tener dos préstamos activos a la vez.

### 8.2 Registrar una devolución (administrador y técnico)

En una fila Activa, **Registrar devolución**. La ventana lista los equipos entregados; marque **No se usó** solo en los que se devuelven sin haber sido utilizados, y pulse **Confirmar devolución**. El préstamo pasa a Finalizado con fecha y hora de fin.

### 8.3 Editar y eliminar

- **Editar** (administrador y técnico, solo préstamos Activos): modifica los datos de quien retira; no cambia los equipos entregados.
- **Eliminar** (solo administrador, con confirmación): borra el préstamo y sus equipos; si estaba activo, los equipos quedan libres.

## 9. Historial de una solicitud

En el **detalle de una solicitud** (al final del modal) aparece una sección **Historial** con los eventos de esa solicitud, del más reciente al más antiguo: creación, cambios de estado, asignaciones, modificaciones y préstamos (entrega y devolución). Cada evento indica fecha y hora, qué ocurrió y qué usuario lo realizó. Es solo de lectura y lo ven los mismos roles que pueden abrir el detalle (el docente, solo en sus solicitudes).

## 10. Registro de actividad (solo administrador)

La barra superior del administrador incluye el botón **Actividad**, que abre `actividad.html` ("Registro de actividad"). Muestra una tabla con **fecha, usuario, acción, sección y detalle** de las operaciones de escritura realizadas con éxito en el sistema (altas, modificaciones, bajas, cambios de estado, asignaciones, inicios y cierres de sesión), para dar trazabilidad. Por defecto muestra los últimos 7 días; se puede filtrar por **Desde**, **Hasta** y **Cédula** y botón **Consultar** (máximo 100 filas por consulta). Nunca registra contraseñas. Es de solo lectura. Si otro rol intenta abrir la pantalla, el servidor responde "Acceso denegado: rol incorrecto".

## 11. Reportes (`reportes.html`)

Visible para administrador, técnico y dirección; solo lectura.

- **Resumen general** (siempre al día, no depende del filtro): usuarios activos, equipos en inventario, tickets sin terminar, solicitudes sin resolver y préstamos activos.
- **Filtro de fechas**: campos **Desde** y **Hasta** y botón **Filtrar**. Si no se indican, se usan los últimos 30 días hasta hoy. Si "Desde" es posterior a "Hasta", el sistema los invierte. El filtro aplica a los reportes siguientes.
- **Tickets por estado** y **Tickets por prioridad** (Urgente, Alta, Normal): barras con el total de cada una.
- **Equipos con más incidencias** y **Salas más utilizadas** (según tickets).
- **Préstamos en el período**: total, activos, finalizados y duración promedio (en horas, "-" si no hay finalizados).
- **Equipos más prestados**.

## 12. Mensajes de error frecuentes

Los mensajes los emite el servidor y se muestran tal cual en la franja roja o dentro de la ventana abierta.

### Acceso y sesión

| Mensaje | Causa y qué hacer |
|---|---|
| Usuario o credenciales incorrectas | Cédula inexistente o contraseña errónea (el sistema no distingue cuál). Reintente. |
| Cédula y contraseña son obligatorias | Falta uno de los campos del login. |
| Acceso denegado: la cuenta se encuentra deshabilitada | Un administrador deshabilitó la cuenta. Solicite que la habiliten. |
| Acceso denegado: el usuario ya tiene una sesión activa | Hay otra sesión abierta o quedó sin cerrar. Ciérrela, o espere hasta 8 horas desde su inicio. |
| Acceso denegado: el usuario no tiene un rol asignado | Pida a un administrador que le asigne un rol. |
| Acceso denegado: sesión no iniciada | La sesión venció o no existe. Vuelva a ingresar. |
| Acceso denegado: rol incorrecto | Su rol no permite esa operación. |
| Solicitud rechazada | Falló la verificación de seguridad (token CSRF), normalmente por sesión vencida. Cierre sesión e ingrese de nuevo. |
| Error de conexión con la base de datos / Error interno del servidor | Falla del servidor. Avise al administrador del sistema. |

### Usuarios (administrador)

| Mensaje | Causa |
|---|---|
| Existen campos vacíos | Falta algún dato obligatorio. |
| Cédula incorrecta | No son 8 dígitos o empieza con 0. |
| Correo electrónico incorrecto | Formato de correo inválido. |
| La contraseña debe contener al menos 12 caracteres | Contraseña corta. |
| Las contraseñas ingresadas no coinciden | Contraseña y confirmación distintas. |
| Rol incorrecto | Rol fuera de los cuatro válidos. |
| No se pudo registrar el empleado / No se pudo modificar el empleado | Por ejemplo, la cédula ya existe. |
| No podés deshabilitar tu propia cuenta / No podés eliminar tu propia cuenta | Un administrador no puede hacerlo sobre sí mismo. |
| El usuario no existe | La cédula no corresponde a ninguna cuenta. |
| No se pudo eliminar el empleado | Tiene registros asociados; deshabilítelo en su lugar. |

### Inventario

| Mensaje | Causa |
|---|---|
| Ya existe una sala igual | Mismo tipo y número que otra sala. |
| Ya existe un equipo con ese número en esa sala | Número de equipo repetido en la sala. |
| Debe describir la incidencia para marcar el equipo con ese estado | Estado "Incidencia" sin descripción. |
| La incidencia admite hasta 255 caracteres | Descripción demasiado larga. |
| No se pudo eliminar el equipo, es posible que tenga tickets o préstamos asociados | Equipo con historial de tickets o préstamos (igual para salas). |
| Primero debe registrar una sala para poder agregar equipos. | No hay salas cargadas. |

### Tickets

| Mensaje | Causa |
|---|---|
| Existen campos vacíos o inválidos | Falta algún dato de la planilla. |
| La hora de salida debe ser posterior a la hora de entrada | Horario incoherente. |
| Debe indicar al menos un equipo / La sala elegida no tiene equipos registrados en el inventario | La sala no tiene equipos cargados. |
| Debe indicar el nombre del estudiante del equipo con incidencia | Equipo usado con incidencia y sin estudiante. |
| El formulario contiene lenguaje inapropiado / El comentario contiene lenguaje inapropiado | Filtro de palabras ofensivas. |
| El comentario admite hasta 500 caracteres | Comentario largo. |
| Falta completar el texto del comentario | Comentario vacío. |
| Acceso denegado: solo un docente puede registrar tickets | Solo el docente crea tickets. |
| Acceso denegado: el ticket pertenece a otro docente | El docente solo accede a sus tickets. |
| El ticket ya no se puede editar porque salió del estado Pendiente | El docente solo edita mientras está Pendiente. |
| Acceso denegado: solo un administrador puede eliminar tickets | Eliminar es solo del administrador. |
| Acceso denegado: solo el autor o un administrador puede operar el comentario | Editar o eliminar comentarios ajenos. |
| Solo se puede asignar el ticket a un usuario con rol Técnico | La asignación del administrador exige un técnico. |

### Solicitudes

| Mensaje | Causa |
|---|---|
| Debe seleccionar el laboratorio o sala a preparar / Debe indicar el programa que necesita | Faltan datos de una preparación de laboratorio. |
| El detalle admite hasta 255 caracteres y el programa hasta 100 | Textos largos. |
| Acceso denegado: solo un docente puede registrar solicitudes | Solo el docente crea solicitudes. |
| Acceso denegado: la solicitud pertenece a otro docente | El docente solo accede a las suyas. |
| El docente solo puede editar la solicitud mientras está Pendiente (igual para eliminar) | Ya fue tomada por el área técnica. |
| La solicitud tiene un préstamo activo: registre la devolución antes de editarla | Edición bloqueada por préstamo activo. |
| No se puede eliminar la solicitud: tiene préstamos registrados | Eliminación bloqueada. |
| Solo se puede asignar la solicitud a un usuario con rol Técnico | La asignación del administrador exige un técnico. |

### Préstamos

| Mensaje | Causa |
|---|---|
| Debe seleccionar al menos un equipo | Entrega sin equipos. |
| La solicitud indicada no es de tipo Préstamo | Solo las solicitudes de Préstamo admiten entrega. |
| Esta solicitud ya tiene un préstamo activo. Registre la devolución antes de crear uno nuevo | Un préstamo activo por solicitud. |
| El equipo N ya está en un préstamo activo | Otro usuario lo prestó antes. Actualice la lista. |
| El préstamo ya fue devuelto / El préstamo ya fue devuelto y no se puede editar | Operación sobre un préstamo Finalizado. |
| Tipo de documento inválido | Debe ser Cédula, Pasaporte u Otro. |
| El nombre admite hasta 100 caracteres y el documento hasta 20 | Textos largos. |
| Acceso denegado: solo el administrador puede eliminar préstamos | Eliminar es solo del administrador. |

### Reportes

| Mensaje | Causa |
|---|---|
| La fecha 'desde' es incorrecta, el formato es AAAA-MM-DD (igual para 'hasta') | Fecha inválida en el filtro. |
