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
}

