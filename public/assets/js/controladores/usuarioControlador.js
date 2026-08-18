/*
  este archivo es la controlador de usuarios.
  aca va la logica del JS y no me gusta romperlo.
  pero por ahora sirve para que el sistema funcione.
*/

document.addEventListener('DOMContentLoaded', () => {
    const formularioAgregar = document.getElementById('form-agregar-usuario');
    const formularioEliminar = document.getElementById('form-eliminar-usuario');
    const formularioCambioRol = document.getElementById('form-cambiar-rol');
    const selectorVentanaUsuario = document.getElementById('selectorVentanaUsuario');

    const usuarioActual = PermisoServicio.obtenerSesionUsuario();

    const mostrarVentanaSegunRol = (roles) => {
        const rolesNormalizados = PermisoServicio.normalizarRoles(roles || []);
        const ventanasDisponibles = PermisoServicio.obtenerVentanasDisponibles(rolesNormalizados);

        if (selectorVentanaUsuario) {
            selectorVentanaUsuario.innerHTML = '';

            if (ventanasDisponibles.length > 1) {
                selectorVentanaUsuario.innerHTML = ventanasDisponibles.map(ventana => `
                    <button type="button" class="btn btn-sm selectorVentanaBoton ${ventana === 'Técnico' ? 'btn-primary' : 'btn-outline-primary'}" data-ventana="${ventana}">
                        ${ventana}
                    </button>
                `).join('');
            }
        }
    };

    if (formularioAgregar) {
        formularioAgregar.addEventListener('submit', function (event) {
            event.preventDefault();

            const datos = UsuarioVista.obtenerDatosFormulario();

            if (!datos.cedula || !datos.nombre || !datos.rol) {
                UsuarioVista.mostrarMensaje('Completa la cédula, el nombre y el rol del usuario.', 'mensajeUsuario');
                return;
            }

            if (!PermisoServicio.ROLES_VALIDOS.includes(datos.rol)) {
                UsuarioVista.mostrarMensaje('El rol ingresado no es válido.', 'mensajeUsuario');
                return;
            }

            // Más adelante se conectará con PHP para guardar el usuario.
            UsuarioVista.mostrarMensaje('La acción de agregar usuario está preparada para conectarse con PHP.', 'mensajeUsuario');
            UsuarioVista.limpiarFormularioUsuario();
        });
    }

    if (formularioEliminar) {
        formularioEliminar.addEventListener('submit', function (event) {
            event.preventDefault();

            const datos = UsuarioVista.obtenerDatosEliminar();

            if (!datos.cedula) {
                UsuarioVista.mostrarMensaje('Debes indicar la cédula del usuario.', 'mensajeEliminarUsuario');
                return;
            }

            const confirmado = window.confirm(`¿Deseas eliminar al usuario con cédula ${datos.cedula}?`);

            if (!confirmado) {
                return;
            }

            // Más adelante esta función se conectará con PHP para eliminar el usuario.
            UsuarioVista.mostrarMensaje('La eliminación de usuario está preparada para conectarse con PHP.', 'mensajeEliminarUsuario');
            UsuarioVista.limpiarFormularioEliminar();
        });
    }

    if (formularioCambioRol) {
        formularioCambioRol.addEventListener('submit', function (event) {
            event.preventDefault();

            const datos = UsuarioVista.obtenerDatosCambioRol();

            if (!datos.cedula || !datos.nuevoRol) {
                UsuarioVista.mostrarMensaje('Completa la cédula y el nuevo rol.', 'mensajeCambioRol');
                return;
            }

            if (!PermisoServicio.ROLES_VALIDOS.includes(datos.nuevoRol)) {
                UsuarioVista.mostrarMensaje('El nuevo rol no es válido.', 'mensajeCambioRol');
                return;
            }

            // Más adelante se conectará con PHP para cambiar el rol del usuario.
            UsuarioVista.mostrarMensaje('El cambio de rol está preparado para conectarse con PHP.', 'mensajeCambioRol');
            UsuarioVista.limpiarFormularioCambioRol();
        });
    }

    if (selectorVentanaUsuario) {
        selectorVentanaUsuario.addEventListener('click', function (event) {
            const boton = event.target.closest('.selectorVentanaBoton');

            if (!boton) {
                return;
            }

            const ventanaSeleccionada = boton.dataset.ventana;
            const destino = PermisoServicio.obtenerRutaVentana(ventanaSeleccionada);

            if (destino) {
                window.location.href = destino;
            }
        });
    }

    if (usuarioActual && usuarioActual.roles) {
        mostrarVentanaSegunRol(usuarioActual.roles);
        UsuarioVista.renderizarRolesUsuario(usuarioActual.roles);
    }
});
