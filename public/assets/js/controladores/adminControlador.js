/*
  este archivo es la controlador del admin.
  aca va la logica del JS y no me gusta romperlo.
  pero por ahora sirve para que el sistema funcione.
*/

document.addEventListener("DOMContentLoaded", () => {
    if (!PermisoServicio.validarAccesoPagina("Administrador")) {
        return;
    }

    const tabla = document.getElementById("registro_tickets");
    const modalEstado = document.getElementById("modal-cambiar-estado");
    const ticketSeleccionado = document.getElementById("ticketSeleccionado");
    const nuevoEstado = document.getElementById("nuevoEstado");
    const formularioEstado = document.getElementById("form-cambiar-estado");

    function obtenerTickets() {
        const tickets = TicketStorage.obtenerTickets();
        if (tickets === null) {
            alert("No se pudieron leer los tickets guardados.");
            return [];
        }

        return tickets;
    }

    function recargarTabla() {
        AdminVista.mostrarTickets(obtenerTickets());
    }

    function prepararModalEstado() {
        // Volvemos a cargar los tickets para que el modal
        // muestre siempre lo que está guardado.
        AdminVista.rellenarSelectorTicket(obtenerTickets());
        const actual = document.getElementById("estadoActual");
        if (actual) {
            actual.textContent = "Sin ticket seleccionado";
        }
        if (ticketSeleccionado) {
            ticketSeleccionado.value = "";
        }
        if (nuevoEstado) {
            nuevoEstado.value = "Pendiente";
        }
    }

    // Cargamos la tabla con los tickets actuales.
    recargarTabla();

    // Cuando se abre el modal, se recargan los tickets disponibles.
    if (modalEstado) {
        modalEstado.addEventListener("show.bs.modal", prepararModalEstado);
    }

    if (ticketSeleccionado) {
        ticketSeleccionado.addEventListener("change", function () {
            AdminVista.actualizarEstadoActual(this.value);
        });
    }

    if (formularioEstado) {
        formularioEstado.addEventListener("submit", function (event) {
            event.preventDefault();

            const idTicket = ticketSeleccionado.value;
            const estadoNuevo = nuevoEstado.value;

            if (!idTicket || !estadoNuevo) {
                return;
            }

            const ticketActual = TicketStorage.buscarTicketPorId(idTicket);

            if (!ticketActual) {
                return;
            }

            if (!TicketServicio.validarEstado(estadoNuevo)) {
                alert("El estado seleccionado no es válido.");
                return;
            }

            const estadoNormalizado = TicketServicio.normalizarEstado(estadoNuevo);
            const nuevoTicket = TicketStorage.actualizarTicket(idTicket, {
                estado: estadoNormalizado
            });

            if (!nuevoTicket) {
                alert("No se pudo actualizar el ticket.");
                return;
            }

            recargarTabla();
            const modal = bootstrap.Modal.getInstance(modalEstado);
            if (modal) {
                modal.hide();
            }
        });
    }

    if (tabla) {
        tabla.addEventListener("click", function (event) {
            const boton = event.target.closest(".botonEliminar");

            if (!boton) {
                return;
            }

            const idTicket = boton.dataset.ticketId;
            const ticket = TicketStorage.buscarTicketPorId(idTicket);

            if (!ticket) {
                return;
            }

            const confirmado = window.confirm(`¿Eliminar el ticket #${ticket.id}?`);
            if (!confirmado) {
                return;
            }

            if (!TicketStorage.eliminarTicket(idTicket)) {
                alert("No se pudo eliminar el ticket.");
                return;
            }

            recargarTabla();
        });
    }
});