<?php

require_once __DIR__ . "/../modelo/ConectorPDO.php";
require_once __DIR__ . "/../modelo/AccesoDatosUsuario.php";
require_once __DIR__ . "/../modelo/Usuario.php";
require_once __DIR__ . "/../modelo/Login.php";

//Comprueba que el formulario haya sido enviado mediante POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $mensaje = "Acceso Denegado: Petición incorrecta";
    header("Location: login.php?" . "error=" . $mensaje);
    exit;
}

//Recupera las credenciales provenientes del formulario
$cedula = trim($_POST["cedula"] ?? "");
$clave = $_POST["clave"] ?? "";

$conectorPDO = new ConectorPDO ("localhost", "leandro", "123", "test");
$conexion = $conectorPDO->establecerConexion();

    $accesoDatosUsuario = new AccesoDatosUsuario($conexion);
    $login = new Login($accesoDatosUsuario);

$conectorPDO->desconectar();

$usuario = $login->autenticar($cedula, $clave);

//Si las credenciales no coinciden, muestra el error y detiene el proceso
if ($usuario === null) {
    $mensaje = "Acceso Denegado: La cédula o la contraseña son incorrectas.";
    header("Location: login.php?" . "error=" . $mensaje);
    exit;
}

//Solo se encuentra implementado el rol administrador
if (!$usuario->esAdministrador()) {
    $mensaje = "Acceso Denegado: El usuario no tiene acceso al panel de administración.";
    header("Location: login.php?" . "error=" . $mensaje);
    exit;
}

session_start();
session_regenerate_id(true);

$_SESSION["cedula"] = $usuario->getCedula();
$_SESSION["administrador"] = $usuario->esAdministrador();
$_SESSION["logistica"] = $usuario->esLogistica();

if ($_SESSION["administrador"] && $_SESSION["logistica"]) {
    header("Location: panelRoles.php");
} elseif ($_SESSION["logistica"]) {
    header("Location: logistica.php");
} elseif ($_SESSION["administrador"]) {
    header("Location: administrador.php");
}

exit;

?>