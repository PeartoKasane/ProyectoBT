// Espera a que todo el documento HTML haya sido cargado
// antes de empezar a mostrar los tickets.
document.addEventListener("DOMContentLoaded", () => {

    // Obtiene todos los tickets almacenados.
    const tickets =
        TicketStorage.obtenerTickets();

    // Envía los tickets a la vista para que
    // sean representados en la tabla.
    AdminVista.mostrarTickets(tickets);
});