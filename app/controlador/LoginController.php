<?php

require_once RUTA_MODELO . "/ConectorPDO.php";
require_once RUTA_MODELO . "/UsuarioDAO.php";
require_once RUTA_VISTA . "/RespuestaJson.php";

require_once RUTA_NUCLEO . "/Conexion.php";
require_once RUTA_NUCLEO . "/Token.php";
require_once RUTA_NUCLEO . "/Sesion.php";

/**
 * Controlador de la API que gestiona el inicio y el cierre de sesión.
 * Responde siempre en JSON. Atiende las rutas login, logout y sesion.
 */
class LoginController {

    /**
     * Autentica al usuario con las credenciales recibidas en formato JSON.
     * @param string $metodo Método HTTP de la petición, solo se acepta POST.
     */
    public function autenticar(string $metodo): void {
        if ($metodo !== "POST") {
            RespuestaJson::error("Método no permitido", 405);
        }

        //Los datos no llegan por $_POST sino como texto JSON en el cuerpo de la petición
        $datos = json_decode(file_get_contents("php://input"), true);

        if (!is_array($datos)) {
            RespuestaJson::error("JSON inválido", 400);
        }

        $cedula = is_string($datos["cedula"] ?? null) ? trim($datos["cedula"]) : "";
        $clave = is_string($datos["clave"] ?? null) ? $datos["clave"] : "";

        if ($cedula === "" || $clave === "") {
            RespuestaJson::error("Cédula y contraseña son obligatorias", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new UsuarioDAO($conexion);
        $usuario = $dao->buscarUsuario($cedula);

        //Mismo mensaje para cédula inexistente y contraseña incorrecta, para no revelar qué cédulas existen
        if ($usuario === null || !password_verify($clave, $usuario->getClaveHash())) {
            Conexion::desconectar();
            RespuestaJson::error("Usuario o credenciales incorrectas", 401);
        }

        //RF36: una cuenta deshabilitada no puede iniciar sesión, aunque la contraseña sea correcta
        if (!$usuario->esActivo()) {
            Conexion::desconectar();
            RespuestaJson::error("Acceso denegado: la cuenta se encuentra deshabilitada", 403);
        }

        //RF37: no se permite una segunda sesión mientras la anterior siga activa
        if ($usuario->tieneSesionActiva()) {
            Conexion::desconectar();
            RespuestaJson::error("Acceso denegado: el usuario ya tiene una sesión activa", 409);
        }

        //Orden de prioridad para elegir el panel
        $rol = $this->determinarRol($usuario);

        if ($rol === null) {
            Conexion::desconectar();
            RespuestaJson::error("Acceso denegado: el usuario no tiene un rol asignado", 403);
        }

        Sesion::iniciar($usuario);
        Token::generarTokenCSRF();

        //RF37: registra que el usuario tiene una sesión activa, antes de desconectar
        $dao->establecerSesionActiva($usuario->getCedula(), true);

        Conexion::desconectar();

        //El token se devuelve en el cuerpo para que el JavaScript lo guarde y lo envíe en cada petición que modifique datos
        RespuestaJson::exito([
            "mensaje" => "Sesión iniciada correctamente",
            "csrfToken" => $_SESSION["csrfToken"],
            "cedula" => $_SESSION["cedula"],
            "rol" => $rol
        ]);
    }

    /**
     * Informa los datos de la sesión iniciada (cédula, nombre, apellido, correo y rol).
     * Lo utilizan las páginas HTML para saber quién está logueado y qué opciones mostrarle.
     * @param string $metodo Método HTTP de la petición, solo se acepta GET.
     */
    public function obtenerSesion(string $metodo): void {
        if ($metodo !== "GET") {
            RespuestaJson::error("Método no permitido", 405);
        }

        if (!isset($_SESSION["cedula"])) {
            RespuestaJson::error("Acceso denegado: sesión no iniciada", 401);
        }

        $conexion = Conexion::conectar();
        $dao = new UsuarioDAO($conexion);
        $usuario = $dao->buscarUsuario($_SESSION["cedula"]);
        Conexion::desconectar();

        if ($usuario === null) {
            RespuestaJson::error("Acceso denegado: sesión no iniciada", 401);
        }

        RespuestaJson::exito([
            "cedula" => $usuario->getCedula(),
            "nombre" => $usuario->getNombre(),
            "apellido" => $usuario->getApellido(),
            "email" => $usuario->getEmail(),
            "rol" => $this->determinarRol($usuario)
        ]);
    }

    /**
     * Cierra la sesión del usuario. Requiere token CSRF.
     * @param string $metodo Método HTTP de la petición, solo se acepta POST.
     */
    public function cerrarSesion(string $metodo): void {
        if ($metodo !== "POST") {
            RespuestaJson::error("Método no permitido", 405);
        }

        Token::verificarCSRF();

        //?recurso=salida lo envía el navegador al abandonar la página (cerrar la pestaña, el navegador, etc.)
        if (($_GET["recurso"] ?? "") === "salida") {
            $this->registrarSalida();
        }

        //RF37: registra que la sesión ya no está activa. Si la base falla, igual se cierra la sesión localmente.
        $cedula = $_SESSION["cedula"] ?? null;

        if ($cedula !== null) {
            $conexion = Conexion::intentarConectar();

            if ($conexion !== null) {
                $dao = new UsuarioDAO($conexion);
                $dao->establecerSesionActiva($cedula, false);
                Conexion::desconectar();
            }
        }

        Sesion::cerrar();

        RespuestaJson::exito(["mensaje" => "Sesión cerrada correctamente"]);
    }

    /**
     * RF37: el usuario abandonó la página sin usar el botón "Cerrar sesión".
     *
     * El navegador avisa lo mismo al cerrar la pestaña que al recargar o pasar a otra página del sistema, y desde
     * acá no se puede distinguir. Por eso se libera la sesión en la base de datos (así puede volver a iniciar sesión)
     * pero la sesión PHP no se destruye: se anota la hora de salida. Si dentro de Sesion::SEGUNDOS_GRACIA llega otra
     * petición de esa misma sesión (recarga o cambio de página), Sesion::reanudar() la vuelve a activar; si no llega
     * ninguna, la sesión queda vencida.
     */
    private function registrarSalida(): void {
        $cedula = $_SESSION["cedula"] ?? null;

        if ($cedula !== null) {
            $conexion = Conexion::intentarConectar();

            if ($conexion !== null) {
                $dao = new UsuarioDAO($conexion);
                $dao->establecerSesionActiva($cedula, false);
                Conexion::desconectar();
            }

            $_SESSION["salidaPendiente"] = time();
        }

        RespuestaJson::exito(["mensaje" => "Salida registrada"]);
    }

    /**
     * Elige el rol principal del usuario (mismo orden de prioridad que se usa para elegir el panel).
     * @return string|null "administrador", "direccion", "docente", "tecnico" o null si no tiene rol.
     */
    private function determinarRol(Usuario $usuario): ?string {
        if ($usuario->esAdministrador()) {
            return "administrador";
        }
        if ($usuario->esDireccion()) {
            return "direccion";
        }
        if ($usuario->esDocente()) {
            return "docente";
        }
        if ($usuario->esTecnico()) {
            return "tecnico";
        }

        return null;
    }
}
