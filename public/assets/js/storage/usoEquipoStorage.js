/*
  este archivo guarda el registro de uso separado de los tickets.
*/

class UsoEquipoStorage {
    static CLAVE = "registrosUsoEquipos";

    static obtenerRegistros() {
        try {
            const registrosGuardados = localStorage.getItem(this.CLAVE);
            if (!registrosGuardados) {
                return [];
            }

            const registros = JSON.parse(registrosGuardados);
            return Array.isArray(registros) ? registros : null;
        } catch (error) {
            return null;
        }
    }

    static agregarRegistro(registro) {
        const registros = this.obtenerRegistros();
        if (!registros) {
            return null;
        }

        registros.push(registro);
        try {
            localStorage.setItem(this.CLAVE, JSON.stringify(registros));
            return registro;
        } catch (error) {
            return null;
        }
    }
}

globalThis.UsoEquipoStorage = UsoEquipoStorage;