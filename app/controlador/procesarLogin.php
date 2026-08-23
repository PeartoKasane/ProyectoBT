<?php
session_start();
require_once __DIR__ . '/../modelo/Login.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula   = $_POST['ci'] ?? $_POST['documento'] ?? '';
    $password = $_POST['contrasena'] ?? $_POST['clave'] ?? '';

    if (empty($cedula) || empty($password)) {
        header("Location: ../../public/Login.html?error=campos_vacios");
        exit();
    }

    $loginService = new Login();
    $usuario = $loginService->autenticar($cedula, $password);

    if ($usuario) {
        $_SESSION['usuario_doc']    = $usuario->getDocumento();
        $_SESSION['usuario_nombre'] = $usuario->getNombre();
        $_SESSION['usuario_rol']    = $usuario->getRol();

        // Redirección según rol a sus respectivos controladores SGRSI
        switch (strtolower($usuario->getRol())) {
            case 'administrador':
                header("Location: cargarAdministrador.php");
                break;
            case 'docente':
                header("Location: cargarDocente.php");
                break;
            case 'direccion':
            case 'dirección':
                header("Location: cargarDireccion.php");
                break;
            default:
                header("Location: ../../public/Login.html?error=rol_invalido");
                break;
        }
        exit();
    } else {
        header("Location: ../../public/Login.html?error=credenciales_invalidas");
        exit();
    }
} else {
    header("Location: ../../public/Login.html");
    exit();
}