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
        try {
            const usuariosGuardados = localStorage.getItem(this.CLAVE_USUARIOS);
            if (!usuariosGuardados) {
                return [];
            }

            const usuarios = JSON.parse(usuariosGuardados);
            return Array.isArray(usuarios) ? usuarios : null;
        } catch (error) {
            return null;
        }
    }

    static guardarUsuarios(usuarios) {
        // guarda la lista en localStorage para poder usarla despues
        try {
            localStorage.setItem(this.CLAVE_USUARIOS, JSON.stringify(usuarios));
            return true;
        } catch (error) {
            return false;
        }
    }

    static async inicializarUsuariosPrueba() {
        // si ya hay usuarios cargados, no los duplica
        const usuariosActuales = this.obtenerUsuarios();

        if (!usuariosActuales) {
            return null;
        }

        if (usuariosActuales.length > 0) {
            let huboCambios = false;

            for (const usuario of usuariosActuales) {
                if (usuario.passwordHash && usuario.passwordSalt) {
                    continue;
                }

                const contrasenaAnterior = typeof usuario.password === "string"
                    ? usuario.password
                    : usuario.contrasena;

                if (typeof contrasenaAnterior !== "string") {
                    continue;
                }

                const credenciales = await CredencialServicio.crearCredenciales(contrasenaAnterior);
                usuario.passwordHash = credenciales.hash;
                usuario.passwordSalt = credenciales.salt;
                delete usuario.password;
                delete usuario.contrasena;
                huboCambios = true;
            }

            if (huboCambios && !this.guardarUsuarios(usuariosActuales)) {
                return null;
            }

            return usuariosActuales;
        }

        // normaliza los roles para que queden en la misma forma que usa el sistema
        const usuariosPreparados = [];

        for (const usuario of this.USUARIOS_PRUEBA) {
            const credenciales = await CredencialServicio.crearCredenciales(usuario.contrasena);
            usuariosPreparados.push({
                cedula: usuario.cedula,
                nombre: usuario.nombre,
                roles: PermisoServicio.normalizarRoles(usuario.roles),
                passwordHash: credenciales.hash,
                passwordSalt: credenciales.salt
            });
        }

        return this.guardarUsuarios(usuariosPreparados) ? usuariosPreparados : null;
    }

    static async buscarUsuarioPorCredenciales(cedula, contrasena) {
        const usuarios = this.obtenerUsuarios();
        if (!usuarios) {
            throw new Error("No se pudieron leer los usuarios.");
        }

        const usuario = usuarios.find(item => String(item.cedula) === String(cedula));
        if (!usuario || !usuario.passwordHash || !usuario.passwordSalt) {
            return null;
        }

        const coincide = await CredencialServicio.verificarContrasena(
            contrasena,
            usuario.passwordHash,
            usuario.passwordSalt
        );

        return coincide ? usuario : null;
    }

    static async agregarUsuario(usuario) {
        // agrega usuario si no existe ya por cedula
        const usuarios = this.obtenerUsuarios();
        if (!usuarios) {
            return null;
        }

        const yaExiste = usuarios.some(item => String(item.cedula) === String(usuario.cedula));

        if (yaExiste) {
            return usuario;
        }

        const usuarioPreparado = {
            cedula: usuario.cedula,
            nombre: usuario.nombre,
            roles: usuario.roles,
            passwordHash: usuario.passwordHash,
            passwordSalt: usuario.passwordSalt
        };

        const contrasenaAnterior = typeof usuario.password === "string"
            ? usuario.password
            : usuario.contrasena;

        if (!usuarioPreparado.passwordHash && typeof contrasenaAnterior === "string") {
            const credenciales = await CredencialServicio.crearCredenciales(contrasenaAnterior);
            usuarioPreparado.passwordHash = credenciales.hash;
            usuarioPreparado.passwordSalt = credenciales.salt;
        }

        usuarios.push(usuarioPreparado);
        return this.guardarUsuarios(usuarios) ? usuarioPreparado : null;
    }

    static eliminarUsuarioPorCedula(cedula) {
        // borra un usuario por su cedula
        const usuarios = this.obtenerUsuarios();
        if (!usuarios) {
            return null;
        }

        const usuariosActualizados = usuarios.filter(usuario => usuario.cedula !== cedula);
        return this.guardarUsuarios(usuariosActualizados) ? usuariosActualizados : null;
    }

    static buscarUsuarioPorCedula(cedula) {
        // otra forma de buscar por cedula, por si hace falta mas adelante
        const usuarios = this.obtenerUsuarios();
        return usuarios ? usuarios.find(usuario => String(usuario.cedula) === String(cedula)) : null;
    }

    static actualizarRolUsuario(cedula, nuevoRol) {
        // cambia el rol de un usuario que ya existe
        const usuarios = this.obtenerUsuarios();
        if (!usuarios) {
            return null;
        }

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

        return this.guardarUsuarios(usuarios) ? usuarios[indice] : null;
    }
}

globalThis.UsuarioStorage = UsuarioStorage;
