# Persistencia

Documento de la capa de datos. Todas las consultas salen de los DAO de `app/modelo/` y se ejecutan con **consultas preparadas** (PDO, `ATTR_EMULATE_PREPARES = false`).

## Notación de álgebra relacional

| Símbolo | Operación |
|---|---|
| σ<sub>cond</sub>(R) | Selección |
| π<sub>a, b</sub>(R) | Proyección |
| R ⋈<sub>cond</sub> S | Join natural/theta |
| R ⟕<sub>cond</sub> S | Join externo izquierdo |
| R − S | Diferencia |
| ρ<sub>x</sub>(R) | Renombre (alias) |
| γ<sub>grupo; f(a)→n</sub>(R) | Agrupación y agregación |
| τ<sub>a ↓</sub>(R) | Ordenamiento (↓ descendente) |
| δ | Eliminación de duplicados |

Abreviaturas de relaciones (además HS = HISTORIAL_SOLICITUD, AC = ACTIVIDAD): U = USUARIO, A = ADMINISTRADOR, D = DOCENTE, DIR = DIRECCION, TEC = TECNICO, S = SALA, E = EQUIPO, H = HISTORIAL_EQUIPO, T = TICKET, TE = TICKET_EQUIPO, TC = TICKET_COMENTARIO, SO = SOLICITUD, SC = SOLICITUD_COMENTARIO, P = PRESTAMO, PE = PRESTAMO_EQUIPO.

---

## 1. Lista de consultas (DQL)

### Usuarios (`UsuarioDAO`)
- **Q1** `buscarUsuario`: datos completos de un usuario por cédula, con indicador de cada rol (login y sesión).
- **Q2** `listarUsuario`: un usuario por cédula con su rol principal.
- **Q3** `listarUsuarios`: todos los usuarios con roles y estado.

### Inventario (`InventarioDAO`)
- **Q4** `listarInventario`: todos los equipos ordenados por sala y número.
- **Q5** `listarSalas`: todas las salas ordenadas por tipo y número.
- **Q6** `listarSala`: una sala por id.
- **Q7** `listarEquiposPorSala`: equipos de una sala.
- **Q8** `listarEquipo`: un equipo por id.
- **Q9** `listarHistorialPorEquipo`: eventos de un equipo con el usuario que los hizo.
- **Q10** `existeSala`: ¿existe una sala con ese tipo y número?
- **Q11** `existeEquipo`: ¿existe un equipo con ese número en esa sala?

### Tickets (`TicketDAO`)
- **Q12** `listarTickets`: todos los tickets con sala, docente y asignado.
- **Q13** `listarTicketsPorDocente`: los tickets de un docente.
- **Q14** `listarTicket`: un ticket por id.
- **Q15** `listarEquiposPorTicket`: equipos afectados de un ticket.
- **Q16** `listarComentarios`: comentarios de un ticket con su autor.
- **Q17** `listarTecnicos` / `esTecnico`: técnicos disponibles para asignar / verificar que una cédula es técnico.
- **Q18** `existeSala` / `equipoPerteneceASala`: validaciones de integridad de la planilla.
- **Q19** `guardarEquipos` (conteo): incidencias de un equipo en las últimas semanas (calcula la prioridad).

### Solicitudes (`SolicitudDAO`)
- **Q20** `listarSolicitudes` / `listarSolicitudesPorDocente` / `listarSolicitud`: listado, listado propio y detalle.
- **Q21** `listarComentarios`: comentarios de una solicitud con su autor.
- **Q22** `listarSalas`, `existeSala`, `listarTecnicos`, `esTecnico`: apoyo a los formularios y validaciones.
- **Q23** `tienePrestamos`: ¿la solicitud tiene préstamos asociados? (impide eliminarla).

### Actividad (`ActividadDAO`)
- `INSERT INTO ACTIVIDAD` (`registrar`): una fila por cada operación POST/PUT/PATCH/DELETE completada con éxito, escrita al terminar la petición.

### Préstamos (`PrestamoDAO`)
- **Q24** `listarPrestamos` / `listarPrestamo` / `listarPrestamoActivoPorSolicitud`: listado, detalle y préstamo activo de una solicitud.
- **Q25** `listarEquiposPorPrestamo`: equipos entregados en un préstamo.
- **Q26** `listarIdsEquiposPrestados`: equipos con préstamo activo.
- **Q27** `listarEquiposDisponibles`: equipos que no están en ningún préstamo activo.
- **Q28** `existeEquipo`: validación de existencia.

### Reportes (`ReporteDAO`)
- **Q29** `obtenerResumenGeneral`: totales del sistema.
- **Q30** `contarTicketsPorEstado` / `contarTicketsPorPrioridad`: tickets por estado y por prioridad en un rango de fechas.
- **Q31** `listarEquiposConMasIncidencias`: top 10 de equipos con más incidencias.
- **Q32** `listarSalasConMasTickets`: salas ordenadas por cantidad de tickets.
- **Q33** `obtenerMetricasPrestamos`: total, activos, finalizados y duración promedio.
- **Q34** `listarEquiposMasPrestados`: top 10 de equipos más prestados.
- **Q35** `listarPersonalConRoles`: personal con sus roles (panel de dirección).

### Historial y actividad (`SolicitudDAO`, `ActividadDAO`)
- **Q36** `listarHistorial`: acciones realizadas sobre una solicitud con el usuario que las hizo (RF31).
- **Q37** `ActividadDAO::listar`: operaciones registradas en un rango de fechas, con filtro opcional por usuario (RNF4).

---

## 2. Consultas resueltas con álgebra relacional

**Q1 `buscarUsuario`** (cédula = c)

R₁ = σ<sub>U.cedula = c</sub>(U)
R₂ = R₁ ⟕<sub>cedula</sub> A ⟕<sub>cedula</sub> D ⟕<sub>cedula</sub> DIR ⟕<sub>cedula</sub> TEC
Resultado = π<sub>cedula, nombre, apellido, email, claveHash, activo, sesionActiva, inicioSesion, (A.cedula ≠ NULL), (D.cedula ≠ NULL), (DIR.cedula ≠ NULL), (TEC.cedula ≠ NULL)</sub>(R₂)

**Q2 `listarUsuario`**: igual que Q1 con la proyección de `rol` calculado (primer rol no nulo en el orden A, D, DIR, TEC) y sin `claveHash`.

**Q3 `listarUsuarios` / Q35 `listarPersonalConRoles`**: Q1 sin la selección: π<sub>…</sub>(U ⟕ A ⟕ D ⟕ DIR ⟕ TEC).

**Q4 `listarInventario`**: τ<sub>idSala, numero</sub>(π<sub>id, numero, idSala, estado, incidencia</sub>(E))

**Q5 `listarSalas`**: τ<sub>tipo, numero</sub>(π<sub>id, tipo, numero</sub>(S))

**Q6 `listarSala`**: π<sub>id, tipo, numero</sub>(σ<sub>id = idSala</sub>(S))

**Q7 `listarEquiposPorSala`**: τ<sub>numero</sub>(π<sub>id, numero, idSala, estado, incidencia</sub>(σ<sub>idSala = s</sub>(E)))

**Q8 `listarEquipo`**: π<sub>id, numero, idSala, estado, incidencia</sub>(σ<sub>id = e</sub>(E))

**Q9 `listarHistorialPorEquipo`**
R = σ<sub>H.equipo_id = e</sub>(H) ⟕<sub>H.cedula = U.cedula</sub> U
Resultado = τ<sub>fecha ↓</sub>(π<sub>H.id, accion, detalle, fecha, U.nombre, U.apellido</sub>(R))

**Q10 `existeSala`**: π<sub>id</sub>(σ<sub>tipo = t ∧ numero = n</sub>(S))

**Q11 `existeEquipo`**: π<sub>id</sub>(σ<sub>idSala = s ∧ numero = n</sub>(E))

**Q12 `listarTickets`**
R = T ⋈<sub>T.idSala = S.id</sub> S ⋈<sub>T.cedulaDocente = doc.cedula</sub> ρ<sub>doc</sub>(U) ⟕<sub>T.cedulaAsignado = asig.cedula</sub> ρ<sub>asig</sub>(U)
Resultado = τ<sub>prioridad ↑, fechaCreacion ↓</sub>(π<sub>T.*, S.tipo, S.numero, doc.nombre, doc.apellido, asig.nombre, asig.apellido</sub>(R))

**Q13 `listarTicketsPorDocente`**: igual que Q12 con σ<sub>T.cedulaDocente = c</sub> aplicada a T antes del join y orden por `fechaCreacion ↓`.

**Q14 `listarTicket`**: igual que Q12 con σ<sub>T.id = t</sub>.

**Q15 `listarEquiposPorTicket`**
π<sub>TE.id, equipo_id, utilizado, estudiante, incidencia, descripcion, E.numero</sub>(σ<sub>TE.ticket_id = t</sub>(TE) ⋈<sub>TE.equipo_id = E.id</sub> E)

**Q16 `listarComentarios` (ticket)**
τ<sub>fecha ↓</sub>(π<sub>TC.id, TC.cedula, comentario, fecha, U.nombre, U.apellido</sub>(σ<sub>TC.ticket_id = t</sub>(TC) ⟕<sub>TC.cedula = U.cedula</sub> U))

**Q17 `listarTecnicos`**: τ<sub>nombre</sub>(π<sub>U.cedula, nombre, apellido</sub>(U ⋈<sub>U.cedula = TEC.cedula</sub> TEC))
**`esTecnico`**: π<sub>cedula</sub>(σ<sub>cedula = c</sub>(TEC))

**Q18 `existeSala`**: π<sub>id</sub>(σ<sub>id = s</sub>(S)) · **`equipoPerteneceASala`**: π<sub>id</sub>(σ<sub>id = e ∧ idSala = s</sub>(E))

**Q19 conteo de incidencias recientes**
R = σ<sub>TE.equipo_id = e ∧ TE.incidencia ≠ NULL ∧ T.fechaCreacion ≥ ahora − k semanas</sub>(TE ⋈<sub>TE.ticket_id = T.id</sub> T)
Resultado = γ<sub>∅; COUNT(*) → total</sub>(R)

**Q20 `listarSolicitudes`** (y variantes `PorDocente`, `listarSolicitud`)
R = SO ⟕<sub>SO.idSala = S.id</sub> S ⋈<sub>SO.cedulaDocente = doc.cedula</sub> ρ<sub>doc</sub>(U) ⟕<sub>SO.cedulaAsignado = asig.cedula</sub> ρ<sub>asig</sub>(U)
Resultado = τ<sub>fechaCreacion ↓</sub>(π<sub>SO.*, S.tipo, S.numero, doc.nombre, doc.apellido, asig.nombre, asig.apellido</sub>(R)); la variante propia agrega σ<sub>cedulaDocente = c</sub> y el detalle σ<sub>SO.id = s</sub>.

**Q21 `listarComentarios` (solicitud)**
τ<sub>fecha ↓</sub>(π<sub>SC.id, comentario, fecha, SC.cedula, U.nombre, U.apellido</sub>(σ<sub>SC.solicitud_id = s</sub>(SC) ⟕<sub>SC.cedula = U.cedula</sub> U))

**Q22**: `listarSalas` = Q5; `existeSala` = Q18; `listarTecnicos` y `esTecnico` = Q17.

**Q23 `tienePrestamos`**: π<sub>id</sub>(σ<sub>solicitud_id = s</sub>(P)) limitado a una tupla.

**Q24 `listarPrestamos`** (y `listarPrestamo`, `listarPrestamoActivoPorSolicitud`)
R = P ⋈<sub>P.cedulaResponsable = r.cedula</sub> ρ<sub>r</sub>(U) ⋈<sub>P.solicitud_id = SO.id</sub> SO
Resultado = τ<sub>fechaHoraInicio ↓</sub>(π<sub>P.id, solicitanteNombre, solicitanteTipoDocumento, solicitanteDocumento, fechaHoraInicio, fechaHoraFin, P.estado, r.nombre, r.apellido, SO.id</sub>(R)); el detalle agrega σ<sub>P.id = p</sub> y el activo de una solicitud σ<sub>P.solicitud_id = s ∧ P.estado = 'Activo'</sub>.

**Q25 `listarEquiposPorPrestamo`**
π<sub>PE.id, noSeUso, E.numero, S.tipo, S.numero</sub>(σ<sub>PE.prestamo_id = p</sub>(PE) ⋈<sub>PE.equipo_id = E.id</sub> E ⋈<sub>E.idSala = S.id</sub> S)

**Q26 `listarIdsEquiposPrestados`**
δ(π<sub>PE.equipo_id</sub>(PE ⋈<sub>PE.prestamo_id = P.id</sub> σ<sub>estado = 'Activo'</sub>(P)))

**Q27 `listarEquiposDisponibles`**
Prestados = π<sub>equipo_id</sub>(PE ⋈<sub>prestamo_id = P.id</sub> σ<sub>estado = 'Activo'</sub>(P))
Disponibles = π<sub>E.id</sub>(E) − ρ<sub>id←equipo_id</sub>(Prestados)
Resultado = τ<sub>S.tipo, S.numero, E.numero</sub>(π<sub>S.id, S.tipo, S.numero, E.id, E.numero</sub>((E ⋈<sub>E.id ∈ Disponibles</sub> Disponibles) ⋈<sub>E.idSala = S.id</sub> S))

**Q28 `existeEquipo`**: π<sub>id</sub>(σ<sub>id = e</sub>(E))

**Q29 `obtenerResumenGeneral`**
γ<sub>∅; COUNT(*)</sub>(σ<sub>activo = TRUE</sub>(U)), γ<sub>∅; COUNT(*)</sub>(E), γ<sub>∅; COUNT(*)</sub>(σ<sub>estado ≠ 'Terminado'</sub>(T)), γ<sub>∅; COUNT(*)</sub>(σ<sub>estado ≠ 'Resuelta'</sub>(SO)), γ<sub>∅; COUNT(*)</sub>(σ<sub>estado = 'Activo'</sub>(P)): cinco agregaciones, cada una una tupla de un atributo.

**Q30 `contarTicketsPorEstado`**: γ<sub>estado; COUNT(*)→total</sub>(σ<sub>fecha ∈ [d, h]</sub>(T)) · **`PorPrioridad`**: γ<sub>prioridad; COUNT(*)→total</sub>(σ<sub>fecha ∈ [d, h]</sub>(T))

**Q31 `listarEquiposConMasIncidencias`**
R = σ<sub>TE.incidencia ≠ NULL ∧ T.fecha ∈ [d, h]</sub>(TE ⋈<sub>equipo_id = E.id</sub> E ⋈<sub>E.idSala = S.id</sub> S ⋈<sub>TE.ticket_id = T.id</sub> T)
Resultado = límite 10(τ<sub>totalIncidencias ↓</sub>(γ<sub>E.id, E.numero, S.tipo, S.numero; COUNT(*)→totalIncidencias</sub>(R)))

**Q32 `listarSalasConMasTickets`**
τ<sub>totalTickets ↓</sub>(γ<sub>S.id, S.tipo, S.numero; COUNT(*)→totalTickets</sub>(σ<sub>T.fecha ∈ [d, h]</sub>(T ⋈<sub>T.idSala = S.id</sub> S)))

**Q33 `obtenerMetricasPrestamos`**
γ<sub>∅; COUNT(*)→total, SUM(estado = 'Activo')→activos, SUM(estado = 'Finalizado')→finalizados, AVG(horas(fechaHoraFin − fechaHoraInicio))→promedioHoras</sub>(σ<sub>fecha(fechaHoraInicio) ∈ [d, h]</sub>(P))

**Q34 `listarEquiposMasPrestados`**
R = σ<sub>fecha(P.fechaHoraInicio) ∈ [d, h]</sub>(PE ⋈<sub>equipo_id = E.id</sub> E ⋈<sub>E.idSala = S.id</sub> S ⋈<sub>prestamo_id = P.id</sub> P)
Resultado = límite 10(τ<sub>totalPrestamos ↓</sub>(γ<sub>E.id, E.numero, S.tipo, S.numero; COUNT(*)→totalPrestamos</sub>(R)))

**Q36 `listarHistorial`**
τ<sub>fecha ↓, id ↓</sub>(π<sub>HS.id, accion, detalle, fecha, U.nombre, U.apellido</sub>(σ<sub>HS.solicitud_id = s</sub>(HS) ⟕<sub>HS.cedula = U.cedula</sub> U))   (HS = HISTORIAL_SOLICITUD)

**Q37 `ActividadDAO::listar`**
R = σ<sub>fecha(AC.fecha) ∈ [d, h] ∧ (AC.cedula = c, si se filtra)</sub>(AC) ⟕<sub>AC.cedula = U.cedula</sub> U   (AC = ACTIVIDAD)
Resultado = límite n(τ<sub>fecha ↓, id ↓</sub>(π<sub>AC.id, AC.cedula, U.nombre, U.apellido, metodo, ruta, recurso, detalle, estado, fecha</sub>(R)))

---

## 3. Lista de DML

### Usuarios y roles (`UsuarioDAO`, una transacción por operación)
- `INSERT INTO USUARIO` + `INSERT INTO ADMINISTRADOR | DOCENTE | DIRECCION | TECNICO`: alta de usuario con su rol (`registrarUsuario`).
- `UPDATE USUARIO SET nombre, apellido, email, claveHash`: modificación (`modificarUsuario`).
- `DELETE FROM` los cuatro roles + `INSERT` del rol nuevo: cambio de rol (`modificarUsuario`).
- `DELETE FROM ADMINISTRADOR | DOCENTE | DIRECCION | TECNICO` + `DELETE FROM USUARIO`: baja (`eliminarUsuario`).
- `UPDATE USUARIO SET activo`: habilitar o deshabilitar (`cambiarEstadoActivo`).
- `UPDATE USUARIO SET sesionActiva, inicioSesion`: control de sesión única (`establecerSesionActiva`).

### Inventario (`InventarioDAO`)
- `INSERT INTO SALA` (`registrarSala`) · `UPDATE SALA SET tipo, numero` (`modificarSala`) · `DELETE FROM SALA` (`eliminarSala`, borra sus equipos en cascada).
- `INSERT INTO EQUIPO` + `INSERT INTO HISTORIAL_EQUIPO ('Alta')` (`registrarEquipo`).
- `UPDATE EQUIPO SET estado, incidencia` + `INSERT INTO HISTORIAL_EQUIPO ('Modificación')` (`modificarEquipo`).
- `DELETE FROM EQUIPO` (`eliminarEquipo`).

### Tickets (`TicketDAO`)
- `INSERT INTO TICKET`, `INSERT INTO TICKET_EQUIPO` (uno por equipo), `INSERT INTO HISTORIAL_EQUIPO ('Uso en ticket')` por cada equipo utilizado y `UPDATE TICKET SET prioridad` (`registrarTicket`).
- `UPDATE TICKET SET idSala, fecha, …` y `DELETE FROM TICKET_EQUIPO` + `INSERT INTO TICKET_EQUIPO` (`modificarTicket`).
- `UPDATE TICKET SET estado` (`cambiarEstado`) · `UPDATE TICKET SET cedulaAsignado, estado` (`asignarResponsable`).
- `DELETE FROM TICKET` (`eliminarTicket`, elimina equipos y comentarios).
- `INSERT INTO TICKET_COMENTARIO` · `UPDATE TICKET_COMENTARIO SET comentario` · `DELETE FROM TICKET_COMENTARIO`.

### Solicitudes (`SolicitudDAO`)
- `INSERT INTO SOLICITUD` + `INSERT INTO HISTORIAL_SOLICITUD ('Creación')` (`registrarSolicitud`) · `UPDATE SOLICITUD SET tipo, idSala, programa, detalle, fecha, hora` (`modificarSolicitud`).
- `UPDATE SOLICITUD SET estado` + `INSERT INTO HISTORIAL_SOLICITUD ('Estado')` (`cambiarEstado`) · `UPDATE SOLICITUD SET cedulaAsignado, estado` + `INSERT INTO HISTORIAL_SOLICITUD ('Asignación')` (`asignarResponsable`).
- `DELETE FROM SOLICITUD` (`eliminarSolicitud`).
- `INSERT INTO SOLICITUD_COMENTARIO` · `UPDATE SOLICITUD_COMENTARIO SET comentario` · `DELETE FROM SOLICITUD_COMENTARIO`.

### Préstamos (`PrestamoDAO`)
- `INSERT INTO PRESTAMO` + `INSERT INTO PRESTAMO_EQUIPO` + `INSERT INTO HISTORIAL_EQUIPO ('Préstamo')` + `UPDATE SOLICITUD SET estado = 'En proceso'` + `INSERT INTO HISTORIAL_SOLICITUD` (`registrarPrestamo`).
- `UPDATE PRESTAMO SET fechaHoraFin, estado = 'Finalizado'` + `UPDATE PRESTAMO_EQUIPO SET noSeUso` + `UPDATE SOLICITUD SET estado = 'Resuelta'` + `INSERT INTO HISTORIAL_EQUIPO ('Devolución')` por equipo + `INSERT INTO HISTORIAL_SOLICITUD` (`registrarDevolucion`).
- `UPDATE PRESTAMO SET solicitanteNombre, solicitanteTipoDocumento, solicitanteDocumento` (`modificarPrestamo`).
- `DELETE FROM PRESTAMO` (`eliminarPrestamo`, elimina sus equipos).

---

## 4. Cobertura CRUD por tabla

| Tabla | Create | Read | Update | Delete | Endpoint |
|---|---|---|---|---|---|
| USUARIO | ✔ | ✔ | ✔ | ✔ | `usuarios` (POST, GET, PUT/PATCH, DELETE) |
| ADMINISTRADOR, DOCENTE, DIRECCION, TECNICO | ✔ | ✔ | ✔ | ✔ | `usuarios` (el rol se gestiona con el usuario) |
| SALA | ✔ | ✔ | ✔ | ✔ | `inventario?recurso=salas` |
| EQUIPO | ✔ | ✔ | ✔ | ✔ | `inventario?recurso=equipos` |
| HISTORIAL_EQUIPO | ✔ (automático) | ✔ | no aplica | no aplica | `inventario?idEquipo=`. Es una bitácora de auditoría: se escribe sola en cada alta o modificación de equipo, uso en un ticket, préstamo y devolución y no se edita ni borra a propósito. Se elimina en cascada con su equipo. |
| TICKET | ✔ | ✔ | ✔ | ✔ | `tickets` |
| TICKET_EQUIPO | ✔ | ✔ | ✔ | ✔ | `tickets` (se gestiona con el ticket) |
| TICKET_COMENTARIO | ✔ | ✔ | ✔ | ✔ | `tickets?recurso=comentarios` |
| SOLICITUD | ✔ | ✔ | ✔ | ✔ | `solicitudes` |
| SOLICITUD_COMENTARIO | ✔ | ✔ | ✔ | ✔ | `solicitudes?recurso=comentarios` |
| PRESTAMO | ✔ | ✔ | ✔ | ✔ | `prestamos` |
| PRESTAMO_EQUIPO | ✔ | ✔ | ✔ (`noSeUso`) | ✔ (en cascada) | `prestamos` (se gestiona con el préstamo) |
| HISTORIAL_SOLICITUD | ✔ (automático) | ✔ | no aplica | no aplica | `solicitudes?id=` (campo `historial`). Bitácora: se escribe sola; se elimina en cascada con su solicitud. |
| ACTIVIDAD | ✔ (automático) | ✔ | no aplica | no aplica | `actividad` (solo administrador). Bitácora de auditoría (RNF4). |

## 5. Seguridad de la persistencia

- Toda sentencia usa parámetros enlazados (`:nombre` o `?`) y `ATTR_EMULATE_PREPARES = false`: los datos del usuario nunca se concatenan al SQL.
- La única construcción dinámica es `IN (?, ?, …)` en `registrarDevolucion`: se generan solo los marcadores `?` según la cantidad de ids, y los valores van enlazados.
- Las contraseñas se guardan con `password_hash` y se verifican con `password_verify`.
- Las operaciones de varias sentencias (usuarios y roles, ticket con equipos, préstamo con equipos y devolución, y las operaciones de solicitudes que escriben historial) se ejecutan en transacción.
