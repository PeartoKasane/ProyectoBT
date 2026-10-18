/**
 * CONSTANTES Y VARIABLES NECESARIAS
 */

//Modal de Bootstrap, se maneja desde JavaScript para poder abrirlo una vez que llegan los datos de la API
const elementoModal = document.getElementById("modalHistorialEquipo");
const modalHistorialEquipo = new bootstrap.Modal(elementoModal);
const modalTitulo = document.getElementById("modalHistorialEquipoLabel");

//Constante donde se genera una tarjeta por cada sala
const contenedorSalas = document.getElementById("contenedorSalas");

//Constantes para completar el historial dentro del modal
const detalleEquipo = document.getElementById("detalleEquipo");
const cuerpoTablaHistorial = document.getElementById("cuerpoTablaHistorial");

//Constante para mostrar mensajes de resultado o de error
const mensajeAlerta = document.getElementById("mensajeAlerta");

//Solo el administrador gestiona el inventario (el servidor igual lo verifica en cada petición), el resto consulta
const esAdministrador = sessionStorage.getItem(CLAVE_ROL) === "administrador";

//Modal de Bootstrap para el alta y la modificación de una sala
const modalGestionarSala = new bootstrap.Modal(document.getElementById("modalGestionarSala"));
const modalTituloSala = document.getElementById("modalGestionarSalaLabel");
const formularioGestionarSala = document.getElementById("formularioGestionarSala");
const mensajeFormularioSala = document.getElementById("mensajeFormularioSala");
const entradaTipoSala = document.getElementById("tipoSala");
const entradaNumeroSala = document.getElementById("numeroSala");
const btnConfirmarSala = document.getElementById("btnConfirmarSala");

//Modal de Bootstrap para el alta y la modificación de un equipo
const modalGestionarEquipo = new bootstrap.Modal(document.getElementById("modalGestionarEquipo"));
const modalTituloEquipo = document.getElementById("modalGestionarEquipoLabel");
const formularioGestionarEquipo = document.getElementById("formularioGestionarEquipo");
const mensajeFormularioEquipo = document.getElementById("mensajeFormularioEquipo");
const entradaSalaEquipo = document.getElementById("salaEquipo");
const entradaNumeroEquipo = document.getElementById("numeroEquipo");
const entradaEstadoEquipo = document.getElementById("estadoEquipo");
const entradaIncidenciaEquipo = document.getElementById("incidenciaEquipo");
const cajaIncidencia = document.getElementById("cajaIncidencia");
const btnConfirmarEquipo = document.getElementById("btnConfirmarEquipo");

//Botones de la barra de altas
const btnAltaSala = document.getElementById("btnAltaSala");
const btnAltaEquipo = document.getElementById("btnAltaEquipo");

//Auxiliares para saber qué se está modificando (null = se está agregando)
let salaEnEdicion = null;
let equipoEnEdicion = null;

//Últimas salas recibidas de la API, para completar el selector de sala del formulario de equipo
let salasActuales = [];

//Clases de Bootstrap de las insignias de estado del equipo
const CLASES_ESTADO = {
    "Funcionando": "bg-success",
    "Incidencia": "bg-warning text-dark",
    "Fuera de servicio": "bg-danger"
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

//Convierte una fecha con hora AAAA-MM-DD HH:MM:SS en DD/MM/AAAA HH:MM
function formatearFechaHora(fechaHora) {
    const [fecha, hora] = fechaHora.split(" ");

    if (hora === undefined) {
        return formatearFecha(fecha);
    }

    return `${formatearFecha(fecha)} ${hora.slice(0, 5)}`;
}


/**
 * OPERACIONES CON LA API
 */

/**
 * GET - Obtiene todas las salas, cada una con sus equipos.
 */
async function obtenerInventario() {
    return await solicitarApi("inventario");
}

/**
 * GET - Obtiene un equipo específico con su sala y su historial mediante su id.
 */
async function obtenerEquipo(idEquipo) {
    return await solicitarApi("inventario", { parametros: { idEquipo: idEquipo } });
}

/**
 * POST - Envía una nueva sala a la API.
 */
async function altaSala(sala) {
    return await solicitarApi("inventario", { metodo: "POST", parametros: { recurso: "salas" }, cuerpo: sala });
}

/**
 * PUT - Modifica el tipo y el número de una sala existente.
 */
async function modificarSala(sala) {
    return await solicitarApi("inventario", { metodo: "PUT", parametros: { recurso: "salas" }, cuerpo: sala });
}

/**
 * DELETE - Elimina una sala junto con sus equipos.
 */
async function bajaSala(idSala) {
    return await solicitarApi("inventario", { metodo: "DELETE", parametros: { recurso: "salas" }, cuerpo: { id: idSala } });
}

/**
 * POST - Envía un nuevo equipo a la API.
 */
async function altaEquipo(equipo) {
    return await solicitarApi("inventario", { metodo: "POST", parametros: { recurso: "equipos" }, cuerpo: equipo });
}

/**
 * PUT - Modifica el estado (y la incidencia) de un equipo existente.
 */
async function modificarEquipo(equipo) {
    return await solicitarApi("inventario", { metodo: "PUT", parametros: { recurso: "equipos" }, cuerpo: equipo });
}

/**
 * DELETE - Elimina un equipo.
 */
async function bajaEquipo(idEquipo) {
    return await solicitarApi("inventario", { metodo: "DELETE", parametros: { recurso: "equipos" }, cuerpo: { id: idEquipo } });
}


/**
 * CREACIÓN DE ELEMENTOS
 */

//Crea una celda de la tabla. El contenido puede ser un texto o un elemento
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
 * GESTIÓN DE SALAS Y EQUIPOS
 */

//Crea una fila de la tabla de equipos de una sala a partir de un equipo
function crearFilaEquipo(equipo) {
    const fila = document.createElement("tr");

    const insigniaEstado = crearInsignia(equipo.estado, CLASES_ESTADO[equipo.estado] ?? "bg-secondary");

    //Un equipo sin incidencia llega con incidencia en null
    const textoIncidencia = equipo.incidencia === null ? "-" : equipo.incidencia;

    const cajaOperaciones = document.createElement("div");
    cajaOperaciones.className = "d-flex justify-content-center gap-2";
    cajaOperaciones.appendChild(
        crearBotonOperacion("Ver historial", "btn-outline-secondary", () => abrirHistorialEquipo(equipo.id))
    );

    if (esAdministrador) {
        cajaOperaciones.appendChild(
            crearBotonOperacion("Modificar", "btn-outline-primary", () => abrirModificarEquipo(equipo))
        );
        cajaOperaciones.appendChild(
            crearBotonOperacion("Eliminar", "btn-outline-danger", () => eliminarEquipo(equipo))
        );
    }

    fila.appendChild(crearCelda(equipo.numero));
    fila.appendChild(crearCelda(insigniaEstado));
    fila.appendChild(crearCelda(textoIncidencia));
    fila.appendChild(crearCelda(cajaOperaciones));

    return fila;
}

//Crea la tarjeta de una sala con la tabla de sus equipos
function crearTarjetaSala(sala) {
    const columna = document.createElement("div");
    columna.className = "col-12 mb-4";

    const tarjeta = document.createElement("div");
    tarjeta.className = "card shadow-sm";

    const encabezado = document.createElement("div");
    encabezado.className = "card-header bg-dark text-white d-flex justify-content-between align-items-center";

    const titulo = document.createElement("h2");
    titulo.className = "h5 fw-bold m-0 text-white";
    titulo.textContent = `${sala.tipo} ${sala.numero}`;

    const cantidad = sala.equipos.length;
    const insigniaCantidad = crearInsignia(cantidad === 1 ? "1 equipo" : `${cantidad} equipos`, "bg-light text-dark");

    //Agrupa la cantidad de equipos y, para el administrador, las operaciones sobre la sala
    const cajaEncabezado = document.createElement("div");
    cajaEncabezado.className = "d-flex align-items-center flex-wrap gap-2";
    cajaEncabezado.appendChild(insigniaCantidad);

    if (esAdministrador) {
        cajaEncabezado.appendChild(
            crearBotonOperacion("Agregar equipo", "btn-outline-light", () => abrirAltaEquipo(sala.id))
        );
        cajaEncabezado.appendChild(
            crearBotonOperacion("Modificar sala", "btn-outline-light", () => abrirModificarSala(sala))
        );
        cajaEncabezado.appendChild(
            crearBotonOperacion("Eliminar sala", "btn-outline-danger", () => eliminarSala(sala))
        );
    }

    encabezado.appendChild(titulo);
    encabezado.appendChild(cajaEncabezado);

    const contenedorTabla = document.createElement("div");
    contenedorTabla.className = "table-responsive p-3";

    const tabla = document.createElement("table");
    tabla.className = "table table-striped table-hover align-middle m-0";

    const cabecera = document.createElement("thead");
    cabecera.className = "table-dark text-center";
    const filaCabecera = document.createElement("tr");

    for (const nombreColumna of ["N° de equipo", "Estado", "Incidencia", "Acciones"]) {
        const columnaCabecera = document.createElement("th");
        columnaCabecera.scope = "col";
        columnaCabecera.textContent = nombreColumna;
        filaCabecera.appendChild(columnaCabecera);
    }

    cabecera.appendChild(filaCabecera);

    const cuerpo = document.createElement("tbody");
    cuerpo.className = "text-center";

    if (cantidad === 0) {
        agregarFilaVacia(cuerpo, 4, "La sala no tiene equipos registrados.");
    }

    for (const equipo of sala.equipos) {
        cuerpo.appendChild(crearFilaEquipo(equipo));
    }

    tabla.appendChild(cabecera);
    tabla.appendChild(cuerpo);
    contenedorTabla.appendChild(tabla);

    tarjeta.appendChild(encabezado);
    tarjeta.appendChild(contenedorTabla);
    columna.appendChild(tarjeta);

    return columna;
}

/**
 * Solicita el inventario a la API y genera una tarjeta por sala.
 */
async function actualizarInventario() {
    //Elimina todas las tarjetas actuales
    contenedorSalas.replaceChildren();
    salasActuales = [];

    try {
        // GET index.php?ruta=inventario
        const salas = await obtenerInventario();
        salasActuales = salas;

        if (salas.length === 0) {
            const aviso = document.createElement("p");
            aviso.className = "text-center text-muted";
            aviso.textContent = "No hay salas registradas.";
            contenedorSalas.appendChild(aviso);
            return;
        }

        //Genera una tarjeta por sala
        for (const sala of salas) {
            contenedorSalas.appendChild(crearTarjetaSala(sala));
        }
    } catch (error) {
        mostrarMensaje("No se pudo cargar el inventario: " + error.message, "danger");
    }
}


/**
 * HISTORIAL DEL EQUIPO (MODAL)
 */

//Agrega al detalle del equipo un par etiqueta / valor
function agregarDato(etiqueta, valor) {
    const titulo = document.createElement("dt");
    titulo.className = "col-sm-4";
    titulo.textContent = etiqueta;

    const descripcion = document.createElement("dd");
    descripcion.className = "col-sm-8";
    descripcion.append(valor);

    detalleEquipo.appendChild(titulo);
    detalleEquipo.appendChild(descripcion);
}

//Completa los datos actuales del equipo
function mostrarDatosEquipo(equipo, sala) {
    detalleEquipo.replaceChildren();

    agregarDato("Sala", `${sala.tipo} ${sala.numero}`);
    agregarDato("Estado actual", crearInsignia(equipo.estado, CLASES_ESTADO[equipo.estado] ?? "bg-secondary"));
    agregarDato("Incidencia", equipo.incidencia === null ? "-" : equipo.incidencia);
}

//Completa la tabla con los eventos del equipo, del más reciente al más antiguo (así los entrega la API)
function mostrarHistorialEquipo(historial) {
    cuerpoTablaHistorial.replaceChildren();

    if (historial.length === 0) {
        agregarFilaVacia(cuerpoTablaHistorial, 4, "El equipo no tiene eventos registrados.");
        return;
    }

    for (const evento of historial) {
        const fila = document.createElement("tr");

        fila.appendChild(crearCelda(formatearFechaHora(evento.fecha)));
        fila.appendChild(crearCelda(evento.accion));
        fila.appendChild(crearCelda(evento.detalle ?? "-"));
        fila.appendChild(crearCelda(evento.usuario));

        cuerpoTablaHistorial.appendChild(fila);
    }
}

//Consulta el equipo a la API y abre el modal con su historial
async function abrirHistorialEquipo(idEquipo) {
    ocultarMensaje();

    try {
        // GET index.php?ruta=inventario&idEquipo={idEquipo}
        const detalle = await obtenerEquipo(idEquipo);

        modalTitulo.textContent = `Historial del equipo N° ${detalle.equipo.numero}`;
        mostrarDatosEquipo(detalle.equipo, detalle.sala);
        mostrarHistorialEquipo(detalle.historial);

        modalHistorialEquipo.show();
    } catch (error) {
        mostrarMensaje(error.message, "danger");
    }
}


/**
 * ALTA, MODIFICACIÓN Y BAJA DE SALAS
 */

//Muestra un error dentro de un modal, donde el usuario puede verlo
function mostrarMensajeFormulario(elemento, mensaje) {
    elemento.textContent = mensaje;
    elemento.className = "alert alert-danger";
}

//Prepara el modal de sala y lo abre. Sin sala se está agregando, con sala se está modificando
function abrirModalSala(sala) {
    mensajeFormularioSala.className = "alert d-none";
    formularioGestionarSala.reset();
    salaEnEdicion = sala;

    if (sala === null) {
        modalTituloSala.textContent = "Agregar sala";
    } else {
        modalTituloSala.textContent = "Modificar sala";
        entradaTipoSala.value = sala.tipo;
        entradaNumeroSala.value = sala.numero;
    }

    modalGestionarSala.show();
}

function abrirAltaSala() {
    ocultarMensaje();
    abrirModalSala(null);
}

function abrirModificarSala(sala) {
    ocultarMensaje();
    abrirModalSala(sala);
}

//Valida el formulario de sala, la API lo vuelve a validar. Retorna la sala o null si hay un error
function obtenerDatosFormularioSala() {
    const tipo = entradaTipoSala.value;
    const numero = entradaNumeroSala.value.trim();

    if (tipo === "" || numero === "") {
        mostrarMensajeFormulario(mensajeFormularioSala, "Existen campos vacíos");
        return null;
    }

    if (!/^[0-9]{1,10}$/.test(numero) || Number(numero) < 1) {
        mostrarMensajeFormulario(mensajeFormularioSala, "El número de sala debe ser un entero positivo");
        return null;
    }

    return { tipo: tipo, numero: Number(numero) };
}

//Envía el formulario de sala: POST si se está agregando, PUT si se está modificando
async function confirmarSala(evento) {
    evento.preventDefault();
    mensajeFormularioSala.className = "alert d-none";

    const sala = obtenerDatosFormularioSala();

    if (sala === null) {
        return;
    }

    try {
        btnConfirmarSala.disabled = true;

        let resultado;

        if (salaEnEdicion === null) {
            // POST index.php?ruta=inventario&recurso=salas
            resultado = await altaSala(sala);
        } else {
            // PUT index.php?ruta=inventario&recurso=salas
            resultado = await modificarSala({ id: salaEnEdicion.id, ...sala });
        }

        modalGestionarSala.hide();
        mostrarMensaje(resultado.mensaje, "success");
        await actualizarInventario();
    } catch (error) {
        mostrarMensajeFormulario(mensajeFormularioSala, error.message);
    } finally {
        btnConfirmarSala.disabled = false;
    }
}

//Pide confirmación y elimina una sala junto con sus equipos
async function eliminarSala(sala) {
    if (!window.confirm(`¿Eliminar la sala ${sala.tipo} ${sala.numero}? También se eliminarán todos sus equipos.`)) {
        return;
    }

    ocultarMensaje();

    try {
        // DELETE index.php?ruta=inventario&recurso=salas
        const resultado = await bajaSala(sala.id);

        mostrarMensaje(resultado.mensaje, "success");
        await actualizarInventario();
    } catch (error) {
        mostrarMensaje(error.message, "danger");
    }
}


/**
 * ALTA, MODIFICACIÓN Y BAJA DE EQUIPOS
 */

//Muestra u oculta la descripción de la incidencia según el estado elegido
function actualizarCajaIncidencia() {
    cajaIncidencia.classList.toggle("d-none", entradaEstadoEquipo.value !== "Incidencia");
}

//Completa el selector de sala con las salas actuales
function completarSelectorSalas(idSalaSeleccionada) {
    entradaSalaEquipo.replaceChildren();

    for (const sala of salasActuales) {
        const opcion = document.createElement("option");
        opcion.value = sala.id;
        opcion.textContent = `${sala.tipo} ${sala.numero}`;
        entradaSalaEquipo.appendChild(opcion);
    }

    if (idSalaSeleccionada !== null) {
        entradaSalaEquipo.value = String(idSalaSeleccionada);
    }
}

//Abre el modal para agregar un equipo. Si llega el id de una sala queda seleccionada
function abrirAltaEquipo(idSala = null) {
    ocultarMensaje();

    if (salasActuales.length === 0) {
        mostrarMensaje("Primero debe registrar una sala para poder agregar equipos.", "danger");
        return;
    }

    mensajeFormularioEquipo.className = "alert d-none";
    formularioGestionarEquipo.reset();
    equipoEnEdicion = null;
    modalTituloEquipo.textContent = "Agregar equipo";

    completarSelectorSalas(idSala);
    entradaSalaEquipo.disabled = false;
    entradaNumeroEquipo.readOnly = false;
    actualizarCajaIncidencia();

    modalGestionarEquipo.show();
}

//Abre el modal para modificar un equipo: solo se puede cambiar el estado y la incidencia
function abrirModificarEquipo(equipo) {
    ocultarMensaje();

    mensajeFormularioEquipo.className = "alert d-none";
    formularioGestionarEquipo.reset();
    equipoEnEdicion = equipo;
    modalTituloEquipo.textContent = "Modificar equipo";

    completarSelectorSalas(equipo.idSala);
    entradaSalaEquipo.disabled = true;
    entradaNumeroEquipo.value = equipo.numero;
    entradaNumeroEquipo.readOnly = true;
    entradaEstadoEquipo.value = equipo.estado;
    entradaIncidenciaEquipo.value = equipo.incidencia ?? "";
    actualizarCajaIncidencia();

    modalGestionarEquipo.show();
}

//Valida el formulario de equipo, la API lo vuelve a validar. Retorna los datos o null si hay un error
function obtenerDatosFormularioEquipo() {
    const numero = entradaNumeroEquipo.value.trim();
    const estado = entradaEstadoEquipo.value;
    const incidencia = entradaIncidenciaEquipo.value.trim();

    if (numero === "" || entradaSalaEquipo.value === "" || estado === "") {
        mostrarMensajeFormulario(mensajeFormularioEquipo, "Existen campos vacíos o inválidos");
        return null;
    }

    if (!/^[0-9]{1,10}$/.test(numero) || Number(numero) < 1) {
        mostrarMensajeFormulario(mensajeFormularioEquipo, "El número de equipo debe ser un entero positivo");
        return null;
    }

    //Si el estado es "Incidencia" la descripción es obligatoria, en los demás estados no se envía
    if (estado === "Incidencia" && incidencia === "") {
        mostrarMensajeFormulario(mensajeFormularioEquipo, "Debe describir la incidencia para marcar el equipo con ese estado");
        return null;
    }

    return {
        idSala: Number(entradaSalaEquipo.value),
        numero: Number(numero),
        estado: estado,
        incidencia: estado === "Incidencia" ? incidencia : null
    };
}

//Envía el formulario de equipo: POST si se está agregando, PUT si se está modificando
async function confirmarEquipo(evento) {
    evento.preventDefault();
    mensajeFormularioEquipo.className = "alert d-none";

    const datos = obtenerDatosFormularioEquipo();

    if (datos === null) {
        return;
    }

    try {
        btnConfirmarEquipo.disabled = true;

        let mensaje;

        if (equipoEnEdicion === null) {
            // POST index.php?ruta=inventario&recurso=equipos
            const resultado = await altaEquipo({ numero: datos.numero, idSala: datos.idSala, estado: datos.estado });
            mensaje = resultado.mensaje;

            //El alta no recibe la incidencia: si nace con ese estado se completa con una modificación
            if (datos.estado === "Incidencia") {
                try {
                    // PUT index.php?ruta=inventario&recurso=equipos
                    await modificarEquipo({ id: resultado.id, estado: datos.estado, incidencia: datos.incidencia });
                } catch (error) {
                    mensaje += ", pero no se pudo guardar la descripción de la incidencia: " + error.message;
                }
            }
        } else {
            // PUT index.php?ruta=inventario&recurso=equipos
            const resultado = await modificarEquipo({ id: equipoEnEdicion.id, estado: datos.estado, incidencia: datos.incidencia });
            mensaje = resultado.mensaje;
        }

        modalGestionarEquipo.hide();
        mostrarMensaje(mensaje, "success");
        await actualizarInventario();
    } catch (error) {
        mostrarMensajeFormulario(mensajeFormularioEquipo, error.message);
    } finally {
        btnConfirmarEquipo.disabled = false;
    }
}

//Pide confirmación y elimina un equipo
async function eliminarEquipo(equipo) {
    if (!window.confirm(`¿Eliminar el equipo N° ${equipo.numero}?`)) {
        return;
    }

    ocultarMensaje();

    try {
        // DELETE index.php?ruta=inventario&recurso=equipos
        const resultado = await bajaEquipo(equipo.id);

        mostrarMensaje(resultado.mensaje, "success");
        await actualizarInventario();
    } catch (error) {
        mostrarMensaje(error.message, "danger");
    }
}


/**
 * EVENTOS
 */

if (esAdministrador) {
    btnAltaSala.addEventListener("click", abrirAltaSala);
    btnAltaEquipo.addEventListener("click", () => abrirAltaEquipo());
    formularioGestionarSala.addEventListener("submit", confirmarSala);
    formularioGestionarEquipo.addEventListener("submit", confirmarEquipo);
    entradaEstadoEquipo.addEventListener("change", actualizarCajaIncidencia);
}

// GET index.php?ruta=inventario
actualizarInventario();
