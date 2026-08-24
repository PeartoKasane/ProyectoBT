<?php
session_start();

// Validar sesión y rol
if (!isset($_SESSION['usuario_doc']) || strtolower($_SESSION['usuario_rol']) !== 'dirección' && strtolower($_SESSION['usuario_rol']) !== 'direccion') {
    header("Location: ../../public/index.html?error=acceso_denegado");
    exit();
}

require_once __DIR__ . "/../modelo/ConectorPDO.php";
require_once __DIR__ . "/../modelo/AccesoDatosUsuario.php";

$conectorPDO = ConectorPDO::getInstancia();
$conexion = $conectorPDO->getConexion();

$accesoDatosUsuario = new AccesoDatosUsuario($conexion);
$usuarios = $accesoDatosUsuario->listarUsuarios(); // Consulta DQL institucional

require_once __DIR__ . "/../vista/direccion.php";