/*
  este archivo es la servicio de permisos y roles.
  aca va la logica del JS y no me gusta romperlo.
  pero por ahora sirve para que el sistema funcione.
*/

class PermisoServicio {
    // aca se pone la lista de roles que nos interesan
    // para no mezclar "Dirección" con otras cosas raro
    static ROLES_VALIDOS = [
        'Administrador',
        'Dirección',
        'Técnico',
        'Docente'
    ];

    // cada HTML tiene un rol que tiene que tener para abrirse
    // esto ayuda a no andar repitiendo ifs por todos lados
    static PERMISOS_POR_VENTANA = {
        'Admin.html': 'Administrador',
        'Tecnico.html': 'Técnico',
        'Direccion2.html': 'Dirección',
        'Docente.html': 'Docente'
    };

    // clave de la sesion en localStorage
    static SESION_KEY = 'sesionUsuario';

    // esto define cual rol tiene prioridad cuando el usuario tiene dos
    static PRIORIDAD_POR_ROL = [
        'Administrador',
        'Dirección',
        'Técnico',
        'Docente'
    ];

    // esto sirve para saber que pantallas se muestran segun el rol
    static VENTANAS_POR_ROL = {
        Administrador: ['Administrador'],
        Dirección: ['Dirección'],
        Técnico: ['Técnico'],
        Docente: ['Docente']
    };

    // mapeo para redirigir al rol principal
    static RUTA_POR_VENTANA = {
        Administrador: 'Admin.html',
        Dirección: 'Direccion2.html',
        Técnico: 'Tecnico.html',
        Docente: 'Docente.html'
    };

    static normalizarRoles(roles) {
        // basicamente limpia la lista y deja solo roles validos
        const lista = Array.isArray(roles) ? roles : [];
        const rolesValidos = lista
            .filter(rol => Boolean(rol))
            .map(rol => String(rol).trim())
            .filter(rol => this.ROLES_VALIDOS.includes(rol));

        const sinDuplicados = [...new Set(rolesValidos)];
        return sinDuplicados.slice(0, 2);
    }

    static validarRoles(roles) {
        // esta funcion dice si la lista de roles es valida
        const lista = Array.isArray(roles) ? roles : [];
        const rolesValidos = lista
            .filter(rol => Boolean(rol))
            .map(rol => String(rol).trim())
            .filter(rol => this.ROLES_VALIDOS.includes(rol));

        const sinDuplicados = [...new Set(rolesValidos)];
        return sinDuplicados.length > 0 && sinDuplicados.length <= 2;
    }

    static obtenerSesionUsuario() {
        // lee la sesion que se guardo en localStorage
        try {
            const dato = localStorage.getItem(this.SESION_KEY);
            return dato ? JSON.parse(dato) : null;
        } catch (error) {
            return null;
        }
    }

    static guardarSesionUsuario(usuario) {
        // guarda lo minimo: cedula, nombre y roles
        if (!usuario) {
            return;
        }

        const datos = {
            cedula: usuario.cedula || '',
            nombre: usuario.nombre || '',
            roles: this.normalizarRoles(usuario.roles)
        };

        localStorage.setItem(this.SESION_KEY, JSON.stringify(datos));
    }

    static limpiarSesionUsuario() {
        // por si hay que cerrar sesion
        localStorage.removeItem(this.SESION_KEY);
    }

    static obtenerRolesUsuario(usuario) {
        // devuelve los roles limpios del usuario
        if (!usuario || !Array.isArray(usuario.roles)) {
            return [];
        }

        return this.normalizarRoles(usuario.roles);
    }

    static obtenerRolPrincipal(roles) {
        // devuelve el rol mas importante segun la prioridad
        const listaRoles = this.normalizarRoles(roles);

        for (const rol of this.PRIORIDAD_POR_ROL) {
            if (listaRoles.includes(rol)) {
                return rol;
            }
        }

        return listaRoles[0] || '';
    }

    static obtenerRutaVentana(rol) {
        // cambia el nombre del rol por la pagina que corresponde
        return this.RUTA_POR_VENTANA[rol] || 'index.html';
    }

    static puedeAccederVentana(roles, nombreVentana) {
        // revisa si el usuario tiene el rol que pide esa ventana
        const listaRoles = this.normalizarRoles(roles);

        if (!nombreVentana) {
            return false;
        }

        const rolRequerido = this.PERMISOS_POR_VENTANA[nombreVentana] || nombreVentana;
        return listaRoles.includes(rolRequerido);
    }

    static puedeVerTickets(roles) {
        // para no hacer demasiadas validaciones repetidas
        const listaRoles = this.normalizarRoles(roles);
        return ['Administrador', 'Técnico', 'Dirección', 'Docente'].some(rol => listaRoles.includes(rol));
    }

    static puedeVerSolicitudes(roles) {
        // y esto para solicitudes
        const listaRoles = this.normalizarRoles(roles);
        return ['Administrador', 'Técnico', 'Dirección', 'Docente'].some(rol => listaRoles.includes(rol));
    }

    static puedeAdministrarUsuarios(roles) {
        // admin tiene permisos especiales
        const listaRoles = this.normalizarRoles(roles);
        return listaRoles.includes('Administrador');
    }

    static puedeModificarTickets(roles) {
        // admin puede cambiar tickets
        const listaRoles = this.normalizarRoles(roles);
        return listaRoles.includes('Administrador');
    }

    static puedeModificarSolicitudes(roles) {
        // admin tambien maneja solicitudes
        const listaRoles = this.normalizarRoles(roles);
        return listaRoles.includes('Administrador');
    }

    static puedeCambiarEstados(roles) {
        // deberia ser admin, como dice el nombre
        const listaRoles = this.normalizarRoles(roles);
        return listaRoles.includes('Administrador');
    }

    static puedeMostrarSelectorDeVentanas(roles) {
        // si tiene dos roles, puede cambiar entre pantallas
        const listaRoles = this.normalizarRoles(roles);
        return listaRoles.length > 1;
    }

    static obtenerVentanasDisponibles(roles) {
        // devuelve las ventanas que si puede abrir
        const listaRoles = this.normalizarRoles(roles);
        return listaRoles
            .filter(rol => this.VENTANAS_POR_ROL[rol])
            .map(rol => this.VENTANAS_POR_ROL[rol][0]);
    }

    static validarAccesoPagina(nombreVentana) {
        // esto chequea si el usuario puede entrar a una pagina concreta
        const usuario = this.obtenerSesionUsuario();

        if (!usuario) {
            return true;
        }

        const roles = this.normalizarRoles(usuario.roles);
        const rolRequerido = this.PERMISOS_POR_VENTANA[nombreVentana] || nombreVentana;

        if (!this.puedeAccederVentana(roles, rolRequerido)) {
            alert('No tiene permiso para acceder a esta ventana.');
            const rolPrincipal = this.obtenerRolPrincipal(roles);
            const destino = this.obtenerRutaVentana(rolPrincipal);
            window.location.href = destino || 'index.html';
            return false;
        }

        return true;
    }
}

globalThis.PermisoServicio = PermisoServicio;
