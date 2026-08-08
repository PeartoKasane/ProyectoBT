<?php

class Usuario {
    private string $cedula;
    private string $claveHash;
    private bool $sesionActiva;
    private bool $administrador;
    private bool $logistica;

    public function __construct(string $cedula, string $claveHash, bool $sesionActiva, bool $administrador, bool $logistica) {
        $this->cedula = $cedula;
        $this->claveHash = $claveHash;
        $this->sesionActiva = $sesionActiva;
        $this->administrador = $administrador;
        $this->logistica = $logistica;
    }

    public function getCedula(): string {
        return $this->cedula;
    }

    public function getClaveHash(): string {
        return $this->claveHash;
    }

    public function tieneSesionActiva(): bool {
        return $this->sesionActiva;
    }

    public function esAdministrador(): bool {
        return $this->administrador;
    }

    public function esLogistica(): bool {
        return $this->logistica;
    }
}

?>