/*
  este archivo es la servicio de tickets.
  aca va la logica del JS y no me gusta romperlo.
  pero por ahora sirve para que el sistema funcione.
*/

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

    static SALAS_VALIDAS = {
        laboratorio: ["1", "2", "3", "4", "5", "6"],
        taller: ["1", "2", "3"]
    };

    static TURNOS_VALIDOS = ["M", "V", "N"];

    static validarDatos(datos) {
        const ticket = datos || {};
        const camposObligatorios = [
            ["tipoSala", "Falta seleccionar el tipo de sala."],
            ["numeroSala", "Falta seleccionar el número de sala."],
            ["fecha", "Falta completar la fecha."],
            ["horaEntrada", "Falta completar la hora de entrada."],
            ["horaSalida", "Falta completar la hora de salida."],
            ["asignatura", "Falta completar la asignatura."],
            ["docente", "Falta completar el nombre del docente."],
            ["grupo", "Falta completar el grupo."],
            ["turno", "Falta seleccionar el turno."]
        ];

        for (const [campo, mensaje] of camposObligatorios) {
            if (typeof ticket[campo] !== "string" || !ticket[campo].trim()) {
                return mensaje;
            }
        }

        if (!Array.isArray(ticket.equipos) || ticket.equipos.length === 0) {
            return "Debe indicar al menos un equipo.";
        }

        if (!this.esSalaValida(ticket.tipoSala, ticket.numeroSala)) {
            return "La sala seleccionada no es válida.";
        }

        if (!this.TURNOS_VALIDOS.includes(ticket.turno.trim())) {
            return "El turno seleccionado no es válido.";
        }

        if (!this.esFechaValida(ticket.fecha)) {
            return "La fecha no tiene un formato válido.";
        }

        if (!this.esHoraValida(ticket.horaEntrada) || !this.esHoraValida(ticket.horaSalida)) {
            return "Las horas no tienen un formato válido.";
        }

        if (ticket.horaSalida <= ticket.horaEntrada) {
            return "La hora de salida debe ser posterior a la hora de entrada.";
        }

        return "";
    }

    static esSalaValida(tipoSala, numeroSala) {
        const tipo = String(tipoSala).trim();
        const numero = String(numeroSala).trim();
        return Array.isArray(this.SALAS_VALIDAS[tipo]) && this.SALAS_VALIDAS[tipo].includes(numero);
    }

    static esFechaValida(fecha) {
        if (typeof fecha !== "string" || !/^\d{4}-\d{2}-\d{2}$/.test(fecha)) {
            return false;
        }

        const [anio, mes, dia] = fecha.split("-").map(Number);
        const fechaVerificada = new Date(Date.UTC(anio, mes - 1, dia));
        return fechaVerificada.getUTCFullYear() === anio &&
            fechaVerificada.getUTCMonth() === mes - 1 &&
            fechaVerificada.getUTCDate() === dia;
    }

    static esHoraValida(hora) {
        return typeof hora === "string" && /^(?:[01]\d|2[0-3]):[0-5]\d$/.test(hora);
    }

    static obtenerIncidencias(ticket) {
        if (!ticket || !Array.isArray(ticket.equipos)) {
            return [];
        }

        return ticket.equipos.filter(equipo => Equipo.esIncidencia(equipo));
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

    static obtenerEquiposTexto(ticket) {
        const incidencias = this.obtenerIncidencias(ticket);

        if (incidencias.length === 0) {
            return "Sin equipo";
        }

        return incidencias.map(equipo => equipo.numero || "Sin equipo").join(", ");
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

        return null;
    }

    static validarEstado(estado) {
        return estado === "Realizado" ||
            estado === "Finalizado" ||
            this.ESTADOS_VALIDOS.includes(estado);
    }

    static buscarTicketPorId(tickets, id) {
        // El ID puede ser numérico o tener el formato texto de los tickets nuevos.
        return tickets.find(ticket => String(ticket.id).trim() === String(id).trim());
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