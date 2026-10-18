<?php
//ENTRYPOINT ÚNICO DEL BACKEND
//Toda petición de la API llega acá con la forma index.php?ruta=<recurso>. El frontend solo contiene archivos .html.

require_once __DIR__ . "/../config/config.php";
require_once RUTA_VISTA . "/RespuestaJson.php";
require_once RUTA_NUCLEO . "/Actividad.php";
require_once RUTA_NUCLEO . "/Sesion.php";

//Si no se solicita ninguna ruta se conserva la página de inicio del sistema
if (!isset($_GET["ruta"])) {
    header("Location: ./paginas/login.html");
    exit;
}

try {
    //Captura los datos que vienen luego de ? en la URL
    $ruta = $_GET["ruta"];
    $metodo = $_SERVER["REQUEST_METHOD"];

    if (!is_string($ruta) || trim($ruta) === "") {
        RespuestaJson::error("La ruta es inválida", 400);
    }

    session_start();

    //RF37: si el navegador avisó que se abandonó la página, se resuelve acá si fue una recarga o un cambio de página (reactiva la sesión) o una salida real
    Sesion::reanudar();

    //RNF4: se programa el registro de la actividad antes de procesar la ruta (el cierre de sesión destruye la cédula)
    Actividad::programarRegistro($metodo, $ruta, $_SESSION["cedula"] ?? null);

    require RUTA_RUTAS . "/api.php";
} catch (Throwable $error) {
    //El detalle queda en el log del servidor, al cliente solo se le informa un error genérico
    error_log((string) $error);
    RespuestaJson::error("Error interno del servidor", 500);
}
