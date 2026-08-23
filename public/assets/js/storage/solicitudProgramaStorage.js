/*
  este archivo es la storage de solicitudes.
  aca va la logica del JS y no me gusta romperlo.
  pero por ahora sirve para que el sistema funcione.
*/

class SolicitudProgramaStorage {

    static obtenerSolicitudes() {
        try {
            const solicitudesGuardadas = localStorage.getItem("solicitudesPrograma");
            if (!solicitudesGuardadas) {
                return [];
            }

            const solicitudes = JSON.parse(solicitudesGuardadas);
            return Array.isArray(solicitudes) ? solicitudes : null;
        } catch (error) {
            return null;
        }
    }

    static guardarSolicitudes(solicitudes) {
        try {
            localStorage.setItem("solicitudesPrograma", JSON.stringify(solicitudes));
            return true;
        } catch (error) {
            return false;
        }
    }

    static agregarSolicitud(solicitud) {
        const solicitudes = this.obtenerSolicitudes();
        if (!solicitudes) {
            return null;
        }

        solicitudes.push(solicitud);
        return this.guardarSolicitudes(solicitudes) ? solicitud : null;
    }

    static buscarSolicitudPorId(id) {
        const solicitudes = this.obtenerSolicitudes();
        return solicitudes ? solicitudes.find(solicitud => Number(solicitud.id) === Number(id)) : null;
    }

    static actualizarSolicitud(id, cambios) {
        const solicitudes = this.obtenerSolicitudes();
        if (!solicitudes) {
            return null;
        }

        const indice = solicitudes.findIndex(solicitud => Number(solicitud.id) === Number(id));

        if (indice === -1) {
            return null;
        }

        solicitudes[indice] = {
            ...solicitudes[indice],
            ...cambios
        };

        return this.guardarSolicitudes(solicitudes) ? solicitudes[indice] : null;
    }

    static eliminarSolicitud(id) {
        const solicitudes = this.obtenerSolicitudes();
        if (!solicitudes) {
            return null;
        }

        const solicitudesActualizadas = solicitudes.filter(solicitud => Number(solicitud.id) !== Number(id));
        return this.guardarSolicitudes(solicitudesActualizadas) ? solicitudesActualizadas : null;
    }
}

globalThis.SolicitudProgramaStorage = SolicitudProgramaStorage;