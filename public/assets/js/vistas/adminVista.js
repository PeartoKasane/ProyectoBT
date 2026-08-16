class AdminVista {

    // Obtiene la tabla donde se muestran los tickets.
    static obtenerTabla() {
        return document.getElementById("registro_tickets");
    }

    // Determina la clase CSS correspondiente
    // al nivel de prioridad del ticket.
    static obtenerClasePrioridad(prioridad) {

        if (prioridad === 0) {
            return "bg-danger";
        }

        if (prioridad === 1) {
            return "bg-warning text-dark";
        }

        return "bg-secondary";
    }

    // Genera el contenido HTML correspondiente
    // a las incidencias asociadas al ticket.
    static generarIncidencias(incidencias) {

        if (incidencias.length === 0) {
            return "Sin incidencias registradas";
        }

        return incidencias.map(equipo => `
            <div>
                Equipo ${equipo.numero}:
                ${equipo.estudiante} -
                ${equipo.incidencia}
            </div>
        `).join("");
    }

    // Muestra todos los tickets almacenados
    // en la tabla del administrador.
    static mostrarTickets(tickets) {

        const tabla = this.obtenerTabla();

        // Elimina las filas de prueba que estaban
        // escritas directamente en el HTML.
        tabla.innerHTML = "";

        // Recorre todos los tickets almacenados.
        tickets.forEach(ticket => {

            // Obtiene la prioridad del ticket.
            const prioridad =
                Number(TicketServicio.obtenerPrioridad(ticket));

            // Obtiene las incidencias del ticket.
            const incidencias =
                TicketServicio.obtenerIncidencias(ticket);

            // Genera el contenido de las incidencias.
            const contenidoIncidencias =
                this.generarIncidencias(incidencias);

            // Obtiene la clase visual correspondiente
            // a la prioridad.
            const clasePrioridad =
                this.obtenerClasePrioridad(prioridad);

            // Comprueba el estado del ticket.
            const pendiente =
                TicketServicio.debeMostrarComoPendiente(ticket);

            const realizado =
                TicketServicio.debeMostrarComoRealizado(ticket);

            // Agrega una fila manteniendo siempre
            // el orden: Prioridad, Pendiente, Realizado.
            tabla.innerHTML += `
                <tr>

                    <td>
                        <span class="badge ${clasePrioridad} fs-6 px-3">
                            ${prioridad}
                        </span>
                    </td>

                    <td class="fw-semibold">
                        ${pendiente
                            ? contenidoIncidencias
                            : "---"}
                    </td>

                    <td class="fw-semibold">
                        ${realizado
                            ? contenidoIncidencias
                            : "---"}
                    </td>

                </tr>
            `;
        });
    }
}