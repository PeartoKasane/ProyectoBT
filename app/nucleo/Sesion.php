<?php

require_once RUTA_MODELO . "/Usuario.php";
require_once RUTA_MODELO . "/UsuarioDAO.php";
require_once RUTA_VISTA . "/RespuestaJson.php";
require_once RUTA_NUCLEO . "/Conexion.php";

/**
 * Clase estática que gestiona la sesión PHP del usuario autenticado.
 *
 * Guarda en $_SESSION las claves que usan los controladores para verificar el rol, por lo que una sesión
 * iniciada desde la API es la que verifican todos los controladores.
 */
class Sesion {
    //Tiempo que se espera, tras avisar que el usuario abandonó la página, a que vuelva a pedir algo (recarga o cambio de página)
    private const SEGUNDOS_GRACIA = 30;

    /**
     * Inicia la sesión del usuario y guarda sus datos y roles.
     * @param Usuario $usuario El usuario ya autenticado.
     */
    public static function iniciar(Usuario $usuario): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        //Cambia el identificador de sesión para evitar la fijación de sesión (session fixation)
        session_regenerate_id(true);

        $_SESSION["cedula"] = $usuario->getCedula();
        $_SESSION["nombre"] = $usuario->getNombre();
        $_SESSION["apellido"] = $usuario->getApellido();
        $_SESSION["administrador"] = $usuario->esAdministrador();
        $_SESSION["docente"] = $usuario->esDocente();
        $_SESSION["direccion"] = $usuario->esDireccion();
        $_SESSION["tecnico"] = $usuario->esTecnico();
    }

    /**
     * Destruye la sesión, incluida la cookie del navegador.
     */
    public static function cerrar(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION = [];

        //Elimina también la cookie de sesión del navegador. https://www.php.net/manual/es/function.session-destroy.php
        if (ini_get("session.use_cookies")) {
            $parametros = session_get_cookie_params();

            setcookie(
                session_name(),
                "",
                time() - 42000,
                $parametros["path"],
                $parametros["domain"],
                $parametros["secure"],
                $parametros["httponly"]
            );
        }

        session_destroy();
    }

    /**
     * Resuelve una salida pendiente (ver LoginController::registrarSalida()). Se llama al inicio de cada petición.
     *
     * - Si la petición llega dentro de SEGUNDOS_GRACIA, el usuario no se fue (recargó o cambió de página):
     *   se vuelve a marcar su sesión como activa en la base de datos.
     * - Si llega después, la sesión ya se consideraba cerrada: se destruye y la petición queda sin sesión (401).
     */
    public static function reanudar(): void {
        if (!isset($_SESSION["salidaPendiente"])) {
            return;
        }

        $salida = (int) $_SESSION["salidaPendiente"];
        unset($_SESSION["salidaPendiente"]);

        if (!isset($_SESSION["cedula"])) {
            return;
        }

        if (time() - $salida > self::SEGUNDOS_GRACIA) {
            self::cerrar();
            return;
        }

        $conexion = Conexion::intentarConectar();

        if ($conexion !== null) {
            $dao = new UsuarioDAO($conexion);
            $dao->establecerSesionActiva($_SESSION["cedula"], true);
            Conexion::desconectar();
        }
    }

    /**
     * Verifica que haya una sesión iniciada y que el usuario tenga el rol indicado.
     * Si no es así, responde error 401 o 403 y detiene la ejecución.
     * @param string $rol Clave del rol en $_SESSION: "administrador", "docente", "direccion" o "tecnico".
     */
    public static function verificarRol(string $rol): void {
        if (!isset($_SESSION["cedula"])) {
            RespuestaJson::error("Acceso denegado: sesión no iniciada", 401);
        }

        if (!($_SESSION[$rol] ?? false)) {
            RespuestaJson::error("Acceso denegado: rol incorrecto", 403);
        }
    }
}
