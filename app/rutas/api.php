<?php

require_once RUTA_CONTROLADOR . "/LoginController.php";
require_once RUTA_CONTROLADOR . "/UsuarioController.php";
require_once RUTA_CONTROLADOR . "/InventarioController.php";
require_once RUTA_CONTROLADOR . "/TicketController.php";
require_once RUTA_CONTROLADOR . "/SolicitudController.php";
require_once RUTA_CONTROLADOR . "/PrestamoController.php";
require_once RUTA_CONTROLADOR . "/ReporteController.php";
require_once RUTA_CONTROLADOR . "/ActividadController.php";

//Espacio donde se encuentran los endpoints que establecen la comunicación con el controlador
//Cada ruta se invoca desde el frontend como index.php?ruta=<nombre>
//Si la ruta no coincide con ninguna, el default responde 404 en JSON
switch ($ruta) {
    case "login":
        $controlador = new LoginController();
        $controlador->autenticar($metodo);
        break;
    case "logout":
        $controlador = new LoginController();
        $controlador->cerrarSesion($metodo);
        break;
    case "sesion":
        $controlador = new LoginController();
        $controlador->obtenerSesion($metodo);
        break;
    case "usuarios":
        $controlador = new UsuarioController();
        $controlador->gestionar($metodo);
        break;
    case "inventario":
        $controlador = new InventarioController();
        $controlador->gestionar($metodo);
        break;
    case "tickets":
        $controlador = new TicketController();
        $controlador->gestionar($metodo);
        break;
    case "solicitudes":
        $controlador = new SolicitudController();
        $controlador->gestionar($metodo);
        break;
    case "prestamos":
        $controlador = new PrestamoController();
        $controlador->gestionar($metodo);
        break;
    case "reportes":
        $controlador = new ReporteController();
        $controlador->gestionar($metodo);
        break;
    case "actividad":
        $controlador = new ActividadController();
        $controlador->gestionar($metodo);
        break;
    default:
        RespuestaJson::error("Ruta no encontrada", 404);
}
