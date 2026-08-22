/*
  este archivo es la storage de tickets.
  aca va la logica del JS y no me gusta romperlo.
  pero por ahora sirve para que el sistema funcione.
*/

class TicketStorage {

    static idsIguales(idTicket, idBuscado) {
        // Comparamos como texto para aceptar IDs antiguos numéricos y los nuevos con guion.
        return String(idTicket).trim() === String(idBuscado).trim();
    }

    static obtenerTickets() {
        try {
            const ticketsGuardados = localStorage.getItem("tickets");
            if (!ticketsGuardados) {
                return [];
            }

            const tickets = JSON.parse(ticketsGuardados);
            return Array.isArray(tickets) ? tickets : null;
        } catch (error) {
            return null;
        }
    }

    static guardarTickets(tickets) {
        try {
            localStorage.setItem("tickets", JSON.stringify(tickets));
            return true;
        } catch (error) {
            return false;
        }
    }

    static agregarTicket(ticket) {
        const tickets = this.obtenerTickets();
        if (!tickets) {
            return null;
        }

        tickets.push(ticket);
        return this.guardarTickets(tickets) ? ticket : null;
    }

    static agregarTickets(ticketsNuevos) {
        const tickets = this.obtenerTickets();
        if (!tickets || !Array.isArray(ticketsNuevos) || ticketsNuevos.length === 0) {
            return null;
        }

        // Guardamos todas las incidencias juntas para que no quede un envío a medias.
        const idsExistentes = new Set(tickets.map(ticket => String(ticket.id)));
        const hayIdRepetido = ticketsNuevos.some(ticket => idsExistentes.has(String(ticket.id)));
        if (hayIdRepetido) {
            return null;
        }

        const ticketsActualizados = tickets.concat(ticketsNuevos);
        return this.guardarTickets(ticketsActualizados) ? ticketsNuevos : null;
    }

    static buscarTicketPorId(id) {
        const tickets = this.obtenerTickets();
        return tickets ? tickets.find(ticket => this.idsIguales(ticket.id, id)) : null;
    }

    static actualizarTicket(id, cambios) {
        const tickets = this.obtenerTickets();
        if (!tickets) {
            return null;
        }

        const indice = tickets.findIndex(ticket => this.idsIguales(ticket.id, id));

        if (indice === -1) {
            return null;
        }

        tickets[indice] = {
            ...tickets[indice],
            ...cambios
        };

        return this.guardarTickets(tickets) ? tickets[indice] : null;
    }

    static eliminarTicket(id) {
        const tickets = this.obtenerTickets();
        if (!tickets) {
            return null;
        }

        const ticketsActualizados = tickets.filter(ticket => !this.idsIguales(ticket.id, id));
        return this.guardarTickets(ticketsActualizados) ? ticketsActualizados : null;
    }
}

globalThis.TicketStorage = TicketStorage;