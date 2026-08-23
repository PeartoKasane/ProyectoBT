<?php
require_once __DIR__ . '/AccesoDatosUsuario.php';

class Login {
    private $accesoDatos;

    public function __construct() {
        $this->accesoDatos = new AccesoDatosUsuario();
    }

    public function autenticar($cedula, $clave) {
        $usuario = $this->accesoDatos->obtenerPorDocumento($cedula);

        if ($usuario) {
            // Verifica la contraseña ingresada contra el hash BCRYPT almacenado en la BD
            if (password_verify($clave, $usuario->getClave())) {
                return $usuario;
            }
        }
        return null;
    }
}