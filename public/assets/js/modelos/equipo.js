class Equipo {
    constructor(numero, estudiante = "", estado = "funcionando", incidencia = "") {
        this.numero = numero;
        this.estudiante = estudiante;
        this.estado = estado;
        this.incidencia = incidencia;
    }

    tieneIncidencia() {
        return this.estado === "incidencia";
    }
}