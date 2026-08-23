/*
  este archivo es la vista de dirección.
  aca va la logica del JS y no me gusta romperlo.
  pero por ahora sirve para que el sistema funcione.
*/

class DireccionVista {
    static mostrarTickets(tickets) {
        const tabla = document.getElementById('registro_tickets');

        if (!tabla) {
            return;
        }

        const lista = Array.isArray(tickets) ? tickets : [];
        tabla.replaceChildren();

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
            const estado = TicketServicio.normalizarEstado(ticket.estado);
            const clasePrioridad = AdminVista.obtenerClasePrioridad(prioridad);
            const claseEstado = AdminVista.obtenerEstadoClase(estado);
            const fila = document.createElement("tr");
            fila.dataset.ticketId = String(ticket.id);

            const sala = document.createElement("td");
            sala.className = "fw-semibold";
            sala.textContent = AdminVista.obtenerSala(ticket);

            const detalle = document.createElement("td");
            detalle.className = "fw-semibold";
            const detalleContenedor = document.createElement("div");
            detalleContenedor.className = "infoTicket text-start";

            [
                ["Incidencia:", TicketServicio.obtenerIncidenciasTexto(ticket)],
                ["Equipo:", TicketServicio.obtenerEquiposTexto(ticket)],
                ["Estudiante:", TicketServicio.obtenerEstudiantesTexto(ticket)],
                ["ID:", `#${ticket.id}`]
            ].forEach(([etiqueta, valor]) => {
                const linea = document.createElement("div");
                linea.className = "datosTicket";
                const titulo = document.createElement("strong");
                titulo.textContent = etiqueta;
                linea.append(titulo, document.createTextNode(` ${valor}`));
                detalleContenedor.appendChild(linea);
            });

            detalle.appendChild(detalleContenedor);

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

    static mostrarSolicitudes(solicitudes) {
        const tabla = document.getElementById('registro_solicitudes');

        if (!tabla) {
            return;
        }

        const lista = Array.isArray(solicitudes) ? solicitudes : [];
        tabla.replaceChildren();

        if (lista.length === 0) {
            const filaVacia = document.createElement("tr");
            const celdaVacia = document.createElement("td");
            celdaVacia.colSpan = 7;
            celdaVacia.className = "text-muted py-4";
            celdaVacia.textContent = "No hay solicitudes de preparación registradas.";
            filaVacia.appendChild(celdaVacia);
            tabla.appendChild(filaVacia);
            return;
        }

        lista.forEach(solicitud => {
            const estado = SolicitudProgramaServicio.normalizarEstado(solicitud.estado);
            const claseEstado = SolicitudProgramaVista.obtenerEstadoClase(estado);

            const fila = document.createElement("tr");
            fila.dataset.solicitudId = String(solicitud.id);
            const valores = [
                [solicitud.docente || "Sin nombre", "fw-semibold"],
                [solicitud.laboratorio || "Sin laboratorio", ""],
                [solicitud.programa || "Sin programa", ""],
                [solicitud.descripcion || "Sin detalle", ""],
                [solicitud.fecha || "Sin fecha", ""],
                [solicitud.hora || "Sin hora", ""]
            ];

            valores.forEach(([valor, clase]) => {
                const celda = document.createElement("td");
                celda.className = clase;
                celda.textContent = valor;
                fila.appendChild(celda);
            });

            const estadoCelda = document.createElement("td");
            const estadoBadge = document.createElement("span");
            estadoBadge.className = `badge ${claseEstado} fs-6 px-3`;
            estadoBadge.textContent = estado || "";
            estadoCelda.appendChild(estadoBadge);
            fila.appendChild(estadoCelda);
            tabla.appendChild(fila);
        });
    }
}

globalThis.DireccionVista = DireccionVista;
