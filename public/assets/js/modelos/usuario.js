/*
  este archivo es la modelo del usuario.
  aca va la logica del JS y no me gusta romperlo.
  pero por ahora sirve para que el sistema funcione.
*/

class Usuario {
    constructor(cedula, nombre, roles = [], contrasena = '') {
        this.cedula = cedula;
        this.nombre = nombre;
        this.roles = Array.isArray(roles) ? roles : [];
        this.contrasena = contrasena;
    }
}

globalThis.Usuario = Usuario;
