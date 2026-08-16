class TicketServicio {

    // Obtiene el nivel de prioridad del ticket.
    // utiliza el valor 2 como prioridad predeterminada.
    static obtenerPrioridad(ticket) {
        return ticket.prioridad ?? 2;
    }

    // Obtiene las incidencias asociadas al ticket.
    // Si el ticket no contiene equipos con incidencias,
    // devuelve una lista vacía.
    static obtenerIncidencias(ticket) {
        return ticket.equipos ?? [];
    }

    // Determina si un ticket tiene incidencias registradas.
    static tieneIncidencias(ticket) {
        return this.obtenerIncidencias(ticket).length > 0;
    }

    // Determina si las incidencias del ticket deben
    // mostrarse en la columna de tickets pendientes.
    static debeMostrarComoPendiente(ticket) {
        return ticket.estado === "Pendiente";
    }

    // Determina si las incidencias del ticket deben
    // mostrarse en la columna de tickets realizados.
    static debeMostrarComoRealizado(ticket) {
        return ticket.estado === "Realizado";
    }
}