<?php
session_start();

// Validar sesión y rol
if (!isset($_SESSION['usuario_doc']) || strtolower($_SESSION['usuario_rol']) !== 'administrador') {
    header("Location: ../../public/Login.html?error=acceso_denegado");
    exit();
}

require_once __DIR__ . "/../modelo/ConectorPDO.php";
require_once __DIR__ . "/../modelo/AccesoDatosUsuario.php";

$conectorPDO = ConectorPDO::getInstancia();
$conexion = $conectorPDO->getConexion();

$accesoDatosUsuario = new AccesoDatosUsuario($conexion);
$usuarios = $accesoDatosUsuario->listarUsuarios(); // Ejecuta DQL/SELECT general

require_once __DIR__ . "/../vista/administrador.php";