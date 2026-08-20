/*
  este archivo es la vista de solicitudes.
  aca va la logica del JS y no me gusta romperlo.
  pero por ahora sirve para que el sistema funcione.
*/

class SolicitudProgramaVista {

    static obtenerDatosFormulario() {
        return {
            docente: document.getElementById("docentePrograma").value.trim(),
            laboratorio: document.getElementById("laboratorioPrograma").value.trim(),
            programa: document.getElementById("programaNecesitado").value.trim(),
            descripcion: document.getElementById("detallePrograma").value.trim(),
            fecha: document.getElementById("fechaPrograma").value,
            hora: document.getElementById("horaPrograma").value,
            estado: "Pendiente"
        };
    }

    static mostrarMensaje(mensaje) {
        const contenedor = document.getElementById("mensajeSolicitudPrograma");

        if (!contenedor) {
            alert(mensaje);
            return;
        }

        contenedor.textContent = mensaje;
        contenedor.classList.remove("d-none");
    }

    static limpiarFormulario() {
        const formulario = document.getElementById("form-solicitud-programa");

        if (formulario) {
            formulario.reset();
        }
    }

    static obtenerEstadoClase(estado) {
        const estadoNormalizado = SolicitudProgramaServicio.normalizarEstado(estado);

        if (estadoNormalizado === "Realizado") {
            return "bg-success";
        }

        if (estadoNormalizado === "En preparación") {
            return "bg-primary";
        }

        return "bg-secondary";
    }

    static mostrarSolicitudes(solicitudes) {
        const tabla = document.getElementById("registro_solicitudes");

        if (!tabla) {
            return;
        }

        const lista = Array.isArray(solicitudes) ? solicitudes : [];

        tabla.replaceChildren();

        if (lista.length === 0) {
            const filaVacia = document.createElement("tr");
            const celdaVacia = document.createElement("td");
            celdaVacia.colSpan = 8;
            celdaVacia.className = "text-muted py-4";
            celdaVacia.textContent = "No hay solicitudes de programas pendientes.";
            filaVacia.appendChild(celdaVacia);
            tabla.appendChild(filaVacia);
            return;
        }

        lista.forEach(solicitud => {
            const estado = SolicitudProgramaServicio.normalizarEstado(solicitud.estado);
            const claseEstado = this.obtenerEstadoClase(estado);

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

            const accionesCelda = document.createElement("td");
            const selector = document.createElement("select");
            selector.className = "form-select form-select-sm cambio-estado-programa";
            selector.dataset.solicitudId = String(solicitud.id);
            selector.setAttribute("aria-label", "Cambiar estado de la solicitud");

            ["Pendiente", "En preparación", "Realizado"].forEach(opcionTexto => {
                const opcion = document.createElement("option");
                opcion.value = opcionTexto;
                opcion.textContent = opcionTexto;
                opcion.selected = estado === opcionTexto;
                selector.appendChild(opcion);
            });

            accionesCelda.appendChild(selector);
            fila.appendChild(accionesCelda);
            tabla.appendChild(fila);
        });
    }
}
