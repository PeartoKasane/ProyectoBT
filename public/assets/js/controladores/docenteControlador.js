document.addEventListener("DOMContentLoaded", () => {

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

        // Crear ticket y guardarlo
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
            2,
            "Pendiente"
        );

        TicketStorage.agregarTicket(ticket);

        DocenteVista.mostrarMensaje("Ticket generado correctamente.");

        DocenteVista.limpiarFormulario();
    });
});

