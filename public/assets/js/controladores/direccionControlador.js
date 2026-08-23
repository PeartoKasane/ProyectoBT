/*
  este archivo es la controlador de dirección.
  aca va la logica del JS y no me gusta romperlo.
  pero por ahora sirve para que el sistema funcione.
*/

document.addEventListener('DOMContentLoaded', () => {
    const usuarioActual = PermisoServicio.obtenerSesionUsuario();

    if (!PermisoServicio.validarAccesoPagina('Dirección')) {
        return;
    }

    const tablaTickets = document.getElementById('registro_tickets');
    const tablaSolicitudes = document.getElementById('registro_solicitudes');

    function cargarDatos() {
        if (tablaTickets) {
            const tickets = TicketStorage.obtenerTickets();
            if (tickets === null) {
                alert('No se pudieron leer los tickets guardados.');
            }
            DireccionVista.mostrarTickets(tickets || []);
        }

        if (tablaSolicitudes) {
            const solicitudes = SolicitudProgramaStorage.obtenerSolicitudes();
            if (solicitudes === null) {
                alert('No se pudieron leer las solicitudes guardadas.');
            }
            DireccionVista.mostrarSolicitudes(solicitudes || []);
        }
    }

    if (usuarioActual && usuarioActual.roles && usuarioActual.roles.length > 1) {
        UsuarioVista.renderizarRolesUsuario(usuarioActual.roles);
    }

    // Dirección solo consulta estos datos y no puede realizar cambios.
    cargarDatos();
});
