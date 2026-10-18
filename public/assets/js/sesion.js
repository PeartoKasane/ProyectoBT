/**
 * SESIÓN (script compartido por todas las páginas protegidas)
 *
 * Centraliza el cierre de sesión: ninguna otra página define su propio cerrarSesion().
 * Requiere haber cargado antes api.js.
 *
 * Uso en el HTML:
 *  - Un botón con id="btnCerrarSesion" queda conectado automáticamente.
 *  - Un elemento con data-roles="administrador direccion" solo se muestra a esos roles
 *    (es una ayuda visual: los permisos reales los verifica el servidor en cada petición).
 *  - Un elemento con id="mensajeAlerta" recibe los errores del cierre de sesión.
 */

//POST - Cierra la sesión en el servidor, borra los datos locales y vuelve al inicio de sesión
async function cerrarSesion() {
    const btnCerrarSesion = document.getElementById("btnCerrarSesion");
    const mensajeAlerta = document.getElementById("mensajeAlerta");

    try {
        if (btnCerrarSesion !== null) {
            btnCerrarSesion.disabled = true;
        }

        await solicitarApi("logout", { metodo: "POST" });

        //Se borra antes de salir de la página para que pagehide no envíe un aviso de salida de una sesión ya cerrada
        borrarSesionLocal();
        //replace() abre la siguiente página borrando el historial de la previa
        window.location.replace(PAGINA_LOGIN);
    } catch (error) {
        if (mensajeAlerta !== null) {
            mensajeAlerta.textContent = error.message;
            mensajeAlerta.className = "alert alert-danger shadow-sm";
        } else {
            window.alert(error.message);
        }
    } finally {
        if (btnCerrarSesion !== null) {
            btnCerrarSesion.disabled = false;
        }
    }
}

//Si el navegador no tiene datos de sesión se vuelve al login sin esperar a que responda el servidor
function exigirSesionLocal() {
    if (sessionStorage.getItem(CLAVE_CSRF) === null) {
        window.location.replace(PAGINA_LOGIN);
    }
}

//Oculta los elementos que no corresponden al rol de quien inició sesión
function aplicarRolEnPagina() {
    const rol = sessionStorage.getItem(CLAVE_ROL);

    for (const elemento of document.querySelectorAll("[data-roles]")) {
        const rolesPermitidos = elemento.dataset.roles.split(" ");

        if (!rolesPermitidos.includes(rol)) {
            elemento.classList.add("d-none");
        }
    }
}

//Si se abandona la página sin cerrar sesión, se avisa al servidor para que libere la sesión (RF37)
//pagehide cubre cerrar la pestaña o el navegador, recargar y cambiar de página (beforeunload/unload no son confiables en celulares)
function avisarSalida() {
    notificarSalida();
}

//Si el navegador restaura la página desde su caché (botón "atrás"), el aviso anterior ya liberó la sesión:
//cualquier petición al servidor la reactiva, o vuelve al login si ya había vencido
function restaurarSesion(evento) {
    if (evento.persisted) {
        solicitarApi("sesion").catch(() => {});
    }
}

exigirSesionLocal();
aplicarRolEnPagina();

window.addEventListener("pagehide", avisarSalida);
window.addEventListener("pageshow", restaurarSesion);

const botonCerrarSesion = document.getElementById("btnCerrarSesion");

if (botonCerrarSesion !== null) {
    botonCerrarSesion.addEventListener("click", cerrarSesion);
}
