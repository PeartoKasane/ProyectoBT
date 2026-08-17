class AdminVista {

    static obtenerTabla() {
        return document.getElementById("registro_tickets");
    }

    static obtenerClasePrioridad(prioridad) {
        if (prioridad === 0) {
            return "bg-danger";
        }

        if (prioridad === 1) {
            return "bg-warning text-dark";
        }

        return "bg-secondary";
    }

    static obtenerSala(ticket) {
        const tipoSala = ticket?.tipoSala || "";
        const numeroSala = ticket?.numeroSala || "";

        if (tipoSala && numeroSala) {
            return `${tipoSala} ${numeroSala}`;
        }

        if (numeroSala) {
            return numeroSala;
        }

        return "Sin sala";
    }

    static obtenerEstadoClase(estado) {
        const estadoNormalizado = TicketServicio.normalizarEstado(estado);

        if (estadoNormalizado === "Terminado") {
            return "bg-success";
        }

        if (estadoNormalizado === "En proceso") {
            return "bg-primary";
        }

        if (estadoNormalizado === "Evaluando") {
            return "bg-info text-dark";
        }

        return "bg-secondary";
    }

    static generarDetalleTicket(ticket) {
        const incidencias = TicketServicio.obtenerIncidencias(ticket);
        const descripcion = TicketServicio.obtenerIncidenciasTexto(ticket);
        const estudiantes = TicketServicio.obtenerEstudiantesTexto(ticket);

        return `
            <div class="infoTicket text-start">
                <div class="datosTicket"><strong>Incidencia:</strong> ${descripcion}</div>
                <div class="datosTicket"><strong>Estudiante:</strong> ${estudiantes}</div>
                <div class="datosTicket"><strong>ID:</strong> #${ticket.id}</div>
                <button type="button" class="botonEliminar btn btn-link btn-sm text-danger p-0 mt-2" data-ticket-id="${ticket.id}" aria-label="Eliminar ticket ${ticket.id}">
                    Eliminar
                </button>
            </div>
        `;
    }

    static rellenarSelectorTicket(tickets) {
        const select = document.getElementById("ticketSeleccionado");

        if (!select) {
            return;
        }

        const lista = Array.isArray(tickets) ? tickets : [];

        select.innerHTML = `<option value="">Selecciona un ticket</option>` + lista.map(ticket => `
            <option value="${ticket.id}">${ticket.id} - ${this.obtenerSala(ticket)}</option>
        `).join("");
    }

    static actualizarEstadoActual(ticketId) {
        const estadoActual = document.getElementById("estadoActual");
        if (!estadoActual) {
            return;
        }

        const ticket = TicketStorage.buscarTicketPorId(ticketId);
        estadoActual.textContent = ticket ? TicketServicio.normalizarEstado(ticket.estado) : "Sin ticket seleccionado";
    }

    static mostrarTickets(tickets) {
        const tabla = this.obtenerTabla();

        if (!tabla) {
            return;
        }

        tabla.innerHTML = "";

        const lista = Array.isArray(tickets) ? tickets : [];

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
            const clasePrioridad = this.obtenerClasePrioridad(prioridad);
            const estado = TicketServicio.normalizarEstado(ticket.estado);
            const claseEstado = this.obtenerEstadoClase(estado);

            return `
                <tr data-ticket-id="${ticket.id}">
                    <td class="fw-semibold">${this.obtenerSala(ticket)}</td>
                    <td class="fw-semibold">${this.generarDetalleTicket(ticket)}</td>
                    <td>
                        <span class="badge ${clasePrioridad} fs-6 px-3">${prioridad}</span>
                    </td>
                    <td>
                        <span class="badge ${claseEstado} fs-6 px-3">${estado}</span>
                    </td>
                </tr>
            `;
        }).join("");
    }
}