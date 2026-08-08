<?php

require_once __DIR__ . "/../modelo/ConectorPDO.php";
require_once __DIR__ . "/../modelo/AccesoDatosUsuario.php";
require_once __DIR__ . "/../modelo/Usuario.php";
require_once __DIR__ . "/../modelo/Login.php";

//Comprueba que el formulario haya sido enviado mediante POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $mensaje = "Acceso Denegado: Petición incorrecta";

    //urlencode es un método que toma un string y lo retorna con un formato funcional para ser enviado a través de una URL
    header("Location: login.php?error=" . urlencode($mensaje));
    exit;
}

//Recupera las credenciales provenientes del formulario
$cedula = trim($_POST["cedula"] ?? "");
$clave = $_POST["clave"] ?? "";

//Credenciales hardcodeadas, en un futuro van a colocarse en archivos aislados o variables de entorno
$conectorPDO = new ConectorPDO ("localhost:3306", "leandro", "123", "test");
$conexion = $conectorPDO->establecerConexion();

    $accesoDatosUsuario = new AccesoDatosUsuario($conexion);
    $login = new Login($accesoDatosUsuario);
    $usuario = $login->autenticar($cedula, $clave);

$conectorPDO->desconectar();

//Si las credenciales no coinciden, muestra el error y detiene el proceso
if ($usuario === null) {
    $mensaje = "Acceso Denegado: La cédula o la contraseña son incorrectas.";
    header("Location: login.php?error=" . urlencode($mensaje));
    exit;
}

//Solo se encuentra implementado el rol administrador
if (!$usuario->esAdministrador()) {
    $mensaje = "Acceso Denegado: El usuario no tiene acceso al panel de administración.";
    header("Location: login.php?error=" . urlencode($mensaje));
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