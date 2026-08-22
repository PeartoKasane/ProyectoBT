/*
  este archivo es la servicio de incidencias.
  xddd
*/

class IncidenciaServicio {

    static obtenerEquiposConIncidencias(equipos) {
        return equipos.filter(equipo => equipo.tieneIncidencia());
    }

    static validarEquiposConIncidencias(equipos) {
        for (const equipo of equipos) {
            if (equipo.tieneIncidencia() && equipo.utilizado && String(equipo.estudiante || "").trim() === "") {
                return {
                    valido: false,
                    mensaje: `Debe indicar el nombre del estudiante del equipo ${equipo.numero}.`
                };
            }
        }

        return {
            valido: true,
            mensaje: ""
        };
    }

    static prepararEquiposConIncidencias(equipos) {
        return equipos
            .filter(equipo => equipo.tieneIncidencia())
            .map(equipo => ({
                numero: equipo.numero,
                estudiante: equipo.utilizado ? equipo.estudiante : "",
                estado: "incidencia",
                incidencia: equipo.incidencia,
                utilizado: equipo.utilizado
            }));
    }
}
