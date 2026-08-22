/*
  este archivo es la modelo del ticket.
  aca va la logica del JS y no me gusta romperlo.
  pero por ahora sirve para que el sistema funcione.
*/

class Ticket {
    constructor(
        id,
        tipoSala,
        numeroSala,
        fecha,
        horaEntrada,
        horaSalida,
        asignatura,
        docente,
        docenteCedula,
        grupo,
        turno,
        equipos,
        prioridad = 2,
        estado = "Pendiente"
    ) {
        this.id = id;
        this.tipoSala = tipoSala;
        this.numeroSala = numeroSala;
        this.fecha = fecha;
        this.horaEntrada = horaEntrada;
        this.horaSalida = horaSalida;
        this.asignatura = asignatura;
        this.docente = docente;
        this.docenteCedula = docenteCedula || null;
        this.grupo = grupo;
        this.turno = turno;
        this.equipos = equipos;
        this.prioridad = prioridad;
        this.estado = estado;
        // Estos campos permiten consultar rápidamente el equipo de esta incidencia.
        const equipo = Array.isArray(equipos) ? equipos[0] : null;
        this.equipoId = equipo ? equipo.numero : null;
        this.estudiante = equipo ? equipo.estudiante || "" : "";
        this.tipoIncidencia = equipo ? equipo.incidencia || "" : "";
        this.descripcion = this.tipoIncidencia;
    }
}

globalThis.Ticket = Ticket;