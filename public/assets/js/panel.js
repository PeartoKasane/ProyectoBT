/**
 * PANELES POR ROL (docente, técnico y dirección)
 *
 * Script compartido por docente.html, tecnico.html y direccion.html. Cada página indica su panel con data-panel en el body.
 *  - docente y técnico: muestran los datos del usuario logueado (GET ?ruta=sesion).
 *  - dirección: muestra el resumen de personal por rol (GET ?ruta=reportes&recurso=personal).
 *
 * Requiere haber cargado antes api.js y sesion.js.
 */

/**
 * CONSTANTES Y VARIABLES NECESARIAS
 */

//Panel que corresponde a esta página: "docente", "tecnico" o "direccion"
const PANEL = document.body.dataset.panel;

//Constantes para completar los datos del usuario (docente y técnico)
const datoCedula = document.getElementById("datoCedula");
const datoNombre = document.getElementById("datoNombre");
const datoApellido = document.getElementById("datoApellido");
const datoEmail = document.getElementById("datoEmail");

//Constante para trabajar con la tabla de personal (dirección)
const cuerpoTablaDireccion = document.getElementById("cuerpoTablaDireccion");

//Constante para mostrar mensajes de resultado o de error
const mensajeAlerta = document.getElementById("mensajeAlerta");


/**
 * MENSAJES AL USUARIO
 */

//Muestra un mensaje dentro de la página. El tipo es el sufijo de la clase de Bootstrap: "success" o "danger"
function mostrarMensaje(mensaje, tipo) {
    mensajeAlerta.textContent = mensaje;
    mensajeAlerta.className = "alert alert-" + tipo + " shadow-sm";
}


/**
 * GET - Datos del usuario logueado (docente y técnico).
 */
async function cargarMisDatos() {
    try {
        const usuario = await solicitarApi("sesion");

        datoCedula.textContent = usuario.cedula;
        datoNombre.textContent = usuario.nombre;
        datoApellido.textContent = usuario.apellido;
        //Igual que el panel original, si no tiene correo se informa que no está registrado
        datoEmail.textContent = usuario.email ? usuario.email : "Sin registrar";
    } catch (error) {
        mostrarMensaje(error.message, "danger");
    }
}


/**
 * GET - Resumen institucional de personal por rol (dirección).
 */
async function cargarPersonal() {
    try {
        const personal = await solicitarApi("reportes", { parametros: { recurso: "personal" } });

        cuerpoTablaDireccion.replaceChildren();

        for (const usuario of personal) {
            const fila = document.createElement("tr");

            for (const valor of [usuario.cedula, usuario.nombre, usuario.apellido, usuario.roles]) {
                const celda = document.createElement("td");
                celda.textContent = valor;
                fila.appendChild(celda);
            }

            cuerpoTablaDireccion.appendChild(fila);
        }
    } catch (error) {
        mostrarMensaje(error.message, "danger");
    }
}


/**
 * INICIO DE LA PÁGINA
 */

if (PANEL === "direccion") {
    cargarPersonal();
} else {
    cargarMisDatos();
}
