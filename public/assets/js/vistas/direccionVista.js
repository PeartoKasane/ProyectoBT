/*
  este archivo es la vista de dirección.
  aca va la logica del JS y no me gusta romperlo.
  pero por ahora sirve para que el sistema funcione.
*/

class DireccionVista {
    static mostrarTickets(tickets) {
        const tabla = document.getElementById('registro_tickets');

        if (!tabla) {
            return;
        }

        const lista = Array.isArray(tickets) ? tickets : [];
        tabla.innerHTML = '';

        if (lista.length === 0) {
            tabla.innerHTML = `
                <tr>
                    <td colspan="4" class="text-muted py-4">No hay tickets registrados.</td>
                </tr>
            `;
            return;
        }

        tabla.innerHTML = lista.map(ticket => {
            const prioridad = Number(TicketServicio.obtenerPrioridad(ticket));
            const estado = TicketServicio.normalizarEstado(ticket.estado);
            const clasePrioridad = AdminVista.obtenerClasePrioridad(prioridad);
            const claseEstado = AdminVista.obtenerEstadoClase(estado);
            const equipo = TicketServicio.obtenerEquiposTexto(ticket);

            return `
                <tr data-ticket-id="${ticket.id}">
                    <td class="fw-semibold">${AdminVista.obtenerSala(ticket)}</td>
                    <td class="fw-semibold">
                        <div class="infoTicket text-start">
                            <div class="datosTicket"><strong>Incidencia:</strong> ${TicketServicio.obtenerIncidenciasTexto(ticket)}</div>
                            <div class="datosTicket"><strong>Equipo:</strong> ${equipo}</div>
                            <div class="datosTicket"><strong>Estudiante:</strong> ${TicketServicio.obtenerEstudiantesTexto(ticket)}</div>
                            <div class="datosTicket"><strong>ID:</strong> #${ticket.id}</div>
                        </div>
                    </td>
                    <td>
                        <span class="badge ${clasePrioridad} fs-6 px-3">${prioridad}</span>
                    </td>
                    <td>
                        <span class="badge ${claseEstado} fs-6 px-3">${estado}</span>
                    </td>
                </tr>
            `;
        }).join('');
    }

    static mostrarSolicitudes(solicitudes) {
        const tabla = document.getElementById('registro_solicitudes');

        if (!tabla) {
            return;
        }

        const lista = Array.isArray(solicitudes) ? solicitudes : [];
        tabla.innerHTML = '';

        if (lista.length === 0) {
            tabla.innerHTML = `
                <tr>
                    <td colspan="7" class="text-muted py-4">No hay solicitudes de preparación registradas.</td>
                </tr>
            `;
            return;
        }

        tabla.innerHTML = lista.map(solicitud => {
            const estado = SolicitudProgramaServicio.normalizarEstado(solicitud.estado);
            const claseEstado = SolicitudProgramaVista.obtenerEstadoClase(estado);

            return `
                <tr data-solicitud-id="${solicitud.id}">
                    <td class="fw-semibold">${solicitud.docente || 'Sin nombre'}</td>
                    <td>${solicitud.laboratorio || 'Sin laboratorio'}</td>
                    <td>${solicitud.programa || 'Sin programa'}</td>
                    <td>${solicitud.descripcion || 'Sin detalle'}</td>
                    <td>${solicitud.fecha || 'Sin fecha'}</td>
                    <td>${solicitud.hora || 'Sin hora'}</td>
                    <td>
                        <span class="badge ${claseEstado} fs-6 px-3">${estado}</span>
                    </td>
                </tr>
            `;
        }).join('');
    }
}

globalThis.DireccionVista = DireccionVista;
