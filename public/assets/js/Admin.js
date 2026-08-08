const tabla = document.getElementById("registro_tickets");

let tickets = JSON.parse(localStorage.getItem("tickets")) || [];

function mostrarTickets() {

    tabla.innerHTML = "";

    tickets.forEach(ticket => {

        let prioridad = ticket.prioridad ?? 2;
        let clasePrioridad = "bg-secondary";

        if (prioridad === 0) {
            clasePrioridad = "bg-danger";
        } else if (prioridad === 1) {
            clasePrioridad = "bg-warning text-dark";
        }

        let incidencias = "";

        if (ticket.equipos && ticket.equipos.length > 0) {

            ticket.equipos.forEach(equipo => {

                incidencias += `
                    <div>
                        Equipo ${equipo.numero}: 
                        ${equipo.estudiante} -
                        ${equipo.incidencia}
                    </div>
                `;

            });

        } else {
            incidencias = "Sin incidencias registradas";
        }

        tabla.innerHTML += `
            <tr>
                <td>
                    <span class="badge ${clasePrioridad} fs-6 px-3">
                        ${prioridad}
                    </span>
                </td>

                <td class="fw-semibold">
                    ${ticket.estado === "Pendiente"
                        ? incidencias
                        : "---"}
                </td>

                <td class="fw-semibold">
                    ${ticket.estado === "Realizado"
                        ? incidencias
                        : "---"}
                </td>
            </tr>
        `;
    });
}

mostrarTickets();