/**
 * REPORTES Y ESTADÍSTICAS (RF39-RF43)
 *
 * Consulta GET ?ruta=reportes&desde=AAAA-MM-DD&hasta=AAAA-MM-DD y dibuja los resultados.
 * Requiere haber cargado antes api.js y sesion.js.
 */

/**
 * CONSTANTES Y VARIABLES NECESARIAS
 */

//Constantes para el filtro de fechas
const formFiltro = document.getElementById("formFiltro");
const inputDesde = document.getElementById("desde");
const inputHasta = document.getElementById("hasta");

//Constante para mostrar mensajes de resultado o de error
const mensajeAlerta = document.getElementById("mensajeAlerta");

//Contenedores de los gráficos de barras
const graficoEstados = document.getElementById("graficoEstados");
const graficoPrioridades = document.getElementById("graficoPrioridades");
const graficoIncidencias = document.getElementById("graficoIncidencias");
const graficoSalas = document.getElementById("graficoSalas");
const graficoPrestados = document.getElementById("graficoPrestados");


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
 * DIBUJO DE RESULTADOS
 */

//Escribe un número en el elemento indicado
function escribirTexto(id, valor) {
    document.getElementById(id).textContent = valor;
}

/**
 * Dibuja barras de progreso proporcionales al mayor valor (mínimo 1, para no dividir por cero).
 * @param {HTMLElement} contenedor Elemento donde se dibujan las barras.
 * @param {Array<{etiqueta: string, total: number}>} filas Datos a dibujar.
 * @param {string} claseColor Clase de Bootstrap del color de la barra.
 * @param {string} textoVacio Mensaje si no hay datos (si es "" no se muestra ninguno).
 */
function dibujarBarras(contenedor, filas, claseColor, textoVacio) {
    contenedor.replaceChildren();

    const maximo = Math.max(1, ...filas.map(fila => fila.total));

    for (const fila of filas) {
        const bloque = document.createElement("div");
        bloque.className = "mb-2";

        const encabezado = document.createElement("div");
        encabezado.className = "d-flex justify-content-between small";

        const etiqueta = document.createElement("span");
        etiqueta.textContent = fila.etiqueta;
        const total = document.createElement("span");
        total.textContent = fila.total;
        encabezado.append(etiqueta, total);

        const progreso = document.createElement("div");
        progreso.className = "progress";
        progreso.setAttribute("role", "progressbar");
        progreso.style.height = "10px";

        const barra = document.createElement("div");
        barra.className = "progress-bar " + claseColor;
        barra.style.width = (fila.total / maximo) * 100 + "%";
        progreso.appendChild(barra);

        bloque.append(encabezado, progreso);
        contenedor.appendChild(bloque);
    }

    if (filas.length === 0 && textoVacio !== "") {
        const vacio = document.createElement("p");
        vacio.className = "text-muted small m-0";
        vacio.textContent = textoVacio;
        contenedor.appendChild(vacio);
    }
}

//Texto de un equipo: "Laboratorio 1 - Equipo N° 3"
function nombreEquipo(fila) {
    return `${fila.tipoSala} ${fila.numeroSala} - Equipo N° ${fila.numeroEquipo}`;
}

//Completa toda la página con la respuesta de la API
function dibujarReportes(datos) {
    inputDesde.value = datos.desde;
    inputHasta.value = datos.hasta;

    //RF42: resumen general
    escribirTexto("resUsuariosActivos", datos.resumenGeneral.usuariosActivos);
    escribirTexto("resTotalEquipos", datos.resumenGeneral.totalEquipos);
    escribirTexto("resTicketsPendientes", datos.resumenGeneral.ticketsPendientes);
    escribirTexto("resSolicitudesPendientes", datos.resumenGeneral.solicitudesPendientes);
    escribirTexto("resPrestamosActivos", datos.resumenGeneral.prestamosActivos);

    //RF39: tickets por estado y por prioridad
    dibujarBarras(graficoEstados, datos.ticketsPorEstado.map(f => ({ etiqueta: f.estado, total: f.total })), "bg-secondary", "");
    dibujarBarras(graficoPrioridades, datos.ticketsPorPrioridad.map(f => ({ etiqueta: f.nombre, total: f.total })), "bg-danger", "");

    //RF40: uso de equipos y salas
    dibujarBarras(graficoIncidencias, datos.equiposConMasIncidencias.map(f => ({ etiqueta: nombreEquipo(f), total: f.totalIncidencias })), "bg-warning", "Sin incidencias en el período seleccionado.");
    dibujarBarras(graficoSalas, datos.salasConMasTickets.map(f => ({ etiqueta: `${f.tipoSala} ${f.numeroSala}`, total: f.totalTickets })), "bg-info", "Sin tickets en el período seleccionado.");

    //RF41: préstamos y devoluciones
    const prestamos = datos.metricasPrestamos;
    escribirTexto("prestTotal", prestamos.totalPrestamos);
    escribirTexto("prestActivos", prestamos.activos);
    escribirTexto("prestFinalizados", prestamos.finalizados);
    escribirTexto("prestPromedio", prestamos.promedioHoras !== null ? prestamos.promedioHoras + " hs" : "-");

    dibujarBarras(graficoPrestados, datos.equiposMasPrestados.map(f => ({ etiqueta: nombreEquipo(f), total: f.totalPrestamos })), "bg-primary", "Sin préstamos en el período seleccionado.");
}


/**
 * GET - Consulta los reportes del rango de fechas indicado. Sin fechas, el servidor usa los últimos 30 días.
 */
async function cargarReportes() {
    const parametros = {};

    if (inputDesde.value !== "") {
        parametros.desde = inputDesde.value;
    }

    if (inputHasta.value !== "") {
        parametros.hasta = inputHasta.value;
    }

    try {
        const datos = await solicitarApi("reportes", { parametros });

        ocultarMensaje();
        dibujarReportes(datos);
    } catch (error) {
        mostrarMensaje(error.message, "danger");
    }
}


/**
 * EVENTOS E INICIO DE LA PÁGINA
 */

formFiltro.addEventListener("submit", function (evento) {
    evento.preventDefault();
    cargarReportes();
});

cargarReportes();
