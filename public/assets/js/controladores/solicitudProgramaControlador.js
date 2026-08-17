document.addEventListener("DOMContentLoaded", () => {
    const formularioSolicitud = document.getElementById("form-solicitud-programa");
    const tablaSolicitudes = document.getElementById("registro_solicitudes");

    function recargarTablaSolicitudes() {
        SolicitudProgramaVista.mostrarSolicitudes(SolicitudProgramaStorage.obtenerSolicitudes());
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
            SolicitudProgramaStorage.agregarSolicitud(solicitud);

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
            SolicitudProgramaStorage.actualizarSolicitud(idSolicitud, {
                estado: estadoNormalizado
            });

            recargarTablaSolicitudes();
        });
    }
});
