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
    const botonEnviar = formulario.querySelector('button[type="submit"]');
    let enviandoFormulario = false;

    const habilitarEnvio = () => {
        enviandoFormulario = false;
        if (botonEnviar) {
            botonEnviar.disabled = false;
        }
    };

    function obtenerTickets() {
        const tickets = TicketStorage.obtenerTickets();
        if (tickets === null) {
            DocenteVista.mostrarMensaje("No se pudieron leer los tickets guardados.");
            return [];
        }

        return tickets;
    }

    formulario.addEventListener("submit", function (event) {

        event.preventDefault();

        if (enviandoFormulario) {
            return;
        }

        enviandoFormulario = true;
        if (botonEnviar) {
            botonEnviar.disabled = true;
        }

        // Obtener datos de la interfaz
        const datosFormulario = DocenteVista.obtenerDatosFormulario();

        // Obtener equipos desde la vista
        const equipos = DocenteVista.obtenerEquipos();

        const validacionTicket = TicketServicio.validarDatos({
            ...datosFormulario,
            equipos
        });

        if (validacionTicket) {
            DocenteVista.mostrarMensaje(validacionTicket);
            habilitarEnvio();
            return;
        }

        // Validar equipos con el servicio de incidencias
        const validacion = IncidenciaServicio.validarEquiposConIncidencias(equipos);

        if (!validacion.valido) {
            DocenteVista.mostrarMensaje(validacion.mensaje);
            habilitarEnvio();
            return;
        }

        // Guardar el uso de todos los equipos, aunque ninguno tenga incidencia.
        const registroUso = {
            id: Date.now(),
            ...datosFormulario,
            docenteCedula: usuarioActual && usuarioActual.cedula ? usuarioActual.cedula : null,
            equipos: equipos.map(equipo => ({
                numero: equipo.numero,
                estudiante: equipo.utilizado ? equipo.estudiante : "",
                utilizado: equipo.utilizado,
                incidencia: equipo.tieneIncidencia() ? equipo.incidencia : ""
            }))
        };

        if (!UsoEquipoStorage.agregarRegistro(registroUso)) {
            DocenteVista.mostrarMensaje("No se pudo guardar el registro de uso.");
            habilitarEnvio();
            return;
        }

        // Preparar solo los equipos que tienen incidencias.
        const equiposConIncidencia = IncidenciaServicio.prepararEquiposConIncidencias(equipos);

        // No generar tickets si no hay incidencias, pero conservar el registro de uso.
        if (equiposConIncidencia.length === 0) {
            DocenteVista.mostrarMensaje("Uso de equipos registrado. No hay incidencias para reportar.");
            DocenteVista.limpiarFormulario();
            habilitarEnvio();
            return;
        }

        // Una incidencia de la lista se convierte en un ticket independiente.
        const tickets = equiposConIncidencia.map((equipo, indice) => new Ticket(
            `${Date.now()}-${indice}`,
            datosFormulario.tipoSala,
            datosFormulario.numeroSala,
            datosFormulario.fecha,
            datosFormulario.horaEntrada,
            datosFormulario.horaSalida,
            datosFormulario.asignatura,
            datosFormulario.docente,
            usuarioActual && usuarioActual.cedula ? usuarioActual.cedula : null,
            datosFormulario.grupo,
            datosFormulario.turno,
            [equipo],
            TicketServicio.obtenerPrioridadDesdeIncidencias([equipo]),
            "Pendiente"
        ));

        if (!TicketStorage.agregarTickets(tickets)) {
            DocenteVista.mostrarMensaje("No se pudo guardar el ticket.");
            habilitarEnvio();
            return;
        }

        DocenteVista.mostrarMensaje("Ticket generado correctamente.");

        // Limpiar formulario y refrescar historial
        DocenteVista.limpiarFormulario();
        DocenteVista.renderHistorialTickets(obtenerTickets(), usuarioActual);
        habilitarEnvio();
    });

    DocenteVista.prepararRegistroEquipos();

    // Mostrar historial al cargar la página
    DocenteVista.renderHistorialTickets(obtenerTickets(), usuarioActual);

    // Escuchar cambios en localStorage desde otras pestañas/ventanas
    window.addEventListener('storage', (e) => {
        if (e.key === 'tickets') {
            DocenteVista.renderHistorialTickets(obtenerTickets(), usuarioActual);
        }
    });
});

