class TicketStorage {

    static obtenerTickets() {
        return JSON.parse(localStorage.getItem("tickets")) || [];
    }

    static guardarTickets(tickets) {
        localStorage.setItem("tickets", JSON.stringify(tickets));
    }

    static agregarTicket(ticket) {
        const tickets = this.obtenerTickets();

        tickets.push(ticket);

        this.guardarTickets(tickets);
    }
}