/*
  este archivo es la modelo del equipo.
  aca va la logica del JS y no me gusta romperlo.
  pero por ahora sirve para que el sistema funcione.
*/

class Equipo {
    constructor(numero, estudiante = "", estado = "funcionando", incidencia = "", utilizado = false) {
        this.numero = numero;
        this.estudiante = estudiante;
        this.estado = estado;
        this.incidencia = incidencia;
        // Guardamos el uso por separado para no confundirlo con una incidencia.
        this.utilizado = Boolean(utilizado);
    }

    tieneIncidencia() {
        return Equipo.esIncidencia(this);
    }

    static esIncidencia(equipo) {
        return equipo?.estado === "incidencia" && Boolean(String(equipo?.incidencia || "").trim());
    }
}

globalThis.Equipo = Equipo;