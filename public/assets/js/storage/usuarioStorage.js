/*
  este archivo es la storage de usuarios.
  aca va la logica del JS y no me gusta romperlo.
  pero por ahora sirve para que el sistema funcione.
*/

class UsuarioStorage {
    // esto es como la "base de datos" del frontend mientras estamos probando
    // no es real, es solo para poder entrar al sistema sin PHP
    static CLAVE_USUARIOS = 'usuarios';

    // usuarios de prueba para revisar todos los roles y combinaciones
    // todos tienen la misma contraseña para no complicar las pruebas
    static USUARIOS_PRUEBA = [
        { cedula: '10000001', nombre: 'Usuario Administrador', roles: ['Administrador'], contrasena: '1234' },
        { cedula: '10000002', nombre: 'Usuario Dirección', roles: ['Dirección'], contrasena: '1234' },
        { cedula: '10000003', nombre: 'Usuario Técnico', roles: ['Técnico'], contrasena: '1234' },
        { cedula: '10000004', nombre: 'Usuario Docente', roles: ['Docente'], contrasena: '1234' },
        { cedula: '20000001', nombre: 'Usuario Administrador Dirección', roles: ['Administrador', 'Dirección'], contrasena: '1234' },
        { cedula: '20000002', nombre: 'Usuario Administrador Técnico', roles: ['Administrador', 'Técnico'], contrasena: '1234' },
        { cedula: '20000003', nombre: 'Usuario Administrador Docente', roles: ['Administrador', 'Docente'], contrasena: '1234' },
        { cedula: '20000004', nombre: 'Usuario Dirección Técnico', roles: ['Dirección', 'Técnico'], contrasena: '1234' },
        { cedula: '20000005', nombre: 'Usuario Dirección Docente', roles: ['Dirección', 'Docente'], contrasena: '1234' },
        { cedula: '20000006', nombre: 'Usuario Técnico Docente', roles: ['Técnico', 'Docente'], contrasena: '1234' }
    ];

    static obtenerUsuarios() {
        // lee lo que haya guardado en localStorage
        const usuariosGuardados = localStorage.getItem(this.CLAVE_USUARIOS);
        return usuariosGuardados ? JSON.parse(usuariosGuardados) : [];
    }

    static guardarUsuarios(usuarios) {
        // guarda la lista en localStorage para poder usarla despues
        localStorage.setItem(this.CLAVE_USUARIOS, JSON.stringify(usuarios));
    }

    static inicializarUsuariosPrueba() {
        // si ya hay usuarios cargados, no los duplica
        const usuariosActuales = this.obtenerUsuarios();

        if (usuariosActuales.length > 0) {
            return usuariosActuales;
        }

        // normaliza los roles para que queden en la misma forma que usa el sistema
        const usuariosPreparados = this.USUARIOS_PRUEBA.map(usuario => ({
            ...usuario,
            roles: PermisoServicio.normalizarRoles(usuario.roles)
        }));

        this.guardarUsuarios(usuariosPreparados);
        return usuariosPreparados;
    }

    static buscarUsuarioPorCredenciales(cedula, contrasena) {
        // busca usuario por cedula y contraseña exacta
        const usuarios = this.obtenerUsuarios();
        return usuarios.find(usuario =>
            String(usuario.cedula) === String(cedula) &&
            String(usuario.contrasena || '') === String(contrasena)
        );
    }

    static agregarUsuario(usuario) {
        // agrega usuario si no existe ya por cedula
        const usuarios = this.obtenerUsuarios();
        const yaExiste = usuarios.some(item => String(item.cedula) === String(usuario.cedula));

        if (yaExiste) {
            return usuario;
        }

        usuarios.push(usuario);
        this.guardarUsuarios(usuarios);
        return usuario;
    }

    static eliminarUsuarioPorCedula(cedula) {
        // borra un usuario por su cedula
        const usuariosActualizados = this.obtenerUsuarios().filter(usuario => usuario.cedula !== cedula);
        this.guardarUsuarios(usuariosActualizados);
        return usuariosActualizados;
    }

    static buscarUsuarioPorCedula(cedula) {
        // otra forma de buscar por cedula, por si hace falta mas adelante
        return this.obtenerUsuarios().find(usuario => String(usuario.cedula) === String(cedula));
    }

    static actualizarRolUsuario(cedula, nuevoRol) {
        // cambia el rol de un usuario que ya existe
        const usuarios = this.obtenerUsuarios();
        const indice = usuarios.findIndex(usuario => String(usuario.cedula) === String(cedula));

        if (indice === -1) {
            return null;
        }

        const usuario = usuarios[indice];
        const roles = PermisoServicio.normalizarRoles([...(usuario.roles || []), nuevoRol]);
        usuarios[indice] = {
            ...usuario,
            roles
        };

        this.guardarUsuarios(usuarios);
        return usuarios[indice];
    }
}

globalThis.UsuarioStorage = UsuarioStorage;
