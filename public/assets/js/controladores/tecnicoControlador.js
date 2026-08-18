/*
  este archivo es la controlador del técnico.
  aca va la logica del JS y no me gusta romperlo.
  pero por ahora sirve para que el sistema funcione.
*/

document.addEventListener('DOMContentLoaded', () => {
    const usuarioActual = PermisoServicio.obtenerSesionUsuario();

    if (!PermisoServicio.validarAccesoPagina('Técnico')) {
        return;
    }

    const tablaTickets = document.getElementById('registro_tickets');
    const tablaSolicitudes = document.getElementById('registro_solicitudes');

    function cargarDatos() {
        if (tablaTickets) {
            AdminVista.mostrarTickets(TicketStorage.obtenerTickets());
        }

        if (tablaSolicitudes) {
            SolicitudProgramaVista.mostrarSolicitudes(SolicitudProgramaStorage.obtenerSolicitudes());
        }
    }

    if (usuarioActual && usuarioActual.roles && usuarioActual.roles.length > 1) {
        UsuarioVista.renderizarRolesUsuario(usuarioActual.roles);
    }

    // El técnico solo consulta información, no modifica tickets ni solicitudes.
    cargarDatos();
});
