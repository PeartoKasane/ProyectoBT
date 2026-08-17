class TicketServicio {

    // La prioridad real depende del tipo de incidencia.
    // 0 es la más urgente y 2 es la menos urgente.
    static PRIORIDADES_POR_INCIDENCIA = {
        "No prende": 0,
        "Teclado roto": 1,
        "Mouse roto": 2,
        "Monitor roto": 2,
        "Otro problema": 2
    };

    static ESTADOS_VALIDOS = [
        "Pendiente",
        "Evaluando",
        "En proceso",
        "Terminado"
    ];

    static obtenerIncidencias(ticket) {
        if (!ticket || !Array.isArray(ticket.equipos)) {
            return [];
        }

        return ticket.equipos.filter(equipo => equipo && equipo.incidencia);
    }

    static obtenerIncidenciasTexto(ticket) {
        const incidencias = this.obtenerIncidencias(ticket);

        if (incidencias.length === 0) {
            return "Sin descripción";
        }

        return incidencias.map(equipo => equipo.incidencia || "Sin descripción").join(", ");
    }

    static obtenerEstudiantesTexto(ticket) {
        const incidencias = this.obtenerIncidencias(ticket);

        if (incidencias.length === 0) {
            return "Sin nombre";
        }

        return incidencias.map(equipo => equipo.estudiante || "Sin nombre").join(", ");
    }

    static calcularPrioridadDesdeIncidencias(incidencias) {
        if (!Array.isArray(incidencias) || incidencias.length === 0) {
            return 2;
        }

        const prioridades = incidencias.map(equipo => {
            const nombre = equipo && equipo.incidencia;
            return this.PRIORIDADES_POR_INCIDENCIA[nombre] ?? 2;
        });

        return Math.min(...prioridades);
    }

    static obtenerPrioridad(ticket) {
        if (!ticket) {
            return 2;
        }

        const incidencias = this.obtenerIncidencias(ticket);
        if (incidencias.length > 0) {
            return this.calcularPrioridadDesdeIncidencias(incidencias);
        }

        return Number(ticket.prioridad ?? 2);
    }

    static obtenerPrioridadDesdeIncidencias(incidencias) {
        return this.calcularPrioridadDesdeIncidencias(incidencias);
    }

    static normalizarEstado(estado) {
        if (estado === "Realizado") {
            return "Terminado";
        }

        if (estado === "Finalizado") {
            return "Terminado";
        }

        if (this.ESTADOS_VALIDOS.includes(estado)) {
            return estado;
        }

        return "Pendiente";
    }

    static validarEstado(estado) {
        return this.ESTADOS_VALIDOS.includes(this.normalizarEstado(estado));
    }

    static buscarTicketPorId(tickets, id) {
        return tickets.find(ticket => Number(ticket.id) === Number(id));
    }

    static tieneIncidencias(ticket) {
        return this.obtenerIncidencias(ticket).length > 0;
    }

    static debeMostrarComoPendiente(ticket) {
        return this.normalizarEstado(ticket?.estado) === "Pendiente";
    }

    static debeMostrarComoEvaluando(ticket) {
        return this.normalizarEstado(ticket?.estado) === "Evaluando";
    }

    static debeMostrarComoEnProceso(ticket) {
        return this.normalizarEstado(ticket?.estado) === "En proceso";
    }

    static debeMostrarComoTerminado(ticket) {
        return this.normalizarEstado(ticket?.estado) === "Terminado";
    }
}

globalThis.TicketServicio = TicketServicio;