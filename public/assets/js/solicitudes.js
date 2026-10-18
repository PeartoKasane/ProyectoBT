/**
 * CONSTANTES Y VARIABLES NECESARIAS
 * La comunicación con la API (solicitarApi, ErrorApi) y el cierre de sesión están en api.js y sesion.js.
 */

//Ruta de la API (declarada en el archivo de rutas del backend)
const RUTA_SOLICITUDES = "solicitudes";

//Modales de Bootstrap, se manejan desde JavaScript para poder abrirlos y cerrarlos según la operación
const modalNuevaSolicitud = new bootstrap.Modal(document.getElementById("modalNuevaSolicitud"));
const modalDetalleSolicitud = new bootstrap.Modal(document.getElementById("modalDetalleSolicitud"));
const modalTitulo = document.getElementById("modalDetalleSolicitudLabel");
const tituloFormulario = document.getElementById("modalNuevaSolicitudLabel");

//Constantes para los botones de la página
const btnNuevaSolicitud = document.getElementById("btnNuevaSolicitud");
const btnActualizarEstado = document.getElementById("btnActualizarEstado");
const btnAsignar = document.getElementById("btnAsignar");
const btnGuardarSolicitud = document.getElementById("btnGuardarSolicitud");
const btnGuardarComentario = document.getElementById("btnGuardarComentario");
const btnCancelarEdicionComentario = document.getElementById("btnCancelarEdicionComentario");

//Constante para trabajar con la tabla de solicitudes
const cuerpoTablaSolicitudes = document.getElementById("cuerpoTablaSolicitudes");

//Constantes para mostrar mensajes de resultado o de error (en la página, en el formulario y en el detalle)
const mensajeAlerta = document.getElementById("mensajeAlerta");
const mensajeFormulario = document.getElementById("mensajeFormulario");
const mensajeDetalle = document.getElementById("mensajeDetalle");

//Formulario de nueva solicitud y sus campos
const formularioNuevaSolicitud = document.getElementById("formularioNuevaSolicitud");
const entradaTipo = document.getElementById("tipo");
const entradaSala = document.getElementById("idSala");
const entradaPrograma = document.getElementById("programa");
const cajaPrograma = document.getElementById("cajaPrograma");
const entradaDetalle = document.getElementById("detalle");
const entradaFecha = document.getElementById("fecha");
const entradaHora = document.getElementById("hora");

//Constantes para completar el detalle dentro del modal
const detalleDatos = document.getElementById("detalleDatos");
const listaComentarios = document.getElementById("listaComentarios");
const seccionPrestamo = document.getElementById("seccionPrestamo");
const textoPrestamo = document.getElementById("textoPrestamo");
const enlacePrestamo = document.getElementById("enlacePrestamo");
const selectEstado = document.getElementById("selectEstado");
const selectTecnico = document.getElementById("selectTecnico");
const formularioComentario = document.getElementById("formularioComentario");
const entradaComentario = document.getElementById("comentario");

//Clases de Bootstrap de las insignias de estado
const CLASES_ESTADO = {
    "Pendiente": "bg-secondary",
    "En proceso": "bg-warning text-dark",
    "Resuelta": "bg-success"
};

//Rol de quien tiene la sesión iniciada: "administrador", "docente", "direccion" o "tecnico"
const rolActual = sessionStorage.getItem(CLAVE_ROL);

//Cédula de quien tiene la sesión iniciada, se usa para saber qué comentarios puede modificar
const cedulaPropia = sessionStorage.getItem(CLAVE_CEDULA);

//Id de la solicitud que se muestra en el modal de detalle
let solicitudAbierta = null;

//Auxiliares para saber si el formulario está registrando (null) o editando una solicitud / un comentario
let solicitudEnEdicion = null;
let comentarioEnEdicion = null;


/**
 * MENSAJES AL USUARIO
 */

//Muestra un mensaje en el elemento indicado. El tipo es el sufijo de la clase de Bootstrap: "success" o "danger"
function mostrarMensajeEn(elemento, mensaje, tipo) {
    elemento.textContent = mensaje;
    elemento.className = "alert alert-" + tipo + " shadow-sm";
}

function ocultarMensajeEn(elemento) {
    elemento.className = "alert d-none";
}

//Mensajes dentro de la página
function mostrarMensaje(mensaje, tipo) {
    mostrarMensajeEn(mensajeAlerta, mensaje, tipo);
}

function ocultarMensaje() {
    ocultarMensajeEn(mensajeAlerta);
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

//Una solicitud sin sala (posible en un préstamo) llega con tipoSala en null
function textoSala(solicitud) {
    return solicitud.tipoSala === null ? "-" : `${solicitud.tipoSala} ${solicitud.numeroSala}`;
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

//El administrador modifica cualquier solicitud; el docente solo las suyas mientras estén Pendientes (el servidor lo vuelve a verificar)
function puedeModificarSolicitud(solicitud) {
    if (rolActual === "administrador") {
        return true;
    }

    return rolActual === "docente" && solicitud.cedulaDocente === cedulaPropia && solicitud.estado === "Pendiente";
}

//Crea una fila de la tabla a partir de una solicitud
function agregarFilaSolicitud(solicitud) {
    const fila = document.createElement("tr");

    const cajaOperaciones = document.createElement("div");
    cajaOperaciones.className = "d-flex justify-content-center gap-2";
    cajaOperaciones.appendChild(
        crearBotonOperacion("Ver detalle", "btn-outline-primary", () => abrirDetalleSolicitud(solicitud.id))
    );

    if (puedeModificarSolicitud(solicitud)) {
        cajaOperaciones.appendChild(
            crearBotonOperacion("Editar", "btn-outline-secondary", () => abrirFormularioSolicitud(solicitud))
        );
        cajaOperaciones.appendChild(
            crearBotonOperacion("Eliminar", "btn-outline-danger", () => eliminarSolicitud(solicitud))
        );
    }

    const celdaDetalle = crearCelda(solicitud.detalle);
    celdaDetalle.className = "text-start";

    fila.appendChild(crearCelda(solicitud.id));
    fila.appendChild(crearCelda(solicitud.tipo));
    fila.appendChild(crearCelda(textoSala(solicitud)));
    fila.appendChild(celdaDetalle);
    fila.appendChild(crearCelda(`${formatearFecha(solicitud.fecha)} ${formatearHora(solicitud.hora)}`));
    fila.appendChild(crearCelda(solicitud.docente));
    fila.appendChild(crearCelda(crearInsignia(solicitud.estado, CLASES_ESTADO[solicitud.estado] ?? "bg-secondary")));
    fila.appendChild(crearCelda(solicitud.asignado === null ? "Sin asignar" : solicitud.asignado));
    fila.appendChild(crearCelda(cajaOperaciones));

    cuerpoTablaSolicitudes.appendChild(fila);
}

//GET - Solicita las solicitudes a la API y genera las filas
async function actualizarTabla() {
    //Elimina todas las filas actuales
    cuerpoTablaSolicitudes.replaceChildren();

    try {
        const solicitudes = await solicitarApi(RUTA_SOLICITUDES);

        if (solicitudes.length === 0) {
            agregarFilaVacia(cuerpoTablaSolicitudes, 9, "No hay solicitudes registradas todavía.");
            return;
        }

        for (const solicitud of solicitudes) {
            agregarFilaSolicitud(solicitud);
        }
    } catch (error) {
        mostrarMensaje("No se pudieron cargar las solicitudes: " + error.message, "danger");
    }
}


/**
 * ALTA Y EDICIÓN DE SOLICITUDES (MODAL)
 */

//Muestra u oculta el campo "Programa", que solo corresponde a una preparación de laboratorio
function actualizarCamposSegunTipo() {
    const esPreparacion = entradaTipo.value === "Preparación de laboratorio";

    cajaPrograma.classList.toggle("d-none", !esPreparacion);
    entradaPrograma.required = esPreparacion;
    //En una preparación la sala es obligatoria; en un préstamo es opcional
    entradaSala.required = esPreparacion;
}

//GET - Carga las salas en la lista desplegable del formulario
async function cargarSalas() {
    const salas = await solicitarApi(RUTA_SOLICITUDES, { parametros: { recurso: "salas" } });

    entradaSala.replaceChildren();

    const opcionVacia = document.createElement("option");
    opcionVacia.value = "";
    opcionVacia.textContent = "No aplica / a definir";
    entradaSala.appendChild(opcionVacia);

    for (const sala of salas) {
        const opcion = document.createElement("option");
        opcion.value = sala.id;
        opcion.textContent = `${sala.tipo} ${sala.numero}`;
        entradaSala.appendChild(opcion);
    }
}

//Abre el modal. Sin parámetro el formulario queda vacío (alta); con una solicitud se completa con sus datos (edición)
async function abrirFormularioSolicitud(solicitud = null) {
    ocultarMensaje();
    ocultarMensajeEn(mensajeFormulario);
    formularioNuevaSolicitud.reset();

    solicitudEnEdicion = solicitud;
    tituloFormulario.textContent = solicitud === null ? "Nueva solicitud" : `Editar solicitud N° ${solicitud.id}`;
    btnGuardarSolicitud.textContent = solicitud === null ? "Enviar solicitud" : "Guardar cambios";

    try {
        await cargarSalas();

        if (solicitud !== null) {
            entradaTipo.value = solicitud.tipo;
            entradaSala.value = solicitud.idSala === null ? "" : solicitud.idSala;
            entradaPrograma.value = solicitud.programa ?? "";
            entradaDetalle.value = solicitud.detalle;
            entradaFecha.value = solicitud.fecha;
            entradaHora.value = formatearHora(solicitud.hora);
        }

        actualizarCamposSegunTipo();
        modalNuevaSolicitud.show();
    } catch (error) {
        mostrarMensaje(error.message, "danger");
    }
}

//POST o PUT - Registra la solicitud con los datos del formulario, o guarda los cambios si se está editando
async function guardarSolicitud(evento) {
    evento.preventDefault();
    ocultarMensajeEn(mensajeFormulario);

    const cuerpo = {
        tipo: entradaTipo.value,
        idSala: entradaSala.value === "" ? null : Number(entradaSala.value),
        programa: entradaPrograma.value,
        detalle: entradaDetalle.value,
        fecha: entradaFecha.value,
        hora: entradaHora.value
    };

    try {
        let respuesta;

        if (solicitudEnEdicion === null) {
            respuesta = await solicitarApi(RUTA_SOLICITUDES, { metodo: "POST", cuerpo: cuerpo });
        } else {
            respuesta = await solicitarApi(RUTA_SOLICITUDES, { metodo: "PUT", cuerpo: { id: solicitudEnEdicion.id, ...cuerpo } });
        }

        modalNuevaSolicitud.hide();
        mostrarMensaje(respuesta.mensaje, "success");
        await actualizarTabla();
    } catch (error) {
        mostrarMensajeEn(mensajeFormulario, error.message, "danger");
    }
}

//DELETE - Elimina la solicitud (y sus comentarios) luego de pedir confirmación
async function eliminarSolicitud(solicitud) {
    if (!confirm(`¿Eliminar la solicitud N° ${solicitud.id}? Se eliminarán también sus comentarios.`)) {
        return;
    }

    ocultarMensaje();

    try {
        const respuesta = await solicitarApi(RUTA_SOLICITUDES, { metodo: "DELETE", cuerpo: { id: solicitud.id } });

        mostrarMensaje(respuesta.mensaje, "success");
        await actualizarTabla();
    } catch (error) {
        mostrarMensaje(error.message, "danger");
    }
}


/**
 * DETALLE DE LA SOLICITUD (MODAL)
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

//Completa los datos generales de la solicitud
function mostrarDatosSolicitud(solicitud) {
    detalleDatos.replaceChildren();

    agregarDato("Tipo", solicitud.tipo);
    agregarDato("Sala", textoSala(solicitud));
    agregarDato("Fecha", formatearFecha(solicitud.fecha));
    agregarDato("Hora", formatearHora(solicitud.hora));

    //Solo una preparación de laboratorio indica programa
    if (solicitud.programa !== null) {
        agregarDato("Programa", solicitud.programa);
    }

    agregarDato("Docente", solicitud.docente);
    agregarDato("Asignado a", solicitud.asignado === null ? "Sin asignar" : solicitud.asignado);
    agregarDato("Estado", crearInsignia(solicitud.estado, CLASES_ESTADO[solicitud.estado] ?? "bg-secondary"));
    agregarDato("Detalle", solicitud.detalle);
}

//Muestra el acceso a la entrega o a la devolución de equipos. La API informa el préstamo activo solo a administrador y técnico
function mostrarSeccionPrestamo(solicitud, prestamoActivo) {
    const puedeGestionar = rolActual === "administrador" || rolActual === "tecnico";

    if (!puedeGestionar || solicitud.tipo !== "Préstamo") {
        seccionPrestamo.classList.add("d-none");
        return;
    }

    if (prestamoActivo === null) {
        textoPrestamo.textContent = "La solicitud todavía no tiene equipos entregados.";
        enlacePrestamo.textContent = "Registrar entrega de equipos";
        enlacePrestamo.href = `./prestamos.html?nuevo=${encodeURIComponent(solicitud.id)}`;
    } else {
        textoPrestamo.textContent = `Préstamo activo desde ${formatearFechaHora(prestamoActivo.fechaHoraInicio)} a nombre de ${prestamoActivo.solicitanteNombre}.`;
        enlacePrestamo.textContent = "Registrar devolución";
        enlacePrestamo.href = `./prestamos.html?devolver=${encodeURIComponent(prestamoActivo.id)}`;
    }

    seccionPrestamo.classList.remove("d-none");
}

//Prepara los controles de estado y de asignación según el rol
function prepararGestion(solicitud, tecnicos) {
    selectEstado.value = solicitud.estado;

    selectTecnico.replaceChildren();

    if (rolActual === "administrador") {
        //El administrador elige a qué técnico asignar la solicitud
        for (const tecnico of tecnicos) {
            const opcion = document.createElement("option");
            opcion.value = tecnico.cedula;
            opcion.textContent = `${tecnico.nombre} ${tecnico.apellido}`;
            selectTecnico.appendChild(opcion);
        }

        if (solicitud.cedulaAsignado !== null) {
            selectTecnico.value = solicitud.cedulaAsignado;
        }

        selectTecnico.classList.remove("d-none");
        btnAsignar.textContent = "Asignar";
        btnAsignar.disabled = tecnicos.length === 0;
    } else {
        //El técnico solo puede autoasignarse
        selectTecnico.classList.add("d-none");
        btnAsignar.textContent = "Autoasignarme";
        btnAsignar.disabled = false;
    }
}

//Completa la lista de comentarios, del más reciente al más antiguo (así los entrega la API)
function mostrarComentarios(comentarios) {
    listaComentarios.replaceChildren();

    if (comentarios.length === 0) {
        const elemento = document.createElement("li");
        elemento.className = "list-group-item text-muted";
        elemento.textContent = "Todavía no hay comentarios.";
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

        elemento.appendChild(autor);
        elemento.appendChild(fecha);
        elemento.appendChild(texto);

        //Solo el autor o el administrador modifican un comentario (el servidor lo vuelve a verificar)
        if (rolActual === "administrador" || (comentario.cedula !== null && comentario.cedula === cedulaPropia)) {
            const cajaOperaciones = document.createElement("div");
            cajaOperaciones.className = "d-flex gap-2 mt-2";
            cajaOperaciones.appendChild(
                crearBotonOperacion("Editar", "btn-outline-secondary", () => iniciarEdicionComentario(comentario))
            );
            cajaOperaciones.appendChild(
                crearBotonOperacion("Eliminar", "btn-outline-danger", () => eliminarComentario(comentario))
            );
            elemento.appendChild(cajaOperaciones);
        }

        listaComentarios.appendChild(elemento);
    }
}

//RF31: completa el historial de acciones de la solicitud, de la más reciente a la más antigua (así las entrega la API)
function mostrarHistorial(historial) {
    const listaHistorial = document.getElementById("listaHistorial");
    listaHistorial.replaceChildren();

    if (historial.length === 0) {
        const vacio = document.createElement("li");
        vacio.className = "list-group-item text-muted";
        vacio.textContent = "Todavía no hay acciones registradas.";
        listaHistorial.appendChild(vacio);
        return;
    }

    for (const evento of historial) {
        const elemento = document.createElement("li");
        elemento.className = "list-group-item";

        const accion = document.createElement("span");
        accion.className = "fw-semibold";
        accion.textContent = evento.accion;

        const autor = evento.nombre === null ? "Sistema" : `${evento.nombre} ${evento.apellido}`;
        const datos = document.createElement("small");
        datos.className = "text-muted ms-2";
        datos.textContent = `${formatearFechaHora(evento.fecha)} - ${autor}`;

        elemento.appendChild(accion);
        elemento.appendChild(datos);

        if (evento.detalle !== null) {
            const detalle = document.createElement("p");
            detalle.className = "mb-0";
            detalle.textContent = evento.detalle;
            elemento.appendChild(detalle);
        }

        listaHistorial.appendChild(elemento);
    }
}

//GET - Carga el detalle de la solicitud en el modal (sin abrirlo, para poder refrescarlo tras una operación)
async function cargarDetalle(id) {
    const detalle = await solicitarApi(RUTA_SOLICITUDES, { parametros: { id: id } });

    solicitudAbierta = detalle.solicitud.id;
    modalTitulo.textContent = `Solicitud N° ${detalle.solicitud.id}`;

    mostrarDatosSolicitud(detalle.solicitud);
    mostrarSeccionPrestamo(detalle.solicitud, detalle.prestamoActivo);
    prepararGestion(detalle.solicitud, detalle.tecnicos);
    mostrarComentarios(detalle.comentarios);
    mostrarHistorial(detalle.historial);
}

//Consulta la solicitud a la API y abre el modal con su detalle
async function abrirDetalleSolicitud(id) {
    ocultarMensaje();
    ocultarMensajeEn(mensajeDetalle);
    terminarEdicionComentario();

    try {
        await cargarDetalle(id);
        modalDetalleSolicitud.show();
    } catch (error) {
        mostrarMensaje(error.message, "danger");
    }
}

//Ejecuta una operación sobre la solicitud abierta, muestra el resultado en el modal y refresca el detalle y la tabla
async function operarSobreSolicitud(operacion) {
    ocultarMensajeEn(mensajeDetalle);

    try {
        const respuesta = await operacion();

        await cargarDetalle(solicitudAbierta);
        mostrarMensajeEn(mensajeDetalle, respuesta.mensaje, "success");
        await actualizarTabla();
    } catch (error) {
        mostrarMensajeEn(mensajeDetalle, error.message, "danger");
    }
}

//PATCH - Cambia el estado de la solicitud
function actualizarEstado() {
    return operarSobreSolicitud(() => solicitarApi(RUTA_SOLICITUDES, {
        metodo: "PATCH",
        cuerpo: { id: solicitudAbierta, estado: selectEstado.value }
    }));
}

//PATCH - Asigna un responsable. Para el técnico la API ignora la cédula y lo asigna a sí mismo
function asignarResponsable() {
    const cedula = rolActual === "administrador" ? selectTecnico.value : (sessionStorage.getItem(CLAVE_CEDULA) ?? "");

    return operarSobreSolicitud(() => solicitarApi(RUTA_SOLICITUDES, {
        metodo: "PATCH",
        cuerpo: { id: solicitudAbierta, cedulaAsignado: cedula }
    }));
}

//Vuelve el formulario de comentarios al modo "agregar"
function terminarEdicionComentario() {
    comentarioEnEdicion = null;
    formularioComentario.reset();
    btnGuardarComentario.textContent = "Comentar";
    btnCancelarEdicionComentario.classList.add("d-none");
}

//Carga el texto del comentario en el formulario para modificarlo
function iniciarEdicionComentario(comentario) {
    comentarioEnEdicion = comentario.id;
    entradaComentario.value = comentario.comentario;
    btnGuardarComentario.textContent = "Guardar cambios";
    btnCancelarEdicionComentario.classList.remove("d-none");
    entradaComentario.focus();
}

//POST o PUT - Agrega un comentario a la solicitud, o guarda los cambios si se está editando uno
async function guardarComentario(evento) {
    evento.preventDefault();

    const respuesta = comentarioEnEdicion === null
        ? () => solicitarApi(RUTA_SOLICITUDES, {
            metodo: "POST",
            parametros: { recurso: "comentarios" },
            cuerpo: { id: solicitudAbierta, comentario: entradaComentario.value }
        })
        : () => solicitarApi(RUTA_SOLICITUDES, {
            metodo: "PUT",
            parametros: { recurso: "comentarios" },
            cuerpo: { id: comentarioEnEdicion, comentario: entradaComentario.value }
        });

    await operarSobreSolicitud(respuesta);

    //Solo se vacía el campo si la operación salió bien (si hubo error el texto se conserva para corregirlo)
    if (mensajeDetalle.classList.contains("alert-success")) {
        terminarEdicionComentario();
    }
}

//DELETE - Elimina un comentario luego de pedir confirmación
async function eliminarComentario(comentario) {
    if (!confirm("¿Eliminar este comentario?")) {
        return;
    }

    await operarSobreSolicitud(() => solicitarApi(RUTA_SOLICITUDES, {
        metodo: "DELETE",
        parametros: { recurso: "comentarios" },
        cuerpo: { id: comentario.id }
    }));

    //Si se eliminó el comentario que se estaba editando, el formulario vuelve al modo "agregar"
    if (comentarioEnEdicion === comentario.id) {
        terminarEdicionComentario();
    }
}


/**
 * EVENTOS
 */

if (btnNuevaSolicitud !== null) {
    btnNuevaSolicitud.addEventListener("click", () => abrirFormularioSolicitud());
}

entradaTipo.addEventListener("change", actualizarCamposSegunTipo);
formularioNuevaSolicitud.addEventListener("submit", guardarSolicitud);
btnActualizarEstado.addEventListener("click", actualizarEstado);
btnAsignar.addEventListener("click", asignarResponsable);
formularioComentario.addEventListener("submit", guardarComentario);
btnCancelarEdicionComentario.addEventListener("click", terminarEdicionComentario);

//GET ?ruta=solicitudes
actualizarTabla();

//Si se llegó desde otra página con ?id=N (por ejemplo desde el historial de préstamos) se abre ese detalle
const idEnUrl = new URLSearchParams(window.location.search).get("id");

if (idEnUrl !== null) {
    abrirDetalleSolicitud(idEnUrl);
}
