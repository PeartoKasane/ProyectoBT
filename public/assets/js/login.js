/**
 * CONSTANTES Y VARIABLES NECESARIAS
 */

//Panel al que se redirige según el rol que informa la API
const DESTINOS = {
    administrador: "./administrador.html",
    direccion: "./direccion.html",
    docente: "./docente.html",
    tecnico: "./tecnico.html"
};

const formularioLogin = document.getElementById("formularioLogin");
const mensajeAlerta = document.getElementById("mensajeAlerta");

const entradaCedula = document.getElementById("cedula");
const entradaClave = document.getElementById("clave");


/**
 * MENSAJES AL USUARIO
 */

//Muestra el mensaje de error dentro de la página
function mostrarError(mensaje) {
    mensajeAlerta.textContent = mensaje;
    mensajeAlerta.classList.remove("d-none");
}

//Oculta el mensaje de error anterior
function ocultarError() {
    mensajeAlerta.classList.add("d-none");
}


/**
 * OPERACIONES CON LA API
 */

//POST - Envía las credenciales y retorna los datos de la sesión si son correctas
async function loguear(cedula, clave) {
    return await solicitarApi("login", { metodo: "POST", cuerpo: { cedula: cedula, clave: clave } });
}

//POST - Envía las credenciales, guarda los datos de sesión y deriva al panel del rol
async function gestionarLogin(eventoFormulario) {
    eventoFormulario.preventDefault();
    ocultarError();

    try {
        const sesion = await loguear(entradaCedula.value.trim(), entradaClave.value);

        if (!sesion.csrfToken) {
            throw new Error("La API no devolvió el token CSRF.");
        }

        const destino = DESTINOS[sesion.rol];

        if (destino === undefined) {
            throw new Error("La API informó un rol desconocido.");
        }

        //Guardamos el token, la cédula y el rol en la sesión del navegador (se borran al cerrar la pestaña)
        guardarSesionLocal(sesion);

        //replace() abre la siguiente página borrando el historial de la previa
        window.location.replace(destino);
    } catch (error) {
        mostrarError(error.message);
    }
}


/**
 * EVENTOS
 */

formularioLogin.addEventListener("submit", gestionarLogin);
