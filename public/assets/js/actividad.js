/**
 * CONSTANTES Y VARIABLES NECESARIAS
 */

//Endpoint declarado en app/rutas/api.php
const RUTA_ACTIVIDAD = "actividad";

//Texto que se muestra para cada método HTTP
const ACCIONES = {
    POST: "Alta / registro",
    PUT: "Modificación",
    PATCH: "Cambio de estado / asignación",
    DELETE: "Baja"
};

const formularioFiltro = document.getElementById("formularioFiltro");
const filtroDesde = document.getElementById("filtroDesde");
const filtroHasta = document.getElementById("filtroHasta");
const filtroCedula = document.getElementById("filtroCedula");
const cuerpoTablaActividad = document.getElementById("cuerpoTablaActividad");
const mensajeAlerta = document.getElementById("mensajeAlerta");


/**
 * MENSAJES AL USUARIO
 */

function mostrarMensaje(mensaje, tipo) {
    mensajeAlerta.textContent = mensaje;
    mensajeAlerta.className = "alert alert-" + tipo + " shadow-sm";
}

function ocultarMensaje() {
    mensajeAlerta.className = "alert d-none";
}


/**
 * OPERACIONES CON LA API
 */

//GET - Obtiene las operaciones registradas aplicando los filtros del formulario
async function obtenerActividad() {
    const parametros = {};

    if (filtroDesde.value !== "") {
        parametros.desde = filtroDesde.value;
    }
    if (filtroHasta.value !== "") {
        parametros.hasta = filtroHasta.value;
    }
    if (filtroCedula.value.trim() !== "") {
        parametros.cedula = filtroCedula.value.trim();
    }

    return await solicitarApi(RUTA_ACTIVIDAD, { parametros: parametros });
}


/**
 * VISTA
 */

//Convierte AAAA-MM-DD HH:MM:SS en DD/MM/AAAA HH:MM
function formatearFechaHora(fechaHora) {
    const [fecha, hora] = fechaHora.split(" ");
    const partes = fecha.split("-");

    return `${partes[2]}/${partes[1]}/${partes[0]} ${hora.slice(0, 5)}`;
}

function crearCelda(texto) {
    const celda = document.createElement("td");
    celda.textContent = texto;

    return celda;
}

function mostrarActividad(actividad) {
    cuerpoTablaActividad.replaceChildren();

    if (actividad.length === 0) {
        const fila = document.createElement("tr");
        const celda = crearCelda("No hay actividad registrada en el período.");
        celda.colSpan = 5;
        celda.className = "text-muted";
        fila.appendChild(celda);
        cuerpoTablaActividad.appendChild(fila);
        return;
    }

    for (const registro of actividad) {
        const usuario = registro.nombre === null ? (registro.cedula ?? "Desconocido") : `${registro.nombre} ${registro.apellido} (${registro.cedula})`;
        const seccion = registro.recurso === null ? registro.ruta : `${registro.ruta} / ${registro.recurso}`;

        const fila = document.createElement("tr");
        fila.appendChild(crearCelda(formatearFechaHora(registro.fecha)));
        fila.appendChild(crearCelda(usuario));
        fila.appendChild(crearCelda(ACCIONES[registro.metodo] ?? registro.metodo));
        fila.appendChild(crearCelda(seccion));
        fila.appendChild(crearCelda(registro.detalle ?? "-"));
        cuerpoTablaActividad.appendChild(fila);
    }
}

async function cargarActividad() {
    try {
        ocultarMensaje();

        const resultado = await obtenerActividad();

        //Se muestran en el formulario las fechas efectivas que usó el servidor
        filtroDesde.value = resultado.desde;
        filtroHasta.value = resultado.hasta;

        mostrarActividad(resultado.actividad);
    } catch (error) {
        mostrarMensaje(error.message, "danger");
    }
}


/**
 * EVENTOS
 */

formularioFiltro.addEventListener("submit", (evento) => {
    evento.preventDefault();
    cargarActividad();
});

cargarActividad();
