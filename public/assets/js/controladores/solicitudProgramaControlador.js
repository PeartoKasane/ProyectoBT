/*
  este archivo es la controlador de solicitudes.
  aca va la logica del JS y no me gusta romperlo.
  pero por ahora sirve para que el sistema funcione.
*/

document.addEventListener("DOMContentLoaded", () => {
    const formularioSolicitud = document.getElementById("form-solicitud-programa");
    const tablaSolicitudes = document.getElementById("registro_solicitudes");

    if (formularioSolicitud && !PermisoServicio.validarAccesoPagina("Docente")) {
        return;
    }

    if (!formularioSolicitud && tablaSolicitudes && !PermisoServicio.validarAccesoPagina("Administrador")) {
        return;
    }

    function recargarTablaSolicitudes() {
        const solicitudes = SolicitudProgramaStorage.obtenerSolicitudes();
        if (solicitudes === null) {
            SolicitudProgramaVista.mostrarMensaje("No se pudieron leer las solicitudes guardadas.");
            SolicitudProgramaVista.mostrarSolicitudes([]);
            return;
        }

        SolicitudProgramaVista.mostrarSolicitudes(solicitudes);
    }

    if (formularioSolicitud) {
        formularioSolicitud.addEventListener("submit", function (event) {
            event.preventDefault();

            const datos = SolicitudProgramaVista.obtenerDatosFormulario();
            const mensajeValidacion = SolicitudProgramaServicio.validarDatos(datos);

            if (mensajeValidacion) {
                SolicitudProgramaVista.mostrarMensaje(mensajeValidacion);
                return;
            }

            const solicitud = new SolicitudPrograma(
                Date.now(),
                datos.docente,
                datos.laboratorio,
                datos.programa,
                datos.descripcion,
                datos.fecha,
                datos.hora,
                "Pendiente"
            );

            // Guardo la solicitud para que después aparezca en la tabla de administración.
            if (!SolicitudProgramaStorage.agregarSolicitud(solicitud)) {
                SolicitudProgramaVista.mostrarMensaje("No se pudo guardar la solicitud.");
                return;
            }

            SolicitudProgramaVista.mostrarMensaje("Solicitud enviada correctamente.");
            SolicitudProgramaVista.limpiarFormulario();
        });
    }

    if (tablaSolicitudes) {
        recargarTablaSolicitudes();

        tablaSolicitudes.addEventListener("change", function (event) {
            const select = event.target.closest(".cambio-estado-programa");

            if (!select) {
                return;
            }

            const idSolicitud = select.dataset.solicitudId;
            const estadoNuevo = select.value;
            const solicitudActual = SolicitudProgramaStorage.buscarSolicitudPorId(idSolicitud);

            if (!solicitudActual) {
                return;
            }

            const estadoNormalizado = SolicitudProgramaServicio.normalizarEstado(estadoNuevo);
            const solicitudActualizada = SolicitudProgramaStorage.actualizarSolicitud(idSolicitud, {
                estado: estadoNormalizado
            });

            if (!solicitudActualizada) {
                SolicitudProgramaVista.mostrarMensaje("No se pudo actualizar la solicitud.");
                return;
            }

            recargarTablaSolicitudes();
        });
    }
});
