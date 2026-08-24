<?php
session_start();

// Validar sesión y rol
if (!isset($_SESSION['usuario_doc']) || strtolower($_SESSION['usuario_rol']) !== 'docente') {
    header("Location: ../../public/index.html?error=acceso_denegado");
    exit();
}

require_once __DIR__ . "/../modelo/ConectorPDO.php";
require_once __DIR__ . "/../modelo/AccesoDatosUsuario.php";

$conectorPDO = ConectorPDO::getInstancia();
$conexion = $conectorPDO->getConexion();

$accesoDatosUsuario = new AccesoDatosUsuario($conexion);
$usuarioDatos = $accesoDatosUsuario->obtenerPorDocumento($_SESSION['usuario_doc']);

require_once __DIR__ . "/../vista/docente.php";