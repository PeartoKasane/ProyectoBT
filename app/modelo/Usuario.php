<?php

/**
 * Clase que representa a un usuario del sistema con su estado de cuenta, su sesión y sus roles.
 *
 * Los roles no se guardan como texto: cada uno es una tabla (ADMINISTRADOR, DOCENTE, DIRECCION y TECNICO)
 * y UsuarioDAO::buscarUsuario() indica en cuáles figura la cédula.
 */
class Usuario {
    //RF37: si pasó más de este tiempo desde que se inició la sesión, se considera vencida sola
    private const HORAS_VENCIMIENTO_SESION = 8;

    private string $cedula;
    private string $nombre;
    private string $apellido;
    private ?string $email;
    private string $claveHash;
    private bool $activo;
    private bool $sesionActiva;
    private ?string $inicioSesion;
    private bool $administrador;
    private bool $docente;
    private bool $direccion;
    private bool $tecnico;

    /**
     * Constructor parametrizado que recibe todos los datos del usuario (se instancia desde UsuarioDAO::buscarUsuario()).
     * @param string $cedula Cédula del usuario sin puntos ni guiones.
     * @param string $nombre Nombre del usuario.
     * @param string $apellido Apellido del usuario.
     * @param string|null $email Correo electrónico, null si no lo registró.
     * @param string $claveHash Hash de la contraseña (password_hash).
     * @param bool $activo FALSE si la cuenta está deshabilitada (RF36).
     * @param bool $sesionActiva TRUE si quedó marcada una sesión iniciada (RF37).
     * @param string|null $inicioSesion Momento en que se inició la sesión activa, null si no hay.
     * @param bool $administrador TRUE si figura en la tabla ADMINISTRADOR.
     * @param bool $docente TRUE si figura en la tabla DOCENTE.
     * @param bool $direccion TRUE si figura en la tabla DIRECCION.
     * @param bool $tecnico TRUE si figura en la tabla TECNICO.
     */
    public function __construct(
        string $cedula,
        string $nombre,
        string $apellido,
        ?string $email,
        string $claveHash,
        bool $activo,
        bool $sesionActiva,
        ?string $inicioSesion,
        bool $administrador,
        bool $docente,
        bool $direccion,
        bool $tecnico
    ) {
        $this->cedula = $cedula;
        $this->nombre = $nombre;
        $this->apellido = $apellido;
        $this->email = $email;
        $this->claveHash = $claveHash;
        $this->activo = $activo;
        $this->sesionActiva = $sesionActiva;
        $this->inicioSesion = $inicioSesion;
        $this->administrador = $administrador;
        $this->docente = $docente;
        $this->direccion = $direccion;
        $this->tecnico = $tecnico;
    }

    /**
     * @return string La cédula del usuario.
     */
    public function getCedula(): string {
        return $this->cedula;
    }

    /**
     * @return string El nombre del usuario.
     */
    public function getNombre(): string {
        return $this->nombre;
    }

    /**
     * @return string El apellido del usuario.
     */
    public function getApellido(): string {
        return $this->apellido;
    }

    /**
     * @return string|null El correo electrónico, null si no lo registró.
     */
    public function getEmail(): ?string {
        return $this->email;
    }

    /**
     * @return string El hash de la contraseña, nunca debe enviarse al cliente.
     */
    public function getClaveHash(): string {
        return $this->claveHash;
    }

    /**
     * RF36: indica si la cuenta está habilitada.
     * @return bool FALSE si el administrador la deshabilitó.
     */
    public function esActivo(): bool {
        return $this->activo;
    }

    /**
     * RF37: indica si el usuario tiene una sesión activa que todavía no venció.
     * Si sesionActiva está en TRUE pero ya pasaron más de 8 horas desde que empezó,
     * se la considera vencida (por ejemplo, cerró el navegador sin avisar).
     */
    public function tieneSesionActiva(): bool {
        if (!$this->sesionActiva) {
            return false;
        }

        if ($this->inicioSesion === null) {
            return true;
        }

        $inicio = new DateTime($this->inicioSesion);
        $ahora = new DateTime();
        $horasTranscurridas = ($ahora->getTimestamp() - $inicio->getTimestamp()) / 3600;

        return $horasTranscurridas < self::HORAS_VENCIMIENTO_SESION;
    }

    /**
     * @return bool TRUE si el usuario tiene el rol administrador.
     */
    public function esAdministrador(): bool {
        return $this->administrador;
    }

    /**
     * @return bool TRUE si el usuario tiene el rol docente.
     */
    public function esDocente(): bool {
        return $this->docente;
    }

    /**
     * @return bool TRUE si el usuario tiene el rol dirección.
     */
    public function esDireccion(): bool {
        return $this->direccion;
    }

    /**
     * @return bool TRUE si el usuario tiene el rol técnico.
     */
    public function esTecnico(): bool {
        return $this->tecnico;
    }
}
