/*
  este archivo es el de solicitudes de programa.
  xdd
*/

class SolicitudProgramaServicio {

    static ESTADOS_VALIDOS = [
        "Pendiente",
        "En preparación",
        "Realizado"
    ];

    static normalizarEstado(estado) {
        if (this.ESTADOS_VALIDOS.includes(estado)) {
            return estado;
        }

        return "Pendiente";
    }

    static validarEstado(estado) {
        return this.ESTADOS_VALIDOS.includes(this.normalizarEstado(estado));
    }

    static validarDatos(solicitud) {
        const datos = solicitud || {};

        if (!datos.docente || !datos.docente.trim()) {
            return "Falta completar el nombre del docente.";
        }

        if (!datos.laboratorio || !datos.laboratorio.trim()) {
            return "Falta seleccionar el laboratorio o sala.";
        }

        if (!datos.programa || !datos.programa.trim()) {
            return "Falta completar el programa que necesita.";
        }

        if (!datos.descripcion || !datos.descripcion.trim()) {
            return "Falta completar la descripción del pedido.";
        }

        if (!datos.fecha) {
            return "Falta completar la fecha.";
        }

        if (!datos.hora) {
            return "Falta completar la hora.";
        }

        return "";
    }
}

globalThis.SolicitudProgramaServicio = SolicitudProgramaServicio;