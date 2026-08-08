const formulario = document.getElementById("form-docente");

formulario.addEventListener("submit", function (event) {
    event.preventDefault();

    // Datos generales de la sala
    const tipoSala = document.getElementById("tipo_sala").value;
    const numeroSala = document.getElementById("num_sala").value;
    const fecha = document.getElementById("fecha").value;
    const horaEntrada = document.getElementById("hora_entrada").value;
    const horaSalida = document.getElementById("hora_salida").value;
    const asignatura = document.getElementById("asignatura").value;
    const docente = document.getElementById("docente").value;
    const grupo = document.getElementById("grupo").value;
    const turno = document.getElementById("turno").value;

    // Buscar todas las filas de equipos
    const filas = document.querySelectorAll("#registro_salas tr");

    const equiposConIncidencia = [];

    filas.forEach(fila => {

        const numeroEquipo = fila.children[0].textContent;
        const nombre = fila.querySelector(".nombre-estudiante").value.trim();
        const selectEstado = fila.querySelector(".estado-equipo");

        const estado = selectEstado.value;
        const descripcion = selectEstado.options[selectEstado.selectedIndex].textContent;

        // Solo guardar equipos que tengan una incidencia
        if (estado === "incidencia") {

            if (nombre === "") {
                alert(`Debe indicar el nombre del estudiante del equipo ${numeroEquipo}.`);
                return;
            }

            equiposConIncidencia.push({
                numero: numeroEquipo,
                estudiante: nombre,
                incidencia: descripcion
            });
        }
    });

    // Si no hay incidencias, no se genera el ticket
    if (equiposConIncidencia.length === 0) {
        alert("No hay equipos con incidencias para reportar.");
        return;
    }

    // Obtener tickets existentes
    let tickets = JSON.parse(localStorage.getItem("tickets")) || [];

    // Crear ticket
    const ticket = {
        id: Date.now(),
        tipoSala: tipoSala,
        numeroSala: numeroSala,
        fecha: fecha,
        horaEntrada: horaEntrada,
        horaSalida: horaSalida,
        asignatura: asignatura,
        docente: docente,
        grupo: grupo,
        turno: turno,
        equipos: equiposConIncidencia,
        prioridad: 2,
        estado: "Pendiente"
    };

    // Guardar ticket
    tickets.push(ticket);

    localStorage.setItem("tickets", JSON.stringify(tickets));

    alert("Ticket generado correctamente.");

    formulario.reset();
});