/*
  este archivo es la storage de tickets.
  aca va la logica del JS y no me gusta romperlo.
  pero por ahora sirve para que el sistema funcione.
*/

class TicketStorage {

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

    static buscarTicketPorId(id) {
        const tickets = this.obtenerTickets();
        return tickets ? tickets.find(ticket => Number(ticket.id) === Number(id)) : null;
    }

    static actualizarTicket(id, cambios) {
        const tickets = this.obtenerTickets();
        if (!tickets) {
            return null;
        }

        const indice = tickets.findIndex(ticket => Number(ticket.id) === Number(id));

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

        const ticketsActualizados = tickets.filter(ticket => Number(ticket.id) !== Number(id));
        return this.guardarTickets(ticketsActualizados) ? ticketsActualizados : null;
    }
}

globalThis.TicketStorage = TicketStorage;