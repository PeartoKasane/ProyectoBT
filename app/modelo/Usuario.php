<?php

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

    public function getCedula(): string {
        return $this->cedula;
    }

    public function getNombre(): string {
        return $this->nombre;
    }

    public function getApellido(): string {
        return $this->apellido;
    }

    public function getEmail(): ?string {
        return $this->email;
    }

    public function getClaveHash(): string {
        return $this->claveHash;
    }

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

    public function esAdministrador(): bool {
        return $this->administrador;
    }

    public function esDocente(): bool {
        return $this->docente;
    }

    public function esDireccion(): bool {
        return $this->direccion;
    }

    public function esTecnico(): bool {
        return $this->tecnico;
    }
}
