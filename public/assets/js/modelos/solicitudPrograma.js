class SolicitudPrograma {
    constructor(
        id,
        docente,
        laboratorio,
        programa,
        descripcion,
        fecha,
        hora,
        estado = "Pendiente"
    ) {
        this.id = id;
        this.docente = docente;
        this.laboratorio = laboratorio;
        this.programa = programa;
        this.descripcion = descripcion;
        this.fecha = fecha;
        this.hora = hora;
        this.estado = estado;
    }
}

globalThis.SolicitudPrograma = SolicitudPrograma;