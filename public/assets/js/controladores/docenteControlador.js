/*
  este archivo es la controlador del docente.
  aca va la logica del JS y no me gusta romperlo.
  pero por ahora sirve para que el sistema funcione.
*/

document.addEventListener("DOMContentLoaded", () => {
    const usuarioActual = PermisoServicio.obtenerSesionUsuario();

    if (!PermisoServicio.validarAccesoPagina('Docente')) {
        return;
    }

    if (usuarioActual && usuarioActual.roles && usuarioActual.roles.length > 1) {
        UsuarioVista.renderizarRolesUsuario(usuarioActual.roles);
    }

    const formulario = document.getElementById("form-docente");

    formulario.addEventListener("submit", function (event) {

        event.preventDefault();

        // Obtener datos de la interfaz
        const datosFormulario = DocenteVista.obtenerDatosFormulario();

        // Obtener equipos desde la vista
        const equipos = DocenteVista.obtenerEquipos();

        // Validar equipos con el servicio de incidencias
        const validacion = IncidenciaServicio.validarEquiposConIncidencias(equipos);

        if (!validacion.valido) {
            DocenteVista.mostrarMensaje(validacion.mensaje);
            return;
        }

        // Preparar solo los equipos que tienen incidencias
        const equiposConIncidencia = IncidenciaServicio.prepararEquiposConIncidencias(equipos);

        // No generar ticket si no hay incidencias
        if (equiposConIncidencia.length === 0) {
            DocenteVista.mostrarMensaje("No hay equipos con incidencias para reportar.");
            return;
        }

        // Crear ticket y guardarlo con la prioridad 
        // de la incidencia reportada por el docente.
        const prioridad = TicketServicio.obtenerPrioridadDesdeIncidencias(equiposConIncidencia);

        const ticket = new Ticket(
            Date.now(),
            datosFormulario.tipoSala,
            datosFormulario.numeroSala,
            datosFormulario.fecha,
            datosFormulario.horaEntrada,
            datosFormulario.horaSalida,
            datosFormulario.asignatura,
            datosFormulario.docente,
            datosFormulario.grupo,
            datosFormulario.turno,
            equiposConIncidencia,
            prioridad,
            "Pendiente"
        );

        TicketStorage.agregarTicket(ticket);

        DocenteVista.mostrarMensaje("Ticket generado correctamente.");

        DocenteVista.limpiarFormulario();
    });
});

