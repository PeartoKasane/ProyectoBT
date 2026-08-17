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
        this.grupo = grupo;
        this.turno = turno;
        this.equipos = equipos;
        this.prioridad = prioridad;
        this.estado = estado;
    }
}

globalThis.Ticket = Ticket;