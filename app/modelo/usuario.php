<?php
class Usuario {
    private $documento;
    private $nombre;
    private $apellido;
    private $email;
    private $claveHash;
    private $rol;

    public function __construct($documento, $nombre, $apellido, $email, $claveHash, $rol) {
        $this->documento = $documento;
        $this->nombre    = $nombre;
        $this->apellido  = $apellido;
        $this->email     = $email;
        $this->claveHash = $claveHash;
        $this->rol       = $rol;
    }

    public function getDocumento() { return $this->documento; }
    public function getNombre()    { return $this->nombre; }
    public function getApellido()  { return $this->apellido; }
    public function getEmail()     { return $this->email; }
    public function getClaveHash() { return $this->claveHash; }
    
    // Alias para compatibilidad con el servicio de Login/Autenticación
    public function getClave()     { return $this->claveHash; } 
    
    public function getRol()       { return $this->rol; }
}