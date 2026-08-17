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

        tabla.innerHTML = "";

        if (lista.length === 0) {
            tabla.innerHTML = `
                <tr>
                    <td colspan="8" class="text-muted py-4">No hay solicitudes de programas pendientes.</td>
                </tr>
            `;
            return;
        }

        tabla.innerHTML = lista.map(solicitud => {
            const estado = SolicitudProgramaServicio.normalizarEstado(solicitud.estado);
            const claseEstado = this.obtenerEstadoClase(estado);

            return `
                <tr data-solicitud-id="${solicitud.id}">
                    <td class="fw-semibold">${solicitud.docente || "Sin nombre"}</td>
                    <td>${solicitud.laboratorio || "Sin laboratorio"}</td>
                    <td>${solicitud.programa || "Sin programa"}</td>
                    <td>${solicitud.descripcion || "Sin detalle"}</td>
                    <td>${solicitud.fecha || "Sin fecha"}</td>
                    <td>${solicitud.hora || "Sin hora"}</td>
                    <td>
                        <span class="badge ${claseEstado} fs-6 px-3">${estado}</span>
                    </td>
                    <td>
                        <select class="form-select form-select-sm cambio-estado-programa" data-solicitud-id="${solicitud.id}" aria-label="Cambiar estado de la solicitud">
                            <option value="Pendiente" ${estado === "Pendiente" ? "selected" : ""}>Pendiente</option>
                            <option value="En preparación" ${estado === "En preparación" ? "selected" : ""}>En preparación</option>
                            <option value="Realizado" ${estado === "Realizado" ? "selected" : ""}>Realizado</option>
                        </select>
                    </td>
                </tr>
            `;
        }).join("");
    }
}
