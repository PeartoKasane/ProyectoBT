/*
  este archivo es la vista de usuarios.
  aca va la logica del JS y no me gusta romperlo.
  pero por ahora sirve para que el sistema funcione.
*/

class UsuarioVista {
    static obtenerDatosFormulario() {
        return {
            cedula: document.getElementById('cedulaUsuario')?.value.trim() || '',
            nombre: document.getElementById('nombreUsuario')?.value.trim() || '',
            rol: document.getElementById('rolUsuario')?.value || ''
        };
    }

    static obtenerDatosEliminar() {
        return {
            cedula: document.getElementById('cedulaEliminarUsuario')?.value.trim() || ''
        };
    }

    static obtenerDatosCambioRol() {
        return {
            cedula: document.getElementById('cedulaCambioRol')?.value.trim() || '',
            nuevoRol: document.getElementById('nuevoRolUsuario')?.value || ''
        };
    }

    static mostrarMensaje(mensaje, contenedorId = 'mensajeUsuario') {
        const contenedor = document.getElementById(contenedorId);

        if (!contenedor) {
            alert(mensaje);
            return;
        }

        contenedor.textContent = mensaje;
        contenedor.classList.remove('d-none');
    }

    static limpiarFormularioUsuario() {
        const formulario = document.getElementById('form-agregar-usuario');

        if (formulario) {
            formulario.reset();
        }
    }

    static limpiarFormularioEliminar() {
        const formulario = document.getElementById('form-eliminar-usuario');

        if (formulario) {
            formulario.reset();
        }
    }

    static limpiarFormularioCambioRol() {
        const formulario = document.getElementById('form-cambiar-rol');

        if (formulario) {
            formulario.reset();
        }
    }

    static renderizarRolesUsuario(roles) {
        const contenedor = document.getElementById('selectorVentanaUsuario');

        if (!contenedor) {
            return;
        }

        const listaRoles = PermisoServicio.normalizarRoles(roles);

        if (listaRoles.length <= 1) {
            contenedor.innerHTML = '';
            return;
        }

        contenedor.innerHTML = listaRoles.map(rol => `
            <button type="button" class="btn btn-sm ${rol === 'Técnico' ? 'btn-primary' : 'btn-outline-primary'} selectorVentanaBoton" data-ventana="${rol}">
                ${rol}
            </button>
        `).join('');
    }
}

globalThis.UsuarioVista = UsuarioVista;
