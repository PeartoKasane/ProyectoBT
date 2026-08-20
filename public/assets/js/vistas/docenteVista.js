/*
  este archivo es la vista del docente.
  aca va la logica del JS y no me gusta romperlo.
  pero por ahora sirve para que el sistema funcione.
*/

class DocenteVista {

    static obtenerDatosFormulario() {
        return {
            tipoSala: document.getElementById("tipo_sala").value,
            numeroSala: document.getElementById("num_sala").value,
            fecha: document.getElementById("fecha").value,
            horaEntrada: document.getElementById("hora_entrada").value,
            horaSalida: document.getElementById("hora_salida").value,
            asignatura: document.getElementById("asignatura").value,
            docente: document.getElementById("docente").value,
            grupo: document.getElementById("grupo").value,
            turno: document.getElementById("turno").value
        };
    }

    static obtenerEquipos() {
        const filas = document.querySelectorAll("#registro_salas tr");

        const equipos = [];

        filas.forEach(fila => {

            const numero = fila.children[0].textContent.trim();
            const estudiante =
                fila.querySelector(".nombre-estudiante").value.trim();

            const selectEstado =
                fila.querySelector(".estado-equipo");

            const estado = selectEstado.value;

            const incidencia =
                selectEstado.options[
                    selectEstado.selectedIndex
                ].textContent;

            equipos.push(
                new Equipo(
                    numero,
                    estudiante,
                    estado,
                    incidencia
                )
            );
        });

        return equipos;
    }

    static mostrarMensaje(mensaje) {
        alert(mensaje);
    }

    static limpiarFormulario() {
        document.getElementById("form-docente").reset();
    }

    static renderHistorialTickets(tickets, usuarioActual) {
        const tbody = document.getElementById('historial_tickets_body');

        if (!tbody) return;

        // Filtrar tickets pertenecientes al docente logueado
        const cedulaDocente = usuarioActual && usuarioActual.cedula ? String(usuarioActual.cedula).trim() : '';
        const nombreDocente = usuarioActual && usuarioActual.nombre ? String(usuarioActual.nombre).trim() : '';

        const ticketsFiltrados = (Array.isArray(tickets) ? tickets : []).filter(ticket => {
            try {
                // Preferir comparación por cédula si está disponible
                if (cedulaDocente && ticket && ticket.docenteCedula) {
                    return String(ticket.docenteCedula) === cedulaDocente;
                }

                // compara por nombre si no hay cédula en el ticket
                const tdoc = ticket && ticket.docente ? String(ticket.docente).trim() : '';
                return nombreDocente && tdoc && tdoc === nombreDocente;
            } catch (e) {
                return false;
            }
        });

        // Construir filas
        tbody.replaceChildren();

        if (ticketsFiltrados.length === 0) {
            const filaVacia = document.createElement("tr");
            const celdaVacia = document.createElement("td");
            celdaVacia.colSpan = 10;
            celdaVacia.textContent = "No hay tickets registrados para este docente.";
            filaVacia.appendChild(celdaVacia);
            tbody.appendChild(filaVacia);
            return;
        }

        ticketsFiltrados.forEach(ticket => {
            const descripcion = TicketServicio.obtenerIncidenciasTexto(ticket);
            const horaSalida = ticket.horaSalida || '';
            const prioridad = TicketServicio.obtenerPrioridad(ticket);
            const estado = TicketServicio.normalizarEstado(ticket.estado);

            const fila = document.createElement("tr");
            const valores = [
                [ticket.id, "text-center"],
                [ticket.docente || "", ""],
                [ticket.tipoSala || "", ""],
                [ticket.numeroSala || "", ""],
                [ticket.fecha || "", ""],
                [ticket.horaEntrada || "", ""],
                [horaSalida, ""],
                [prioridad, "text-center"],
                [estado || "", ""],
                [descripcion, ""]
            ];

            valores.forEach(([valor, clase]) => {
                const celda = document.createElement("td");
                celda.className = clase;
                celda.textContent = String(valor);
                fila.appendChild(celda);
            });

            tbody.appendChild(fila);
        });
    }
}

