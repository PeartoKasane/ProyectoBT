<?php
session_start();

// Validar que la sesión sea de un Administrador
if (!isset($_SESSION['usuario_doc']) || strtolower($_SESSION['usuario_rol']) !== 'administrador') {
    header("Location: ../../public/index.html?error=acceso_denegado");
    exit();
}

require_once __DIR__ . "/../modelo/ConectorPDO.php";
require_once __DIR__ . "/../modelo/AccesoDatosUsuario.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula   = trim($_POST['cedula'] ?? '');
    $nombre   = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $cargo    = trim($_POST['cargo'] ?? '');
    // Se asigna una contraseña por defecto (su propia cédula) si es un alta
    $clavePlana = $cedula;

    if (empty($cedula) || empty($nombre) || empty($apellido) || empty($cargo)) {
        header("Location: cargarAdministrador.php?error=campos_incompletos");
        exit();
    }

    $accesoDatos = new AccesoDatosUsuario();
    $exito = $accesoDatos->guardarEmpleado($cedula, $nombre, $apellido, $cargo, $clavePlana);

    if ($exito) {
        header("Location: cargarAdministrador.php?mensaje=guardado_correcto");
    } else {
        header("Location: cargarAdministrador.php?error=error_guardar");
    }
    exit();
} else {
    header("Location: cargarAdministrador.php");
    exit();
}