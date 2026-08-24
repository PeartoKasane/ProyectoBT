<?php
session_start();
require_once __DIR__ . '/../modelo/Login.php';

// Normalizar string de roles a array (acepta JSON, coma-sep o string simple)
function parseRolesFromString($raw) {
    if (empty($raw)) return [];
    // si viene un JSON array
    $trim = trim($raw);
    if ((strpos($trim, '[') === 0 && strrpos($trim, ']') === strlen($trim)-1) || (strpos($trim, '"') !== false && strpos($trim, '[') !== false)) {
        $decoded = json_decode($trim, true);
        if (is_array($decoded)) return array_map('trim', $decoded);
    }
    // si viene separado por comas
    if (strpos($raw, ',') !== false) {
        $parts = array_map('trim', explode(',', $raw));
        return array_filter($parts, function($v){ return $v !== ''; });
    }
    // string simple
    return [$raw];
}

// prioridad para elegir rol principal (debe coincidir con PermisoServicio)
$PRIORIDAD = ['Administrador', 'Dirección', 'Técnico', 'Docente'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula   = $_POST['ci'] ?? $_POST['documento'] ?? '';
    $password = $_POST['contrasena'] ?? $_POST['clave'] ?? '';

    if (empty($cedula) || empty($password)) {
        // si es AJAX/Fetch, responder JSON
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (strpos($accept, 'application/json') !== false) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'campos_vacios']);
            exit();
        }
        header("Location: ../../public/index.html?error=campos_vacios");
        exit();
    }

    $loginService = new Login();
    $usuario = $loginService->autenticar($cedula, $password);

    if ($usuario) {
        // Parseo de roles y elección de rol principal
        $rawRoles = $usuario->getRol();
        $rolesList = parseRolesFromString($rawRoles);
        // normalizar mayúsculas iniciales y eliminar duplicados
        $rolesClean = [];
        foreach ($rolesList as $r) {
            $rtrim = trim($r);
            if ($rtrim === '') continue;
            // forzar acentos/capitalización simples (mantener como en JS)
            $rnorm = mb_convert_case($rtrim, MB_CASE_TITLE, "UTF-8");
            if (!in_array($rnorm, $rolesClean)) $rolesClean[] = $rnorm;
        }

        // elegir rol principal según prioridad
        $rolPrincipal = $rolesClean[0] ?? '';
        foreach ($PRIORIDAD as $p) {
            if (in_array($p, $rolesClean)) {
                $rolPrincipal = $p;
                break;
            }
        }

        // guardar en sesión (seguridad en servidor)
        $_SESSION['usuario_doc']    = $usuario->getDocumento();
        $_SESSION['usuario_nombre'] = $usuario->getNombre();
        $_SESSION['usuario_email']  = $usuario->getEmail();
        $_SESSION['usuario_roles']  = $rolesClean;
        $_SESSION['usuario_rol']    = $rolPrincipal;

        // si la petición espera JSON (cliente SPA), devolver datos básicos
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (strpos($accept, 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => [
                    'documento' => $usuario->getDocumento(),
                    'nombre'    => $usuario->getNombre(),
                    'email'     => $usuario->getEmail(),
                    'roles'     => $rolesClean
                ]
            ]);
            exit();
        }

        // Petición tradicional: redirigir según rol principal
        switch (strtolower($rolPrincipal)) {
            case 'administrador':
                header("Location: cargarAdministrador.php");
                break;
            case 'docente':
                header("Location: cargarDocente.php");
                break;
            case 'dirección':
            case 'direccion':
                header("Location: cargarDireccion.php");
                break;
            default:
                header("Location: ../../public/index.html?error=rol_invalido");
                break;
        }
        exit();
    } else {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (strpos($accept, 'application/json') !== false) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'credenciales_invalidas']);
            exit();
        }
        header("Location: ../../public/index.html?error=credenciales_invalidas");
        exit();
    }
} else {
    header("Location: ../../public/index.html");
    exit();
}