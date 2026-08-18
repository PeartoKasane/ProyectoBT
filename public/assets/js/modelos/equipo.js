/*
  este archivo es la modelo del equipo.
  aca va la logica del JS y no me gusta romperlo.
  pero por ahora sirve para que el sistema funcione.
*/

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