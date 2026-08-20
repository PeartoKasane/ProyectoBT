/*
  este archivo es la vista del admin.
  aca va la logica del JS y no me gusta romperlo.
  pero por ahora sirve para que el sistema funcione.
*/

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
        const descripcion = TicketServicio.obtenerIncidenciasTexto(ticket);
        const estudiantes = TicketServicio.obtenerEstudiantesTexto(ticket);
        const equipos = TicketServicio.obtenerEquiposTexto(ticket);

        const contenedor = document.createElement("div");
        contenedor.className = "infoTicket text-start";

        const datos = [
            ["Incidencia:", descripcion],
            ["Equipo:", equipos],
            ["Estudiante:", estudiantes],
            ["ID:", `#${ticket.id}`]
        ];

        datos.forEach(([etiqueta, valor]) => {
            const linea = document.createElement("div");
            linea.className = "datosTicket";

            const titulo = document.createElement("strong");
            titulo.textContent = etiqueta;
            linea.append(titulo, document.createTextNode(` ${valor}`));
            contenedor.appendChild(linea);
        });

        const boton = document.createElement("button");
        boton.type = "button";
        boton.className = "botonEliminar btn btn-link btn-sm text-danger p-0 mt-2";
        boton.dataset.ticketId = String(ticket.id);
        boton.setAttribute("aria-label", `Eliminar ticket ${ticket.id}`);
        boton.textContent = "Eliminar";
        contenedor.appendChild(boton);

        return contenedor;
    }

    static rellenarSelectorTicket(tickets) {
        const select = document.getElementById("ticketSeleccionado");

        if (!select) {
            return;
        }

        const lista = Array.isArray(tickets) ? tickets : [];

        select.replaceChildren();

        const opcionInicial = document.createElement("option");
        opcionInicial.value = "";
        opcionInicial.textContent = "Selecciona un ticket";
        select.appendChild(opcionInicial);

        lista.forEach(ticket => {
            const opcion = document.createElement("option");
            opcion.value = String(ticket.id);
            opcion.textContent = `${ticket.id} - ${this.obtenerSala(ticket)}`;
            select.appendChild(opcion);
        });
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

        tabla.replaceChildren();

        const lista = Array.isArray(tickets) ? tickets : [];

        if (lista.length === 0) {
            const filaVacia = document.createElement("tr");
            const celdaVacia = document.createElement("td");
            celdaVacia.colSpan = 4;
            celdaVacia.className = "text-muted py-4";
            celdaVacia.textContent = "No hay tickets registrados.";
            filaVacia.appendChild(celdaVacia);
            tabla.appendChild(filaVacia);
            return;
        }

        lista.forEach(ticket => {
            const prioridad = Number(TicketServicio.obtenerPrioridad(ticket));
            const clasePrioridad = this.obtenerClasePrioridad(prioridad);
            const estado = TicketServicio.normalizarEstado(ticket.estado);
            const claseEstado = this.obtenerEstadoClase(estado);

            const fila = document.createElement("tr");
            fila.dataset.ticketId = String(ticket.id);

            const sala = document.createElement("td");
            sala.className = "fw-semibold";
            sala.textContent = this.obtenerSala(ticket);

            const detalle = document.createElement("td");
            detalle.className = "fw-semibold";
            detalle.appendChild(this.generarDetalleTicket(ticket));

            const prioridadCelda = document.createElement("td");
            const prioridadBadge = document.createElement("span");
            prioridadBadge.className = `badge ${clasePrioridad} fs-6 px-3`;
            prioridadBadge.textContent = String(prioridad);
            prioridadCelda.appendChild(prioridadBadge);

            const estadoCelda = document.createElement("td");
            const estadoBadge = document.createElement("span");
            estadoBadge.className = `badge ${claseEstado} fs-6 px-3`;
            estadoBadge.textContent = estado || "";
            estadoCelda.appendChild(estadoBadge);

            fila.append(sala, detalle, prioridadCelda, estadoCelda);
            tabla.appendChild(fila);
        });
    }
}