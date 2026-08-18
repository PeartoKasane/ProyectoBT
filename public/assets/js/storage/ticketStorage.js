/*
  este archivo es la storage de tickets.
  aca va la logica del JS y no me gusta romperlo.
  pero por ahora sirve para que el sistema funcione.
*/

class TicketStorage {

    static obtenerTickets() {
        const ticketsGuardados = localStorage.getItem("tickets");
        return ticketsGuardados ? JSON.parse(ticketsGuardados) : [];
    }

    static guardarTickets(tickets) {
        localStorage.setItem("tickets", JSON.stringify(tickets));
    }

    static agregarTicket(ticket) {
        const tickets = this.obtenerTickets();
        tickets.push(ticket);
        this.guardarTickets(tickets);
        return ticket;
    }

    static buscarTicketPorId(id) {
        return this.obtenerTickets().find(ticket => Number(ticket.id) === Number(id));
    }

    static actualizarTicket(id, cambios) {
        const tickets = this.obtenerTickets();
        const indice = tickets.findIndex(ticket => Number(ticket.id) === Number(id));

        if (indice === -1) {
            return null;
        }

        tickets[indice] = {
            ...tickets[indice],
            ...cambios
        };

        this.guardarTickets(tickets);
        return tickets[indice];
    }

    static eliminarTicket(id) {
        const ticketsActualizados = this.obtenerTickets().filter(ticket => Number(ticket.id) !== Number(id));
        this.guardarTickets(ticketsActualizados);
        return ticketsActualizados;
    }
}

globalThis.TicketStorage = TicketStorage;