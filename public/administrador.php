<?php

session_start();

if (!isset($_SESSION["cedula"])) {
    $mensaje = "Acceso Denegado: Sesión no iniciada";
    header("Location: login.php?error=" . urlencode($mensaje));
    exit;
}

if ( !isset($_SESSION["administrador"]) || !($_SESSION["administrador"] ?? false) ) {
    $mensaje = "Acceso Denegado: Rol incorrecto";
    header("Location: login.php?error=" . urlencode($mensaje));
    exit;
}

require_once __DIR__ . "/../app/controlador/cargarAdministrador.php";

?>