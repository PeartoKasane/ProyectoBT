/**
 * CONSTANTES Y VARIABLES NECESARIAS
 */

//Modal de Bootstrap, se maneja desde JavaScript para poder abrirlo y cerrarlo según la operación
const elementoModal = document.getElementById("modalGestionarUsuario");
const modalGestionarUsuario = new bootstrap.Modal(elementoModal);
const modalTitulo = document.getElementById("modalGestionarUsuarioLabel");

//Constantes para los botones de la página
const btnAltaUsuario = document.getElementById("btnAltaUsuario");

//Constante para trabajar con la tabla de usuarios
const cuerpoTablaUsuarios = document.getElementById("cuerpoTablaUsuarios");

//Constante para mostrar mensajes de resultado o de error
const mensajeAlerta = document.getElementById("mensajeAlerta");

//Constante para mostrar los errores del formulario dentro del propio modal, donde el usuario puede verlos
const mensajeFormulario = document.getElementById("mensajeFormulario");

//Constante para manipular el formulario
const formularioGestionarUsuario = document.getElementById("formularioGestionarUsuario");

//Campos del formulario
const entradaCedula = document.getElementById("cedula");
const entradaNombre = document.getElementById("nombre");
const entradaApellido = document.getElementById("apellido");
const entradaEmail = document.getElementById("email");
const entradaClave = document.getElementById("clave");
const entradaConfirmarClave = document.getElementById("confirmarClave");
const entradaRol = document.getElementById("rol");

//Cédula de quien tiene la sesión iniciada, se usa para no ofrecerle deshabilitar su propia cuenta
const cedulaPropia = sessionStorage.getItem(CLAVE_CEDULA);

//Auxiliar para saber si se está agregando o modificando un usuario
let usuarioEnEdicion = false;


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
 * GESTIÓN DEL ESTADO DEL FORMULARIO / MODAL
 */

//Limpia todos los campos y configuraciones seleccionadas del formulario
function limpiarEstadoGestionarUsuario() {
    mensajeFormulario.className = "alert d-none";
    usuarioEnEdicion = false;
    entradaCedula.readOnly = false;
    formularioGestionarUsuario.reset();
}

//Abre el modal para dar de alta un nuevo usuario
function abrirAltaUsuario() {
    limpiarEstadoGestionarUsuario();
    modalTitulo.textContent = "Alta de empleado";

    modalGestionarUsuario.show();
}

//Cierra el modal
function cerrarGestionarUsuario() {
    modalGestionarUsuario.hide();
    limpiarEstadoGestionarUsuario();
}


/**
 * OBTENCIÓN DE DATOS DEL FORMULARIO
 */

//Captura los datos ingresados en el formulario
function obtenerDatosFormularioUsuario() {
    const usuario = {
        cedula: entradaCedula.value.trim(),
        nombre: entradaNombre.value.trim(),
        apellido: entradaApellido.value.trim(),
        email: entradaEmail.value.trim(),
        clave: entradaClave.value,
        confirmarClave: entradaConfirmarClave.value,
        rol: entradaRol.value
    };

    return usuario;
}


/**
 * OPERACIONES CON LA API
 */

/**
 * GET - Obtiene todos los usuarios.
 */
async function obtenerUsuarios() {
    return await solicitarApi("usuarios");
}

/**
 * GET - Obtiene un usuario específico mediante su cédula.
 */
async function obtenerUsuario(cedula) {
    return await solicitarApi("usuarios", { parametros: { cedula: cedula } });
}

/**
 * POST - Envía un nuevo usuario a la API.
 */
async function altaUsuario(usuario) {
    return await solicitarApi("usuarios", { metodo: "POST", cuerpo: usuario });
}

/**
 * PUT - Modifica los datos de un usuario existente.
 */
async function modificarUsuario(usuario) {
    return await solicitarApi("usuarios", { metodo: "PUT", cuerpo: usuario });
}

/**
 * PATCH - RF36: habilita o deshabilita una cuenta sin eliminarla.
 */
async function cambiarEstadoUsuario(cedula, activo) {
    try {
        const resultado = await solicitarApi("usuarios", { metodo: "PATCH", cuerpo: { cedula: cedula, activo: activo } });

        //Una vez modificado, vuelve a consultar los datos al servidor
        await actualizarTabla();
        mostrarMensaje(resultado.mensaje, "success");
    } catch (error) {
        mostrarMensaje(error.message, "danger");
    }
}

/**
 * DELETE - Elimina un usuario según su cédula.
 */
async function eliminarUsuario(cedula) {
    if (!window.confirm(`¿Está seguro de eliminar al usuario ${cedula}?`)) {
        return;
    }

    try {
        const resultado = await solicitarApi("usuarios", { metodo: "DELETE", cuerpo: { cedula: cedula } });

        //Una vez eliminado, vuelve a consultar los datos al servidor
        await actualizarTabla();
        mostrarMensaje(resultado.mensaje, "success");
    } catch (error) {
        mostrarMensaje(error.message, "danger");
    }
}

/**
 * GESTIÓN DE FILAS DE LA TABLA
 */

//Crea un botón de acción de la fila con las clases de Bootstrap indicadas
function crearBotonOperacion(texto, clases, accion) {
    const boton = document.createElement("button");
    boton.type = "button";
    boton.textContent = texto;
    boton.className = "btn btn-sm fw-semibold " + clases;
    boton.addEventListener("click", accion);

    return boton;
}

//Crea una fila de la tabla a partir de un usuario
//Se usa textContent y no innerHTML para que un dato con etiquetas HTML se muestre como texto y no se ejecute
function agregarFilaUsuario(usuario) {
    const fila = document.createElement("tr");

    const campoCedula = document.createElement("td");
    campoCedula.textContent = usuario.cedula;
    const campoNombre = document.createElement("td");
    campoNombre.textContent = usuario.nombre;
    const campoApellido = document.createElement("td");
    campoApellido.textContent = usuario.apellido;
    const campoRol = document.createElement("td");
    campoRol.textContent = usuario.rol === "" ? "Sin rol" : usuario.rol;

    const campoEstado = document.createElement("td");
    const insigniaEstado = document.createElement("span");
    insigniaEstado.className = usuario.activo ? "badge bg-success" : "badge bg-secondary";
    insigniaEstado.textContent = usuario.activo ? "Activo" : "Deshabilitado";
    campoEstado.appendChild(insigniaEstado);

    const campoOperaciones = document.createElement("td");
    const cajaOperaciones = document.createElement("div");
    cajaOperaciones.className = "d-flex justify-content-center gap-2";

    //Un administrador no puede deshabilitar su propia cuenta
    if (usuario.cedula !== cedulaPropia) {
        const textoEstado = usuario.activo ? "Deshabilitar" : "Habilitar";
        cajaOperaciones.appendChild(
            crearBotonOperacion(textoEstado, "btn-outline-secondary", () => cambiarEstadoUsuario(usuario.cedula, !usuario.activo))
        );
    }

    cajaOperaciones.appendChild(
        crearBotonOperacion("Modificar", "btn-outline-primary", () => abrirModificarUsuario(usuario.cedula))
    );
    //Tampoco puede eliminar su propia cuenta
    if (usuario.cedula !== cedulaPropia) {
        cajaOperaciones.appendChild(
            crearBotonOperacion("Eliminar", "btn-outline-danger", () => eliminarUsuario(usuario.cedula))
        );
    }

    campoOperaciones.appendChild(cajaOperaciones);

    fila.appendChild(campoCedula);
    fila.appendChild(campoNombre);
    fila.appendChild(campoApellido);
    fila.appendChild(campoRol);
    fila.appendChild(campoEstado);
    fila.appendChild(campoOperaciones);

    cuerpoTablaUsuarios.appendChild(fila);
}

/**
 * Solicita los usuarios nuevamente a la API y genera las filas.
 */
async function actualizarTabla() {
    //Elimina todas las filas actuales
    cuerpoTablaUsuarios.replaceChildren();

    try {
        // GET index.php?ruta=usuarios
        const usuarios = await obtenerUsuarios();

        //Genera una fila por usuario
        for (const usuario of usuarios) {
            agregarFilaUsuario(usuario);
        }
    } catch (error) {
        mostrarMensaje("No se pudieron cargar los usuarios: " + error.message, "danger");
    }
}

//Abre el modal con los datos actuales del usuario para poder modificarlos
async function abrirModificarUsuario(cedula) {
    try {
        // GET index.php?ruta=usuarios&cedula={cedula}
        const usuarioAModificar = await obtenerUsuario(cedula);

        limpiarEstadoGestionarUsuario();
        usuarioEnEdicion = true;

        //Se cargan en el formulario los datos recibidos desde la API.
        entradaCedula.value = usuarioAModificar.cedula;
        entradaNombre.value = usuarioAModificar.nombre;
        entradaApellido.value = usuarioAModificar.apellido;
        entradaEmail.value = usuarioAModificar.email ?? "";
        entradaRol.value = usuarioAModificar.rol;
        entradaCedula.readOnly = true;
        modalTitulo.textContent = "Modificar empleado";

        modalGestionarUsuario.show();
    } catch (error) {
        mostrarMensaje(error.message, "danger");
    }
}

/**
 * Decide si debe realizarse un POST o un PUT dependiendo del estado del formulario.
 */
async function gestionarUsuario(eventoFormulario) {
    eventoFormulario.preventDefault();
    ocultarMensaje();
    mensajeFormulario.className = "alert d-none";

    try {
        const usuario = obtenerDatosFormularioUsuario();

        if (usuario.clave !== usuario.confirmarClave) {
            throw new Error("Las contraseñas ingresadas no coinciden");
        }

        let resultado;

        if (!usuarioEnEdicion) { // POST index.php?ruta=usuarios
            resultado = await altaUsuario(usuario);
        } else { // PUT index.php?ruta=usuarios
            resultado = await modificarUsuario(usuario);
        }

        cerrarGestionarUsuario();
        await actualizarTabla();
        mostrarMensaje(resultado.mensaje, "success");
    } catch (error) {
        //El modal sigue abierto para que se pueda corregir el dato, por eso el error se muestra dentro de él
        mensajeFormulario.textContent = error.message;
        mensajeFormulario.className = "alert alert-danger";
    }
}


/**
 * EVENTOS
 */

//Alta o modificación de usuarios
formularioGestionarUsuario.addEventListener("submit", gestionarUsuario);

//Abrir modal de alta
btnAltaUsuario.addEventListener("click", abrirAltaUsuario);

//Al cerrar el modal (con la X, Escape o haciendo clic afuera) se limpia el estado del formulario
elementoModal.addEventListener("hidden.bs.modal", limpiarEstadoGestionarUsuario);

// GET index.php?ruta=usuarios
actualizarTabla();
