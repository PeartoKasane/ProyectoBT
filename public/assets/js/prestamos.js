/**
 * CONSTANTES Y VARIABLES NECESARIAS
 * La comunicación con la API (solicitarApi, ErrorApi) y el cierre de sesión están en api.js y sesion.js.
 */

//Ruta de la API (declarada en el archivo de rutas del backend)
const RUTA_PRESTAMOS = "prestamos";

//Modales de Bootstrap, se manejan desde JavaScript para poder abrirlos y cerrarlos según la operación
const modalNuevoPrestamo = new bootstrap.Modal(document.getElementById("modalNuevoPrestamo"));
const modalDevolucion = new bootstrap.Modal(document.getElementById("modalDevolucion"));

//Constante para trabajar con la tabla de préstamos
const cuerpoTablaPrestamos = document.getElementById("cuerpoTablaPrestamos");

//Constantes para mostrar mensajes de resultado o de error (en la página y dentro de cada modal)
const mensajeAlerta = document.getElementById("mensajeAlerta");
const mensajeFormularioPrestamo = document.getElementById("mensajeFormularioPrestamo");
const mensajeFormularioDevolucion = document.getElementById("mensajeFormularioDevolucion");

//Formulario de entrega y sus campos
const formularioNuevoPrestamo = document.getElementById("formularioNuevoPrestamo");
const entradaSolicitanteNombre = document.getElementById("solicitanteNombre");
const entradaTipoDocumento = document.getElementById("solicitanteTipoDocumento");
const entradaDocumento = document.getElementById("solicitanteDocumento");
const contenedorEquipos = document.getElementById("contenedorEquipos");
const seccionEquipos = document.getElementById("seccionEquipos");
const tituloFormularioPrestamo = document.getElementById("modalNuevoPrestamoLabel");
const btnGuardarPrestamo = document.getElementById("btnGuardarPrestamo");

//Formulario de devolución
const formularioDevolucion = document.getElementById("formularioDevolucion");
const datosDevolucion = document.getElementById("datosDevolucion");
const cuerpoTablaEquiposDevolucion = document.getElementById("cuerpoTablaEquiposDevolucion");

//Rol de quien tiene la sesión iniciada: solo administrador y técnico registran entregas y devoluciones (dirección solo consulta)
const rolActual = sessionStorage.getItem(CLAVE_ROL);
const puedeGestionar = rolActual === "administrador" || rolActual === "tecnico";

//Solo el administrador elimina préstamos
const puedeEliminar = rolActual === "administrador";

//Ids de la solicitud y del préstamo con los que se está trabajando en cada modal
let solicitudEnEntrega = null;
let prestamoEnDevolucion = null;

//Auxiliar para saber si el formulario está registrando una entrega (null) o editando un préstamo
let prestamoEnEdicion = null;


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

//Agrega un par etiqueta / valor a una lista de descripción (dl)
function agregarDato(lista, etiqueta, valor) {
    const titulo = document.createElement("dt");
    titulo.className = "col-sm-4";
    titulo.textContent = etiqueta;

    const descripcion = document.createElement("dd");
    descripcion.className = "col-sm-8";
    descripcion.append(valor);

    lista.appendChild(titulo);
    lista.appendChild(descripcion);
}


/**
 * GESTIÓN DE FILAS DE LA TABLA
 */

//Crea una fila de la tabla a partir de un préstamo
function agregarFilaPrestamo(prestamo) {
    const fila = document.createElement("tr");

    const enlaceSolicitud = document.createElement("a");
    enlaceSolicitud.href = `./solicitudes.html?id=${encodeURIComponent(prestamo.idSolicitud)}`;
    enlaceSolicitud.textContent = `#${prestamo.idSolicitud}`;

    const claseEstado = prestamo.estado === "Activo" ? "bg-warning text-dark" : "bg-success";

    const cajaOperaciones = document.createElement("div");
    cajaOperaciones.className = "d-flex justify-content-center gap-2";

    //Solo administrador y técnico registran la devolución de un préstamo activo
    if (puedeGestionar && prestamo.estado === "Activo") {
        cajaOperaciones.appendChild(
            crearBotonOperacion("Registrar devolución", "btn-outline-secondary", () => abrirDevolucion(prestamo.id))
        );
        //Un préstamo devuelto ya no se edita
        cajaOperaciones.appendChild(
            crearBotonOperacion("Editar", "btn-outline-primary", () => abrirEdicionPrestamo(prestamo))
        );
    }

    if (puedeEliminar) {
        cajaOperaciones.appendChild(
            crearBotonOperacion("Eliminar", "btn-outline-danger", () => eliminarPrestamo(prestamo))
        );
    }

    fila.appendChild(crearCelda(prestamo.id));
    fila.appendChild(crearCelda(prestamo.solicitanteNombre));
    fila.appendChild(crearCelda(`${prestamo.solicitanteTipoDocumento}: ${prestamo.solicitanteDocumento}`));
    fila.appendChild(crearCelda(formatearFechaHora(prestamo.fechaHoraInicio)));
    //Un préstamo todavía activo llega con fechaHoraFin en null
    fila.appendChild(crearCelda(prestamo.fechaHoraFin === null ? "-" : formatearFechaHora(prestamo.fechaHoraFin)));
    fila.appendChild(crearCelda(crearInsignia(prestamo.estado, claseEstado)));
    fila.appendChild(crearCelda(prestamo.responsable));
    fila.appendChild(crearCelda(enlaceSolicitud));
    fila.appendChild(crearCelda(cajaOperaciones));

    cuerpoTablaPrestamos.appendChild(fila);
}

//GET - Solicita el historial de préstamos a la API y genera las filas
async function actualizarTabla() {
    //Elimina todas las filas actuales
    cuerpoTablaPrestamos.replaceChildren();

    try {
        const prestamos = await solicitarApi(RUTA_PRESTAMOS);

        if (prestamos.length === 0) {
            agregarFilaVacia(cuerpoTablaPrestamos, 9, "No hay préstamos registrados todavía.");
            return;
        }

        for (const prestamo of prestamos) {
            agregarFilaPrestamo(prestamo);
        }
    } catch (error) {
        mostrarMensaje("No se pudieron cargar los préstamos: " + error.message, "danger");
    }
}


/**
 * ENTREGA DE EQUIPOS (MODAL)
 */

//Genera las casillas de los equipos libres, agrupadas por sala
function mostrarEquiposDisponibles(salas) {
    contenedorEquipos.replaceChildren();

    if (salas.length === 0) {
        const aviso = document.createElement("p");
        aviso.className = "text-muted";
        aviso.textContent = "No hay equipos disponibles para prestar.";
        contenedorEquipos.appendChild(aviso);
        return;
    }

    for (const sala of salas) {
        const titulo = document.createElement("p");
        titulo.className = "fw-semibold mb-1";
        titulo.textContent = `${sala.tipo} ${sala.numero}`;

        const fila = document.createElement("div");
        fila.className = "row mb-3";

        for (const equipo of sala.equipos) {
            const columna = document.createElement("div");
            columna.className = "col-4 col-md-2";

            const caja = document.createElement("div");
            caja.className = "form-check";

            const casilla = document.createElement("input");
            casilla.type = "checkbox";
            casilla.className = "form-check-input casilla-equipo";
            casilla.value = equipo.id;
            casilla.id = "equipo" + equipo.id;

            const etiqueta = document.createElement("label");
            etiqueta.className = "form-check-label";
            etiqueta.htmlFor = casilla.id;
            etiqueta.textContent = `N° ${equipo.numero}`;

            caja.appendChild(casilla);
            caja.appendChild(etiqueta);
            columna.appendChild(caja);
            fila.appendChild(columna);
        }

        contenedorEquipos.appendChild(titulo);
        contenedorEquipos.appendChild(fila);
    }
}

//GET - Abre el modal de entrega para una solicitud de tipo Préstamo
async function abrirNuevoPrestamo(idSolicitud) {
    ocultarMensaje();
    ocultarMensajeEn(mensajeFormularioPrestamo);
    formularioNuevoPrestamo.reset();

    try {
        const datos = await solicitarApi(RUTA_PRESTAMOS, {
            parametros: { recurso: "equiposDisponibles", idSolicitud: idSolicitud }
        });

        prestamoEnEdicion = null;
        tituloFormularioPrestamo.textContent = "Registrar entrega de equipos";
        btnGuardarPrestamo.textContent = "Registrar entrega";
        seccionEquipos.classList.remove("d-none");

        solicitudEnEntrega = datos.solicitud.id;
        //Por defecto retira quien hizo la solicitud, el nombre se puede corregir
        entradaSolicitanteNombre.value = datos.solicitud.docente;
        mostrarEquiposDisponibles(datos.salas);

        modalNuevoPrestamo.show();
    } catch (error) {
        mostrarMensaje(error.message, "danger");
    }
}

//Abre el mismo formulario de la entrega para editar los datos de quien retiró los equipos (sin la selección de equipos)
function abrirEdicionPrestamo(prestamo) {
    ocultarMensaje();
    ocultarMensajeEn(mensajeFormularioPrestamo);
    formularioNuevoPrestamo.reset();

    prestamoEnEdicion = prestamo.id;
    tituloFormularioPrestamo.textContent = `Editar préstamo N° ${prestamo.id}`;
    btnGuardarPrestamo.textContent = "Guardar cambios";
    seccionEquipos.classList.add("d-none");

    entradaSolicitanteNombre.value = prestamo.solicitanteNombre;
    entradaTipoDocumento.value = prestamo.solicitanteTipoDocumento;
    entradaDocumento.value = prestamo.solicitanteDocumento;

    modalNuevoPrestamo.show();
}

//DELETE - Elimina el préstamo y sus equipos luego de pedir confirmación
async function eliminarPrestamo(prestamo) {
    const aviso = prestamo.estado === "Activo"
        ? `El préstamo N° ${prestamo.id} sigue activo: sus equipos quedarán libres. ¿Eliminarlo?`
        : `¿Eliminar el préstamo N° ${prestamo.id}?`;

    if (!confirm(aviso)) {
        return;
    }

    ocultarMensaje();

    try {
        const respuesta = await solicitarApi(RUTA_PRESTAMOS, { metodo: "DELETE", cuerpo: { id: prestamo.id } });

        mostrarMensaje(respuesta.mensaje, "success");
        await actualizarTabla();
    } catch (error) {
        mostrarMensaje(error.message, "danger");
    }
}

//POST o PUT - Registra la entrega con los datos del formulario y los equipos marcados, o guarda la edición del préstamo
async function registrarPrestamo(evento) {
    evento.preventDefault();
    ocultarMensajeEn(mensajeFormularioPrestamo);

    if (prestamoEnEdicion !== null) {
        await guardarEdicionPrestamo();
        return;
    }

    const idsEquipo = [];
    for (const casilla of contenedorEquipos.querySelectorAll(".casilla-equipo:checked")) {
        idsEquipo.push(Number(casilla.value));
    }

    const cuerpo = {
        idSolicitud: solicitudEnEntrega,
        solicitanteNombre: entradaSolicitanteNombre.value,
        solicitanteTipoDocumento: entradaTipoDocumento.value,
        solicitanteDocumento: entradaDocumento.value,
        idsEquipo: idsEquipo
    };

    try {
        const respuesta = await solicitarApi(RUTA_PRESTAMOS, { metodo: "POST", cuerpo: cuerpo });

        modalNuevoPrestamo.hide();
        mostrarMensaje(respuesta.mensaje, "success");
        await actualizarTabla();
    } catch (error) {
        mostrarMensajeEn(mensajeFormularioPrestamo, error.message, "danger");
    }
}

//PUT - Guarda los datos de quien retiró los equipos
async function guardarEdicionPrestamo() {
    const cuerpo = {
        id: prestamoEnEdicion,
        solicitanteNombre: entradaSolicitanteNombre.value,
        solicitanteTipoDocumento: entradaTipoDocumento.value,
        solicitanteDocumento: entradaDocumento.value
    };

    try {
        const respuesta = await solicitarApi(RUTA_PRESTAMOS, { metodo: "PUT", cuerpo: cuerpo });

        modalNuevoPrestamo.hide();
        mostrarMensaje(respuesta.mensaje, "success");
        await actualizarTabla();
    } catch (error) {
        mostrarMensajeEn(mensajeFormularioPrestamo, error.message, "danger");
    }
}


/**
 * DEVOLUCIÓN (MODAL)
 */

//Completa los datos del préstamo y la tabla de equipos con la casilla "No se usó"
function mostrarDatosDevolucion(prestamo, equipos) {
    datosDevolucion.replaceChildren();
    agregarDato(datosDevolucion, "Solicitante", `${prestamo.solicitanteNombre} (${prestamo.solicitanteTipoDocumento} ${prestamo.solicitanteDocumento})`);
    agregarDato(datosDevolucion, "Inicio del préstamo", formatearFechaHora(prestamo.fechaHoraInicio));

    cuerpoTablaEquiposDevolucion.replaceChildren();

    if (equipos.length === 0) {
        agregarFilaVacia(cuerpoTablaEquiposDevolucion, 3, "El préstamo no tiene equipos.");
        return;
    }

    for (const equipo of equipos) {
        const fila = document.createElement("tr");

        const casilla = document.createElement("input");
        casilla.type = "checkbox";
        casilla.className = "form-check-input casilla-no-se-uso";
        //Es el id de PRESTAMO_EQUIPO, que es el que espera la API en noSeUso
        casilla.value = equipo.id;

        fila.appendChild(crearCelda(`${equipo.tipoSala} ${equipo.numeroSala}`));
        fila.appendChild(crearCelda(equipo.numeroEquipo));
        fila.appendChild(crearCelda(casilla));

        cuerpoTablaEquiposDevolucion.appendChild(fila);
    }
}

//GET - Abre el modal de devolución para un préstamo activo
async function abrirDevolucion(idPrestamo) {
    ocultarMensaje();
    ocultarMensajeEn(mensajeFormularioDevolucion);

    try {
        const detalle = await solicitarApi(RUTA_PRESTAMOS, { parametros: { id: idPrestamo } });

        if (detalle.prestamo.estado !== "Activo") {
            mostrarMensaje("El préstamo ya fue devuelto.", "danger");
            return;
        }

        prestamoEnDevolucion = detalle.prestamo.id;
        mostrarDatosDevolucion(detalle.prestamo, detalle.equipos);

        modalDevolucion.show();
    } catch (error) {
        mostrarMensaje(error.message, "danger");
    }
}

//PATCH - Registra la devolución con los equipos marcados como "No se usó"
async function registrarDevolucion(evento) {
    evento.preventDefault();
    ocultarMensajeEn(mensajeFormularioDevolucion);

    const noSeUso = [];
    for (const casilla of cuerpoTablaEquiposDevolucion.querySelectorAll(".casilla-no-se-uso:checked")) {
        noSeUso.push(Number(casilla.value));
    }

    try {
        const respuesta = await solicitarApi(RUTA_PRESTAMOS, {
            metodo: "PATCH",
            cuerpo: { id: prestamoEnDevolucion, noSeUso: noSeUso }
        });

        modalDevolucion.hide();
        mostrarMensaje(respuesta.mensaje, "success");
        await actualizarTabla();
    } catch (error) {
        mostrarMensajeEn(mensajeFormularioDevolucion, error.message, "danger");
    }
}


/**
 * EVENTOS
 */

formularioNuevoPrestamo.addEventListener("submit", registrarPrestamo);
formularioDevolucion.addEventListener("submit", registrarDevolucion);

//GET ?ruta=prestamos
actualizarTabla();

//Si se llegó desde el detalle de una solicitud (?nuevo=idSolicitud o ?devolver=idPrestamo) se abre el modal correspondiente
const parametrosUrl = new URLSearchParams(window.location.search);

if (puedeGestionar && parametrosUrl.get("nuevo") !== null) {
    abrirNuevoPrestamo(parametrosUrl.get("nuevo"));
} else if (puedeGestionar && parametrosUrl.get("devolver") !== null) {
    abrirDevolucion(parametrosUrl.get("devolver"));
}
