/**
 * CONSTANTES Y VARIABLES NECESARIAS
 */

//Modal de Bootstrap, se maneja desde JavaScript para poder abrirlo una vez que llegan los datos de la API
const elementoModal = document.getElementById("modalDetalleTicket");
const modalDetalleTicket = new bootstrap.Modal(elementoModal);
const modalTitulo = document.getElementById("modalDetalleTicketLabel");

//Constante para trabajar con la tabla de tickets
const cuerpoTablaTickets = document.getElementById("cuerpoTablaTickets");

//Constantes para completar el detalle dentro del modal
const detalleDatos = document.getElementById("detalleDatos");
const cuerpoTablaEquipos = document.getElementById("cuerpoTablaEquipos");
const listaComentarios = document.getElementById("listaComentarios");

//Constante para mostrar mensajes de resultado o de error
const mensajeAlerta = document.getElementById("mensajeAlerta");

//Mensajes dentro de los modales, donde el usuario puede verlos (el modal tapa la alerta de la página)
const mensajeDetalle = document.getElementById("mensajeDetalle");
const mensajeFormulario = document.getElementById("mensajeFormulario");

//Botón de alta de la página
const btnAltaTicket = document.getElementById("btnAltaTicket");

//Controles del detalle para cambiar el estado, asignar y comentar
const selectEstado = document.getElementById("selectEstado");
const btnCambiarEstado = document.getElementById("btnCambiarEstado");
const selectTecnico = document.getElementById("selectTecnico");
const btnAsignarTecnico = document.getElementById("btnAsignarTecnico");
const btnAutoasignar = document.getElementById("btnAutoasignar");
const formularioComentario = document.getElementById("formularioComentario");
const entradaComentario = document.getElementById("entradaComentario");
const btnGuardarComentario = document.getElementById("btnGuardarComentario");
const btnCancelarComentario = document.getElementById("btnCancelarComentario");

//Modal de alta y modificación de tickets
const elementoModalGestion = document.getElementById("modalGestionarTicket");
const modalGestionarTicket = new bootstrap.Modal(elementoModalGestion);
const modalGestionTitulo = document.getElementById("modalGestionarTicketLabel");
const formularioGestionarTicket = document.getElementById("formularioGestionarTicket");
const btnGuardarTicket = document.getElementById("btnGuardarTicket");
const cuerpoTablaFormularioEquipos = document.getElementById("cuerpoTablaFormularioEquipos");

//Campos del formulario
const entradaSala = document.getElementById("idSala");
const entradaFecha = document.getElementById("fecha");
const entradaHoraEntrada = document.getElementById("horaEntrada");
const entradaHoraSalida = document.getElementById("horaSalida");
const entradaAsignatura = document.getElementById("asignatura");
const entradaGrupo = document.getElementById("grupo");
const entradaTurno = document.getElementById("turno");

//Rol y cédula de quien tiene la sesión iniciada, solo sirven para mostrar u ocultar controles (el servidor verifica igual)
const rolPropio = sessionStorage.getItem(CLAVE_ROL);
const cedulaPropia = sessionStorage.getItem(CLAVE_CEDULA);

//Incidencias que acepta la API
const INCIDENCIAS = ["No prende", "Teclado roto", "Mouse roto", "Monitor roto", "Otro problema"];

//Auxiliares de estado: ticket que se está modificando (null = alta), ticket abierto en el detalle,
//comentario que se está editando (null = comentario nuevo), salas del inventario y filas de equipos del formulario
let ticketEnEdicion = null;
let idTicketAbierto = null;
let idComentarioEnEdicion = null;
let salasInventario = [];
let filasEquipos = [];

//Clases de Bootstrap de las insignias de prioridad (0 = más urgente) y de estado
const CLASES_PRIORIDAD = {
    0: "bg-danger",
    1: "bg-warning text-dark",
    2: "bg-secondary"
};

const CLASES_ESTADO = {
    "Pendiente": "bg-secondary",
    "Evaluando": "bg-info text-dark",
    "En proceso": "bg-primary",
    "Terminado": "bg-success"
};


/**
 * MENSAJES AL USUARIO
 */

//Muestra un mensaje dentro de la página. El tipo es el sufijo de la clase de Bootstrap: "success" o "danger"
function mostrarMensaje(mensaje, tipo) {
    mensajeAlerta.textContent = mensaje;
    mensajeAlerta.className = "alert alert-" + tipo + " shadow-sm";
}

//Oculta el mensaje anterior
function ocultarMensaje() {
    mensajeAlerta.className = "alert d-none";
}

//Muestra un mensaje dentro del modal de detalle
function mostrarMensajeDetalle(mensaje, tipo) {
    mensajeDetalle.textContent = mensaje;
    mensajeDetalle.className = "alert alert-" + tipo;
}

//Oculta el mensaje del modal de detalle
function ocultarMensajeDetalle() {
    mensajeDetalle.className = "alert d-none";
}

//Muestra un error dentro del modal de alta y modificación
function mostrarErrorFormulario(mensaje) {
    mensajeFormulario.textContent = mensaje;
    mensajeFormulario.className = "alert alert-danger";
}


/**
 * PERMISOS EN LA INTERFAZ (ayuda visual, el servidor los verifica en cada petición)
 */

//El administrador siempre; el docente dueño mientras el ticket siga en Pendiente
function puedeModificarTicket(ticket) {
    return rolPropio === "administrador"
        || (rolPropio === "docente" && ticket.cedulaDocente === cedulaPropia && ticket.estado === "Pendiente");
}

//Solo el administrador elimina tickets
function puedeEliminarTicket() {
    return rolPropio === "administrador";
}

//Un comentario lo operan su autor o el administrador (si el autor fue eliminado la cédula llega en null)
function puedeOperarComentario(comentario) {
    return rolPropio === "administrador"
        || (comentario.cedula !== null && comentario.cedula === cedulaPropia);
}


/**
 * FORMATO DE DATOS
 */

//Convierte una fecha AAAA-MM-DD en DD/MM/AAAA. Si no tiene ese formato la deja como llegó
function formatearFecha(fecha) {
    const partes = fecha.split("-");

    if (partes.length !== 3) {
        return fecha;
    }

    return `${partes[2]}/${partes[1]}/${partes[0]}`;
}

//Convierte una hora HH:MM:SS en HH:MM
function formatearHora(hora) {
    return hora.slice(0, 5);
}

//Convierte una fecha con hora AAAA-MM-DD HH:MM:SS en DD/MM/AAAA HH:MM
function formatearFechaHora(fechaHora) {
    const [fecha, hora] = fechaHora.split(" ");

    if (hora === undefined) {
        return formatearFecha(fecha);
    }

    return `${formatearFecha(fecha)} ${formatearHora(hora)}`;
}


/**
 * OPERACIONES CON LA API
 */

/**
 * GET - Obtiene los tickets. El administrador, el técnico y dirección reciben todos; el docente solo los suyos.
 */
async function obtenerTickets() {
    return await solicitarApi("tickets");
}

/**
 * GET - Obtiene un ticket específico con sus equipos y comentarios mediante su id.
 */
async function obtenerTicket(id) {
    return await solicitarApi("tickets", { parametros: { id: id } });
}

/**
 * GET - Obtiene las salas con sus equipos desde el inventario.
 */
async function obtenerSalas() {
    return await solicitarApi("inventario");
}

/**
 * POST - Registra un ticket (solo docente).
 */
async function altaTicket(ticket) {
    return await solicitarApi("tickets", { metodo: "POST", cuerpo: ticket });
}

/**
 * PUT - Modifica los datos de un ticket. Los equipos enviados reemplazan a los anteriores.
 */
async function modificarTicket(ticket) {
    return await solicitarApi("tickets", { metodo: "PUT", cuerpo: ticket });
}

/**
 * DELETE - Elimina un ticket (solo administrador).
 */
async function eliminarTicket(id) {
    if (!window.confirm(`¿Está seguro de eliminar el ticket N° ${id}? Se eliminarán también sus equipos y comentarios.`)) {
        return;
    }

    try {
        const resultado = await solicitarApi("tickets", { metodo: "DELETE", cuerpo: { id: id } });

        //Una vez eliminado, vuelve a consultar los datos al servidor
        await actualizarTabla();
        mostrarMensaje(resultado.mensaje, "success");
    } catch (error) {
        mostrarMensaje(error.message, "danger");
    }
}

/**
 * PATCH - Cambia el estado de un ticket (administrador y técnico).
 */
async function cambiarEstadoTicket(id, estado) {
    return await solicitarApi("tickets", { metodo: "PATCH", cuerpo: { id: id, estado: estado } });
}

/**
 * PATCH - Asigna un responsable. El técnico siempre queda autoasignado, sin importar la cédula enviada.
 */
async function asignarTicket(id, cedulaAsignado) {
    return await solicitarApi("tickets", { metodo: "PATCH", cuerpo: { id: id, cedulaAsignado: cedulaAsignado } });
}

/**
 * POST - Agrega un comentario a un ticket.
 */
async function agregarComentario(idTicket, comentario) {
    return await solicitarApi("tickets", { metodo: "POST", parametros: { recurso: "comentarios" }, cuerpo: { id: idTicket, comentario: comentario } });
}

/**
 * PUT - Modifica el texto de un comentario (su autor o el administrador).
 */
async function modificarComentario(idComentario, comentario) {
    return await solicitarApi("tickets", { metodo: "PUT", parametros: { recurso: "comentarios" }, cuerpo: { id: idComentario, comentario: comentario } });
}

/**
 * DELETE - Elimina un comentario (su autor o el administrador).
 */
async function eliminarComentarioApi(idComentario) {
    return await solicitarApi("tickets", { metodo: "DELETE", parametros: { recurso: "comentarios" }, cuerpo: { id: idComentario } });
}


/**
 * CREACIÓN DE ELEMENTOS
 */

//Crea una celda de la tabla. El contenido puede ser un texto o un elemento (por ejemplo una insignia)
//append() agrega los textos como texto y no como HTML, así un dato con etiquetas se muestra tal cual y no se ejecuta
function crearCelda(contenido) {
    const celda = document.createElement("td");
    celda.append(contenido);

    return celda;
}

//Crea una insignia de Bootstrap con las clases indicadas
function crearInsignia(texto, clases) {
    const insignia = document.createElement("span");
    insignia.className = "badge " + clases;
    insignia.textContent = texto;

    return insignia;
}

//Crea un botón de acción de la fila con las clases de Bootstrap indicadas
function crearBotonOperacion(texto, clases, accion) {
    const boton = document.createElement("button");
    boton.type = "button";
    boton.textContent = texto;
    boton.className = "btn btn-sm fw-semibold " + clases;
    boton.addEventListener("click", accion);

    return boton;
}

//Agrega a una tabla una fila con un texto que ocupa todas las columnas, para cuando no hay datos que mostrar
function agregarFilaVacia(cuerpoTabla, cantidadColumnas, texto) {
    const fila = document.createElement("tr");

    const celda = crearCelda(texto);
    celda.colSpan = cantidadColumnas;
    celda.className = "text-muted";

    fila.appendChild(celda);
    cuerpoTabla.appendChild(fila);
}


/**
 * GESTIÓN DE FILAS DE LA TABLA
 */

//Crea una fila de la tabla a partir de un ticket
function agregarFilaTicket(ticket) {
    const fila = document.createElement("tr");

    const insigniaPrioridad = crearInsignia(ticket.prioridadTexto, CLASES_PRIORIDAD[ticket.prioridad] ?? "bg-secondary");
    const insigniaEstado = crearInsignia(ticket.estado, CLASES_ESTADO[ticket.estado] ?? "bg-secondary");

    //Un ticket que ningún técnico tomó todavía llega con asignado en null
    const textoAsignado = ticket.asignado === null ? "Sin asignar" : ticket.asignado;

    const cajaOperaciones = document.createElement("div");
    cajaOperaciones.className = "d-flex justify-content-center gap-2";
    cajaOperaciones.appendChild(
        crearBotonOperacion("Ver detalle", "btn-outline-primary", () => abrirDetalleTicket(ticket.id))
    );

    if (puedeModificarTicket(ticket)) {
        cajaOperaciones.appendChild(
            crearBotonOperacion("Modificar", "btn-outline-secondary", () => abrirModificarTicket(ticket.id))
        );
    }

    if (puedeEliminarTicket()) {
        cajaOperaciones.appendChild(
            crearBotonOperacion("Eliminar", "btn-outline-danger", () => eliminarTicket(ticket.id))
        );
    }

    fila.appendChild(crearCelda(ticket.id));
    fila.appendChild(crearCelda(formatearFecha(ticket.fecha)));
    fila.appendChild(crearCelda(`${ticket.tipoSala} ${ticket.numeroSala}`));
    fila.appendChild(crearCelda(ticket.asignatura));
    fila.appendChild(crearCelda(ticket.grupo));
    fila.appendChild(crearCelda(insigniaPrioridad));
    fila.appendChild(crearCelda(insigniaEstado));
    fila.appendChild(crearCelda(ticket.docente));
    fila.appendChild(crearCelda(textoAsignado));
    fila.appendChild(crearCelda(cajaOperaciones));

    cuerpoTablaTickets.appendChild(fila);
}

/**
 * Solicita los tickets a la API y genera las filas.
 */
async function actualizarTabla() {
    //Elimina todas las filas actuales
    cuerpoTablaTickets.replaceChildren();

    try {
        //GET ?ruta=tickets
        const tickets = await obtenerTickets();

        if (tickets.length === 0) {
            agregarFilaVacia(cuerpoTablaTickets, 10, "No hay tickets registrados.");
            return;
        }

        //Genera una fila por ticket
        for (const ticket of tickets) {
            agregarFilaTicket(ticket);
        }
    } catch (error) {
        mostrarMensaje("No se pudieron cargar los tickets: " + error.message, "danger");
    }
}


/**
 * DETALLE DEL TICKET (MODAL)
 */

//Agrega al detalle un par etiqueta / valor
function agregarDato(etiqueta, valor) {
    const titulo = document.createElement("dt");
    titulo.className = "col-sm-4";
    titulo.textContent = etiqueta;

    const descripcion = document.createElement("dd");
    descripcion.className = "col-sm-8";
    descripcion.append(valor);

    detalleDatos.appendChild(titulo);
    detalleDatos.appendChild(descripcion);
}

//Completa los datos generales del ticket
function mostrarDatosTicket(ticket) {
    detalleDatos.replaceChildren();

    agregarDato("Fecha", formatearFecha(ticket.fecha));
    agregarDato("Horario", `${formatearHora(ticket.horaEntrada)} a ${formatearHora(ticket.horaSalida)}`);
    agregarDato("Sala", `${ticket.tipoSala} ${ticket.numeroSala}`);
    agregarDato("Asignatura", ticket.asignatura);
    agregarDato("Grupo", ticket.grupo);
    agregarDato("Turno", ticket.turno);
    agregarDato("Prioridad", crearInsignia(ticket.prioridadTexto, CLASES_PRIORIDAD[ticket.prioridad] ?? "bg-secondary"));
    agregarDato("Estado", crearInsignia(ticket.estado, CLASES_ESTADO[ticket.estado] ?? "bg-secondary"));
    agregarDato("Docente", ticket.docente);
    agregarDato("Asignado a", ticket.asignado === null ? "Sin asignar" : ticket.asignado);
}

//Completa la tabla con los equipos declarados en la planilla
function mostrarEquiposTicket(equipos) {
    cuerpoTablaEquipos.replaceChildren();

    if (equipos.length === 0) {
        agregarFilaVacia(cuerpoTablaEquipos, 5, "El ticket no tiene equipos.");
        return;
    }

    for (const equipo of equipos) {
        const fila = document.createElement("tr");

        //Un equipo sin incidencia llega con incidencia en null
        const incidencia = equipo.incidencia === null
            ? crearInsignia("Sin incidencia", "bg-success")
            : crearInsignia(equipo.incidencia, "bg-danger");

        fila.appendChild(crearCelda(equipo.numeroEquipo));
        fila.appendChild(crearCelda(equipo.utilizado ? "Sí" : "No"));
        fila.appendChild(crearCelda(equipo.estudiante ?? "-"));
        fila.appendChild(crearCelda(incidencia));
        fila.appendChild(crearCelda(equipo.descripcion ?? "-"));

        cuerpoTablaEquipos.appendChild(fila);
    }
}

//Completa la lista de comentarios, del más reciente al más antiguo (así los entrega la API)
function mostrarComentariosTicket(comentarios) {
    listaComentarios.replaceChildren();

    if (comentarios.length === 0) {
        const elemento = document.createElement("li");
        elemento.className = "list-group-item text-muted";
        elemento.textContent = "El ticket no tiene comentarios.";
        listaComentarios.appendChild(elemento);
        return;
    }

    for (const comentario of comentarios) {
        const elemento = document.createElement("li");
        elemento.className = "list-group-item";

        const autor = document.createElement("span");
        autor.className = "fw-semibold";
        autor.textContent = comentario.usuario;

        const fecha = document.createElement("small");
        fecha.className = "text-muted ms-2";
        fecha.textContent = formatearFechaHora(comentario.fecha);

        const texto = document.createElement("p");
        texto.className = "mb-0";
        texto.textContent = comentario.comentario;

        //Conserva los saltos de línea del comentario
        texto.style.whiteSpace = "pre-line";

        elemento.appendChild(autor);
        elemento.appendChild(fecha);
        elemento.appendChild(texto);

        if (puedeOperarComentario(comentario)) {
            const cajaComentario = document.createElement("div");
            cajaComentario.className = "d-flex gap-2 mt-2";
            cajaComentario.appendChild(
                crearBotonOperacion("Editar", "btn-outline-secondary", () => iniciarEdicionComentario(comentario))
            );
            cajaComentario.appendChild(
                crearBotonOperacion("Eliminar", "btn-outline-danger", () => eliminarComentarioTicket(comentario.id))
            );
            elemento.appendChild(cajaComentario);
        }

        listaComentarios.appendChild(elemento);
    }
}

//Completa los controles de estado y asignación con los valores actuales (solo los ven administrador y técnico)
function mostrarControlesGestion(detalle) {
    selectEstado.value = detalle.ticket.estado;

    //Solo el administrador recibe la lista de técnicos
    selectTecnico.replaceChildren();

    if (detalle.tecnicos.length === 0) {
        const opcion = document.createElement("option");
        opcion.textContent = "No hay técnicos registrados";
        opcion.value = "";
        selectTecnico.appendChild(opcion);
    }

    for (const tecnico of detalle.tecnicos) {
        const opcion = document.createElement("option");
        opcion.value = tecnico.cedula;
        opcion.textContent = `${tecnico.nombre} ${tecnico.apellido}`;
        selectTecnico.appendChild(opcion);
    }

    selectTecnico.disabled = detalle.tecnicos.length === 0;
    btnAsignarTecnico.disabled = detalle.tecnicos.length === 0;

    if (detalle.ticket.cedulaAsignado !== null) {
        selectTecnico.value = detalle.ticket.cedulaAsignado;
    }
}

//Completa todo el modal con el detalle recibido de la API
function mostrarDetalleTicket(detalle) {
    modalTitulo.textContent = `Ticket N° ${detalle.ticket.id}`;
    mostrarDatosTicket(detalle.ticket);
    mostrarEquiposTicket(detalle.equipos);
    mostrarControlesGestion(detalle);
    mostrarComentariosTicket(detalle.comentarios);
}

//Consulta el ticket a la API y abre el modal con su detalle
async function abrirDetalleTicket(id) {
    ocultarMensaje();

    try {
        //GET ?ruta=tickets&id={id}
        const detalle = await obtenerTicket(id);

        idTicketAbierto = detalle.ticket.id;
        ocultarMensajeDetalle();
        terminarEdicionComentario();
        mostrarDetalleTicket(detalle);

        modalDetalleTicket.show();
    } catch (error) {
        mostrarMensaje(error.message, "danger");
    }
}

//Tras una operación hecha desde el detalle, vuelve a consultar el ticket y la tabla al servidor
async function recargarDetalle(mensaje) {
    try {
        const detalle = await obtenerTicket(idTicketAbierto);

        mostrarDetalleTicket(detalle);
        await actualizarTabla();
        mostrarMensajeDetalle(mensaje, "success");
    } catch (error) {
        mostrarMensajeDetalle(error.message, "danger");
    }
}

//Limpia el estado del detalle al cerrarlo
function limpiarEstadoDetalle() {
    idTicketAbierto = null;
    ocultarMensajeDetalle();
    terminarEdicionComentario();
}


/**
 * ESTADO Y ASIGNACIÓN (DETALLE)
 */

//PATCH - Cambia el estado del ticket abierto
async function actualizarEstado() {
    ocultarMensajeDetalle();

    try {
        //PATCH ?ruta=tickets {id, estado}
        const resultado = await cambiarEstadoTicket(idTicketAbierto, selectEstado.value);
        await recargarDetalle(resultado.mensaje);
    } catch (error) {
        mostrarMensajeDetalle(error.message, "danger");
    }
}

//PATCH - El administrador asigna el ticket al técnico elegido
async function asignarResponsable() {
    ocultarMensajeDetalle();

    try {
        //PATCH ?ruta=tickets {id, cedulaAsignado}
        const resultado = await asignarTicket(idTicketAbierto, selectTecnico.value);
        await recargarDetalle(resultado.mensaje);
    } catch (error) {
        mostrarMensajeDetalle(error.message, "danger");
    }
}

//PATCH - El técnico se asigna el ticket a sí mismo (el servidor usa la cédula de su sesión)
async function autoasignarTicket() {
    ocultarMensajeDetalle();

    try {
        const resultado = await asignarTicket(idTicketAbierto, cedulaPropia);
        await recargarDetalle(resultado.mensaje);
    } catch (error) {
        mostrarMensajeDetalle(error.message, "danger");
    }
}


/**
 * COMENTARIOS (DETALLE)
 */

//Vuelve el formulario de comentarios al modo "comentario nuevo"
function terminarEdicionComentario() {
    idComentarioEnEdicion = null;
    formularioComentario.reset();
    btnGuardarComentario.textContent = "Comentar";
    btnCancelarComentario.classList.add("d-none");
}

//Carga el comentario en el formulario para modificarlo
function iniciarEdicionComentario(comentario) {
    ocultarMensajeDetalle();
    idComentarioEnEdicion = comentario.id;
    entradaComentario.value = comentario.comentario;
    btnGuardarComentario.textContent = "Guardar cambios";
    btnCancelarComentario.classList.remove("d-none");
    entradaComentario.focus();
}

//Decide si debe realizarse un POST o un PUT dependiendo del estado del formulario de comentarios
async function gestionarComentario(eventoFormulario) {
    eventoFormulario.preventDefault();
    ocultarMensajeDetalle();

    const texto = entradaComentario.value.trim();

    if (texto === "") {
        mostrarMensajeDetalle("Falta completar el texto del comentario", "danger");
        return;
    }

    btnGuardarComentario.disabled = true;

    try {
        let resultado;

        //POST ?ruta=tickets&recurso=comentarios
        if (idComentarioEnEdicion === null) {
            resultado = await agregarComentario(idTicketAbierto, texto);
        } else {
            //PUT ?ruta=tickets&recurso=comentarios
            resultado = await modificarComentario(idComentarioEnEdicion, texto);
        }

        terminarEdicionComentario();
        await recargarDetalle(resultado.mensaje);
    } catch (error) {
        mostrarMensajeDetalle(error.message, "danger");
    } finally {
        btnGuardarComentario.disabled = false;
    }
}

//DELETE - Elimina un comentario del ticket abierto
async function eliminarComentarioTicket(idComentario) {
    if (!window.confirm("¿Está seguro de eliminar el comentario?")) {
        return;
    }

    ocultarMensajeDetalle();

    try {
        //DELETE ?ruta=tickets&recurso=comentarios
        const resultado = await eliminarComentarioApi(idComentario);

        //Si se estaba editando justo ese comentario, se descarta la edición
        if (idComentarioEnEdicion === idComentario) {
            terminarEdicionComentario();
        }

        await recargarDetalle(resultado.mensaje);
    } catch (error) {
        mostrarMensajeDetalle(error.message, "danger");
    }
}


/**
 * FORMULARIO DE ALTA Y MODIFICACIÓN (MODAL)
 */

//Limpia todos los campos y configuraciones seleccionadas del formulario
function limpiarEstadoGestionarTicket() {
    mensajeFormulario.className = "alert d-none";
    ticketEnEdicion = null;
    filasEquipos = [];
    cuerpoTablaFormularioEquipos.replaceChildren();
    formularioGestionarTicket.reset();
}

//Cierra el modal
function cerrarGestionarTicket() {
    modalGestionarTicket.hide();
    limpiarEstadoGestionarTicket();
}

//Carga las salas del inventario en el selector
async function cargarSalas() {
    //GET ?ruta=inventario
    salasInventario = await obtenerSalas();

    entradaSala.replaceChildren();

    const opcionInicial = document.createElement("option");
    opcionInicial.value = "";
    opcionInicial.disabled = true;
    opcionInicial.selected = true;
    opcionInicial.textContent = "Seleccione una sala";
    entradaSala.appendChild(opcionInicial);

    for (const sala of salasInventario) {
        const opcion = document.createElement("option");
        opcion.value = sala.id;
        opcion.textContent = `${sala.tipo} ${sala.numero}`;
        entradaSala.appendChild(opcion);
    }
}

//Crea la fila de un equipo de la planilla. Los datos del estudiante, la incidencia y la descripción solo se habilitan si fue utilizado
function agregarFilaFormularioEquipo(equipo, previo) {
    const fila = document.createElement("tr");

    const casilla = document.createElement("input");
    casilla.type = "checkbox";
    casilla.className = "form-check-input";
    casilla.setAttribute("aria-label", `Equipo ${equipo.numero} utilizado`);

    const estudiante = document.createElement("input");
    estudiante.type = "text";
    estudiante.className = "form-control form-control-sm";
    estudiante.placeholder = "Nombre del estudiante";
    estudiante.maxLength = 100;

    const incidencia = document.createElement("select");
    incidencia.className = "form-select form-select-sm";
    const opcionSin = document.createElement("option");
    opcionSin.value = "";
    opcionSin.textContent = "Sin incidencia";
    incidencia.appendChild(opcionSin);
    for (const nombre of INCIDENCIAS) {
        const opcion = document.createElement("option");
        opcion.value = nombre;
        opcion.textContent = nombre;
        incidencia.appendChild(opcion);
    }

    const descripcion = document.createElement("input");
    descripcion.type = "text";
    descripcion.className = "form-control form-control-sm";
    descripcion.placeholder = "Detalle (opcional)";
    descripcion.maxLength = 255;

    //Al desmarcar el equipo se deshabilitan y se vacían sus campos
    function actualizarCampos() {
        for (const campo of [estudiante, incidencia, descripcion]) {
            campo.disabled = !casilla.checked;

            if (!casilla.checked) {
                campo.value = "";
            }
        }
    }

    //Al modificar un ticket se cargan los datos que ya tenía la planilla
    if (previo !== undefined && previo.utilizado) {
        casilla.checked = true;
        estudiante.value = previo.estudiante ?? "";
        incidencia.value = previo.incidencia ?? "";
        descripcion.value = previo.descripcion ?? "";
    }

    actualizarCampos();
    casilla.addEventListener("change", actualizarCampos);

    fila.appendChild(crearCelda(casilla));
    fila.appendChild(crearCelda(equipo.numero));
    fila.appendChild(crearCelda(estudiante));
    fila.appendChild(crearCelda(incidencia));
    fila.appendChild(crearCelda(descripcion));

    cuerpoTablaFormularioEquipos.appendChild(fila);
    filasEquipos.push({ equipo, casilla, estudiante, incidencia, descripcion });
}

//Genera las filas con los equipos de la sala elegida. "previos" son los equipos que ya tenía el ticket (si se modifica)
function mostrarEquiposSala(idSala, previos = []) {
    cuerpoTablaFormularioEquipos.replaceChildren();
    filasEquipos = [];

    const sala = salasInventario.find((s) => s.id === idSala);

    if (sala === undefined || sala.equipos.length === 0) {
        agregarFilaVacia(cuerpoTablaFormularioEquipos, 5, "Esta sala todavía no tiene equipos registrados en el inventario.");
        return;
    }

    for (const equipo of sala.equipos) {
        agregarFilaFormularioEquipo(equipo, previos.find((p) => p.idEquipo === equipo.id));
    }
}

//Abre el modal para registrar un nuevo ticket
async function abrirAltaTicket() {
    ocultarMensaje();

    try {
        await cargarSalas();

        limpiarEstadoGestionarTicket();
        modalGestionTitulo.textContent = "Nuevo ticket - Planilla de uso de sala";

        modalGestionarTicket.show();
    } catch (error) {
        mostrarMensaje("No se pudo cargar el inventario: " + error.message, "danger");
    }
}

//Abre el modal con los datos actuales del ticket para poder modificarlos
async function abrirModificarTicket(id) {
    ocultarMensaje();

    try {
        //GET ?ruta=tickets&id={id} y GET ?ruta=inventario
        const detalle = await obtenerTicket(id);
        await cargarSalas();

        limpiarEstadoGestionarTicket();
        ticketEnEdicion = detalle.ticket.id;

        //Se cargan en el formulario los datos recibidos desde la API
        const ticket = detalle.ticket;
        entradaSala.value = ticket.idSala;
        entradaFecha.value = ticket.fecha;
        entradaHoraEntrada.value = formatearHora(ticket.horaEntrada);
        entradaHoraSalida.value = formatearHora(ticket.horaSalida);
        entradaAsignatura.value = ticket.asignatura;
        entradaGrupo.value = ticket.grupo;
        entradaTurno.value = ticket.turno;
        mostrarEquiposSala(ticket.idSala, detalle.equipos);

        modalGestionTitulo.textContent = `Modificar ticket N° ${ticket.id}`;

        modalGestionarTicket.show();
    } catch (error) {
        mostrarMensaje(error.message, "danger");
    }
}

//Captura los datos ingresados en el formulario. Se envían todos los equipos de la sala, como en la planilla en papel
function obtenerDatosFormularioTicket() {
    const ticket = {
        idSala: Number(entradaSala.value),
        fecha: entradaFecha.value,
        horaEntrada: entradaHoraEntrada.value,
        horaSalida: entradaHoraSalida.value,
        asignatura: entradaAsignatura.value.trim(),
        grupo: entradaGrupo.value.trim(),
        turno: entradaTurno.value,
        equipos: []
    };

    for (const fila of filasEquipos) {
        const utilizado = fila.casilla.checked;

        ticket.equipos.push({
            idEquipo: fila.equipo.id,
            utilizado: utilizado,
            estudiante: utilizado ? fila.estudiante.value.trim() : "",
            incidencia: utilizado ? fila.incidencia.value : "",
            descripcion: utilizado ? fila.descripcion.value.trim() : ""
        });
    }

    if (ticketEnEdicion !== null) {
        ticket.id = ticketEnEdicion;
    }

    return ticket;
}

/**
 * Decide si debe realizarse un POST o un PUT dependiendo del estado del formulario.
 */
async function gestionarTicket(eventoFormulario) {
    eventoFormulario.preventDefault();
    ocultarMensaje();
    mensajeFormulario.className = "alert d-none";

    btnGuardarTicket.disabled = true;

    try {
        const ticket = obtenerDatosFormularioTicket();

        if (ticket.horaSalida <= ticket.horaEntrada) {
            throw new Error("La hora de salida debe ser posterior a la hora de entrada");
        }

        if (ticket.equipos.length === 0) {
            throw new Error("La sala elegida no tiene equipos registrados en el inventario");
        }

        let resultado;

        //POST ?ruta=tickets
        if (ticketEnEdicion === null) {
            resultado = await altaTicket(ticket);
        } else {
            //PUT ?ruta=tickets
            resultado = await modificarTicket(ticket);
        }

        cerrarGestionarTicket();
        await actualizarTabla();
        mostrarMensaje(resultado.mensaje, "success");
    } catch (error) {
        //El modal sigue abierto para que se pueda corregir el dato, por eso el error se muestra dentro de él
        mostrarErrorFormulario(error.message);
    } finally {
        btnGuardarTicket.disabled = false;
    }
}


/**
 * EVENTOS
 */

//Alta o modificación de tickets
formularioGestionarTicket.addEventListener("submit", gestionarTicket);
btnAltaTicket.addEventListener("click", abrirAltaTicket);

//Al elegir otra sala se muestran sus equipos
entradaSala.addEventListener("change", () => mostrarEquiposSala(Number(entradaSala.value)));

//Al cerrar el modal (con la X, Escape o haciendo clic afuera) se limpia el estado del formulario
elementoModalGestion.addEventListener("hidden.bs.modal", limpiarEstadoGestionarTicket);

//Detalle: estado, asignación y comentarios
btnCambiarEstado.addEventListener("click", actualizarEstado);
btnAsignarTecnico.addEventListener("click", asignarResponsable);
btnAutoasignar.addEventListener("click", autoasignarTicket);
formularioComentario.addEventListener("submit", gestionarComentario);
btnCancelarComentario.addEventListener("click", terminarEdicionComentario);
elementoModal.addEventListener("hidden.bs.modal", limpiarEstadoDetalle);

//GET ?ruta=tickets
actualizarTabla();
