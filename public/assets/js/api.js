/**
 * COMUNICACIÓN CON LA API
 *
 * Único lugar del frontend donde se utiliza fetch. Todas las páginas incluyen este script
 * antes del suyo y piden los datos con solicitarApi().
 *
 * FORMATO DE LA COMUNICACIÓN: JSON en ambos sentidos.
 *  - Accept: application/json        -> se declara que la respuesta esperada es JSON.
 *  - Content-Type: application/json  -> se declara que el cuerpo enviado (si hay) es JSON.
 *  - Contrato de toda respuesta: {"status": <código HTTP>, "message": "...", "data": ... | null}
 */

//El HTML se carga desde public/paginas/, el único punto de entrada del backend es public/index.php
const URL_API = "../index.php";
const PAGINA_LOGIN = "./login.html";

//Claves donde se guardan los datos de la sesión en el navegador (se borran al cerrar la pestaña)
const CLAVE_CSRF = "csrfToken";
const CLAVE_CEDULA = "cedula";
const CLAVE_ROL = "rol";


/**
 * EXCEPCIÓN PROPIA
 *
 * Toda falla de una petición (red caída, respuesta que no es JSON, error informado por el servidor)
 * se lanza como ErrorApi, de modo que quien llama solo necesita un try/catch.
 */
class ErrorApi extends Error {
    /**
     * @param {string} mensaje Texto para mostrar al usuario.
     * @param {number} estado Código HTTP de la respuesta, 0 si la petición no llegó al servidor.
     * @param {Error} [causa] Error original, si lo hubo.
     */
    constructor(mensaje, estado = 0, causa = undefined) {
        super(mensaje, { cause: causa });
        this.name = "ErrorApi";
        this.estado = estado;
    }
}


/**
 * DATOS DE LA SESIÓN EN EL NAVEGADOR
 */

function guardarSesionLocal(sesion) {
    sessionStorage.setItem(CLAVE_CSRF, sesion.csrfToken);
    sessionStorage.setItem(CLAVE_CEDULA, sesion.cedula);
    sessionStorage.setItem(CLAVE_ROL, sesion.rol);
}

//Borra los datos de sesión guardados en el navegador (cédula, rol y token CSRF)
function borrarSesionLocal() {
    sessionStorage.removeItem(CLAVE_CSRF);
    sessionStorage.removeItem(CLAVE_CEDULA);
    sessionStorage.removeItem(CLAVE_ROL);
}


/**
 * PETICIÓN A LA API
 *
 * @param {string} ruta Nombre del endpoint declarado en app/rutas/api.php (ej: "usuarios").
 * @param {object} [opciones]
 * @param {string} [opciones.metodo="GET"] Método HTTP.
 * @param {object} [opciones.parametros] Pares clave/valor que viajan en la URL (?cedula=...).
 * @param {object} [opciones.cuerpo] Objeto que se envía como JSON.
 * @returns {Promise<any>} El contenido de "data". En las operaciones que modifican datos (POST, PUT, PATCH, DELETE)
 *                         se le agrega además "mensaje" con el "message" del servidor, así la pantalla puede mostrarlo.
 * @throws {ErrorApi} Ante cualquier falla de comunicación o respuesta con error.
 */
async function solicitarApi(ruta, { metodo = "GET", parametros = {}, cuerpo = undefined } = {}) {
    const consulta = new URLSearchParams({ ruta, ...parametros });

    const cabeceras = {
        "Accept": "application/json"
    };

    const configuracion = { method: metodo, headers: cabeceras };

    if (cuerpo !== undefined) {
        cabeceras["Content-Type"] = "application/json";
        configuracion.body = JSON.stringify(cuerpo);
    }

    //El token CSRF se envía en toda petición que modifica datos
    if (metodo !== "GET") {
        cabeceras["X-CSRF-Token"] = sessionStorage.getItem(CLAVE_CSRF) ?? "";
    }

    let respuesta;
    let texto;

    try {
        respuesta = await fetch(`${URL_API}?${consulta}`, configuracion);
        texto = await respuesta.text();
    } catch (error) {
        throw new ErrorApi("No se pudo establecer comunicación con el servidor.", 0, error);
    }

    let json;

    try {
        json = JSON.parse(texto);
    } catch (error) {
        throw new ErrorApi(`HTTP ${respuesta.status}: la API no devolvió JSON.`, respuesta.status, error);
    }

    //401 fuera del inicio de sesión = sesión no iniciada o vencida, se vuelve al login
    if (respuesta.status === 401 && ruta !== "login") {
        borrarSesionLocal();
        window.location.replace(PAGINA_LOGIN);
    }

    if (!respuesta.ok) {
        throw new ErrorApi(json.message ?? "La solicitud no se pudo completar.", respuesta.status);
    }

    //data proviene del contrato de RespuestaJson: {status, message, data}
    if (metodo !== "GET" && (json.data === null || (typeof json.data === "object" && !Array.isArray(json.data)))) {
        return { ...json.data, mensaje: json.message };
    }

    return json.data;
}


/**
 * AVISO DE SALIDA
 *
 * Se envía cuando el usuario abandona la página (cierra la pestaña o el navegador, recarga, cambia de página)
 * sin haber usado el botón "Cerrar sesión". keepalive permite que el navegador termine la petición aunque la página
 * ya se esté cerrando; no se usa navigator.sendBeacon() porque no deja enviar la cabecera X-CSRF-Token.
 * No espera respuesta: el servidor decide si fue una salida real o un cambio de página (ver Sesion::reanudar()).
 */
function notificarSalida() {
    const token = sessionStorage.getItem(CLAVE_CSRF);

    //Sin token no hay sesión iniciada, o ya se cerró con el botón
    if (token === null) {
        return;
    }

    try {
        fetch(`${URL_API}?${new URLSearchParams({ ruta: "logout", recurso: "salida" })}`, {
            method: "POST",
            keepalive: true,
            headers: { "Accept": "application/json", "X-CSRF-Token": token }
        }).catch(() => {});
    } catch (error) {
        //Al abandonar la página no hay nada que mostrarle al usuario
    }
}
