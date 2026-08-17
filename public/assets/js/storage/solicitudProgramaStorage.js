class SolicitudProgramaStorage {

    static obtenerSolicitudes() {
        const solicitudesGuardadas = localStorage.getItem("solicitudesPrograma");
        return solicitudesGuardadas ? JSON.parse(solicitudesGuardadas) : [];
    }

    static guardarSolicitudes(solicitudes) {
        localStorage.setItem("solicitudesPrograma", JSON.stringify(solicitudes));
    }

    static agregarSolicitud(solicitud) {
        const solicitudes = this.obtenerSolicitudes();
        solicitudes.push(solicitud);
        this.guardarSolicitudes(solicitudes);
        return solicitud;
    }

    static buscarSolicitudPorId(id) {
        return this.obtenerSolicitudes().find(solicitud => Number(solicitud.id) === Number(id));
    }

    static actualizarSolicitud(id, cambios) {
        const solicitudes = this.obtenerSolicitudes();
        const indice = solicitudes.findIndex(solicitud => Number(solicitud.id) === Number(id));

        if (indice === -1) {
            return null;
        }

        solicitudes[indice] = {
            ...solicitudes[indice],
            ...cambios
        };

        this.guardarSolicitudes(solicitudes);
        return solicitudes[indice];
    }

    static eliminarSolicitud(id) {
        const solicitudesActualizadas = this.obtenerSolicitudes().filter(solicitud => Number(solicitud.id) !== Number(id));
        this.guardarSolicitudes(solicitudesActualizadas);
        return solicitudesActualizadas;
    }
}

globalThis.SolicitudProgramaStorage = SolicitudProgramaStorage;