<?php

require_once RUTA_MODELO . "/ConectorPDO.php";
require_once RUTA_MODELO . "/UsuarioDAO.php";
require_once RUTA_MODELO . "/FiltroPalabras.php";
require_once RUTA_VISTA . "/RespuestaJson.php";

require_once RUTA_NUCLEO . "/Conexion.php";
require_once RUTA_NUCLEO . "/Token.php";
require_once RUTA_NUCLEO . "/Sesion.php";

/**
 * Controlador de la API que gestiona los usuarios (ABM + estado de la cuenta).
 * Solo puede ser utilizado por un administrador. Responde siempre en JSON.
 *
 * Cada método HTTP representa una operación distinta sobre el mismo recurso:
 * GET = consultar, POST = alta, PUT = modificar, PATCH = habilitar o deshabilitar (RF36), DELETE = baja.
 */
class UsuarioController {
    //Roles que el sistema acepta al dar de alta o modificar un usuario
    private const ROLES = ["Administrador", "Docente", "Dirección", "Técnico"];

    /**
     * Verifica los permisos y deriva la petición a la operación correspondiente.
     * @param string $metodo Método HTTP de la petición.
     */
    public function gestionar(string $metodo): void {
        Sesion::verificarRol("administrador");

        //Forma de controlar a través del token las consultas críticas sobre determinados datos
        //Puesto que CSRF es para proteger la manipulación de los datos, se permite operar sobre GET sin token
        if (in_array($metodo, ["POST", "PUT", "PATCH", "DELETE"], true)) {
            Token::verificarCSRF();
        }

        //https://www.w3schools.com/php/php_match.asp
        match ($metodo) {
            "GET" => $this->listar(),
            "POST" => $this->alta(),
            "PUT" => $this->modificar(),
            "PATCH" => $this->cambiarEstado(),
            "DELETE" => $this->baja(),
            default => RespuestaJson::error("Método no permitido", 405),
        };
    }

    /**
     * Lee el cuerpo de la petición y lo convierte de JSON a arreglo. Si no es JSON válido, responde error 400.
     */
    private function leerJson(): array {
        $datos = json_decode(file_get_contents("php://input"), true);

        if (!is_array($datos)) {
            RespuestaJson::error("JSON inválido", 400);
        }

        return $datos;
    }

    /**
     * Retorna el campo como texto sin espacios en los extremos, o "" si no existe o no es texto.
     * Evita errores si el cliente envía un valor de otro tipo (número, arreglo, etc.).
     */
    private function texto(array $datos, string $campo): string {
        return is_string($datos[$campo] ?? null) ? trim($datos[$campo]) : "";
    }

    /**
     * Retorna la contraseña tal cual llegó (sin trim, los espacios pueden ser parte de la clave), o "" si no es texto.
     */
    private function clave(array $datos, string $campo): string {
        return is_string($datos[$campo] ?? null) ? $datos[$campo] : "";
    }

    private function listar(): void {
        if (isset($_GET["cedula"])) {
            //En caso de recibir datos mediante la superglobal, se busca un usuario específico
            //Si el valor no es texto (por ejemplo ?cedula[]=1) se lo trata como vacío, y listarUsuario() responde error
            $this->listarUsuario(is_string($_GET["cedula"]) ? trim($_GET["cedula"]) : "");
        } else {
            $this->listarUsuarios();
        }
    }

    private function listarUsuario(string $cedula): void {
        if ($cedula === "") {
            RespuestaJson::error("La cédula es obligatoria", 400);
        }

        if (!preg_match("/^[1-9][0-9]{7}$/", $cedula)) {
            RespuestaJson::error("Cédula incorrecta", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new UsuarioDAO($conexion);
        $usuario = $dao->listarUsuario($cedula);
        Conexion::desconectar();

        if ($usuario === null) {
            RespuestaJson::error("El usuario no existe", 404);
        }

        RespuestaJson::exito($usuario);
    }

    private function listarUsuarios(): void {
        $conexion = Conexion::conectar();
        $dao = new UsuarioDAO($conexion);
        $usuarios = $dao->listarUsuarios();
        //Se desconecta antes de responder porque RespuestaJson::exito() termina la ejecución con exit
        Conexion::desconectar();

        RespuestaJson::exito($usuarios);
    }

    private function alta(): void {
        $datos = $this->leerJson();

        $cedula = $this->texto($datos, "cedula");
        $nombre = $this->texto($datos, "nombre");
        $apellido = $this->texto($datos, "apellido");
        $email = $this->texto($datos, "email");
        $clave = $this->clave($datos, "clave");
        $confirmarClave = $this->clave($datos, "confirmarClave");
        $rol = $this->texto($datos, "rol");

        if ($cedula === "" || $nombre === "" || $apellido === "" || $clave === "" || $confirmarClave === "" || $rol === "") {
            RespuestaJson::error("Existen campos vacíos", 422);
        }
        if (!preg_match("/^[1-9][0-9]{7}$/", $cedula)) {
            RespuestaJson::error("Cédula incorrecta", 422);
        }
        if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            RespuestaJson::error("Correo electrónico incorrecto", 422);
        }
        //RF25: no se admiten palabras ofensivas en los textos ingresados
        if (FiltroPalabras::contienePalabraOfensiva($nombre) || FiltroPalabras::contienePalabraOfensiva($apellido)) {
            RespuestaJson::error("El nombre o el apellido contienen palabras no permitidas", 422);
        }
        if (strlen($clave) < 12) {
            RespuestaJson::error("La contraseña debe contener al menos 12 caracteres", 422);
        }
        if ($clave !== $confirmarClave) {
            RespuestaJson::error("Las contraseñas ingresadas no coinciden", 422);
        }
        if (!in_array($rol, self::ROLES, true)) {
            RespuestaJson::error("Rol incorrecto", 422);
        }

        //Hasheo básico de la contraseña para almacenar en la base de datos
        $claveHash = password_hash($clave, PASSWORD_DEFAULT);

        $conexion = Conexion::conectar();
        $dao = new UsuarioDAO($conexion);
        $resultado = $dao->registrarUsuario($cedula, $nombre, $apellido, $email, $claveHash, $rol);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo registrar el empleado", 400);
        }

        RespuestaJson::exito(["mensaje" => "Empleado ingresado exitosamente"], 201);
    }

    private function modificar(): void {
        $datos = $this->leerJson();

        $cedula = $this->texto($datos, "cedula");
        $nombre = $this->texto($datos, "nombre");
        $apellido = $this->texto($datos, "apellido");
        $email = $this->texto($datos, "email");
        $clave = $this->clave($datos, "clave");
        $confirmarClave = $this->clave($datos, "confirmarClave");
        $rol = $this->texto($datos, "rol");

        if ($cedula === "" || $nombre === "" || $apellido === "" || $clave === "" || $confirmarClave === "" || $rol === "") {
            RespuestaJson::error("Existen campos vacíos", 422);
        }

        if (!preg_match("/^[1-9][0-9]{7}$/", $cedula)) {
            RespuestaJson::error("Cédula incorrecta", 422);
        }

        if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            RespuestaJson::error("Correo electrónico incorrecto", 422);
        }

        //RF25: no se admiten palabras ofensivas en los textos ingresados
        if (FiltroPalabras::contienePalabraOfensiva($nombre) || FiltroPalabras::contienePalabraOfensiva($apellido)) {
            RespuestaJson::error("El nombre o el apellido contienen palabras no permitidas", 422);
        }
        if (strlen($clave) < 12) {
            RespuestaJson::error("La contraseña debe contener al menos 12 caracteres", 422);
        }

        if ($clave !== $confirmarClave) {
            RespuestaJson::error("Las contraseñas ingresadas no coinciden", 422);
        }

        if (!in_array($rol, self::ROLES, true)) {
            RespuestaJson::error("Rol incorrecto", 422);
        }

        $claveHash = password_hash($clave, PASSWORD_DEFAULT);

        $conexion = Conexion::conectar();
        $dao = new UsuarioDAO($conexion);
        $resultado = $dao->modificarUsuario($cedula, $nombre, $apellido, $email, $claveHash, $rol);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo modificar el empleado", 400);
        }

        RespuestaJson::exito(["mensaje" => "Empleado modificado exitosamente"]);
    }

    /**
     * RF36: habilita o deshabilita una cuenta. Espera {"cedula": "...", "activo": true|false}.
     */
    private function cambiarEstado(): void {
        $datos = $this->leerJson();

        $cedula = $this->texto($datos, "cedula");
        $activo = $datos["activo"] ?? null;

        if ($cedula === "") {
            RespuestaJson::error("Falta la cédula del empleado", 422);
        }

        if (!preg_match("/^[1-9][0-9]{7}$/", $cedula)) {
            RespuestaJson::error("Cédula incorrecta", 422);
        }

        if (!is_bool($activo)) {
            RespuestaJson::error("El estado debe ser true o false", 422);
        }

        //No tiene sentido que un administrador se deshabilite a sí mismo y quede sin acceso
        if ($cedula === $_SESSION["cedula"] && !$activo) {
            RespuestaJson::error("No podés deshabilitar tu propia cuenta", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new UsuarioDAO($conexion);

        //Evita informar éxito cuando la cédula no pertenece a ningún usuario
        if ($dao->listarUsuario($cedula) === null) {
            Conexion::desconectar();
            RespuestaJson::error("El usuario no existe", 404);
        }

        $resultado = $dao->cambiarEstadoActivo($cedula, $activo);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo actualizar el estado del usuario", 400);
        }

        RespuestaJson::exito(["mensaje" => $activo ? "Cuenta habilitada" : "Cuenta deshabilitada"]);
    }

    private function baja(): void {
        $datos = $this->leerJson();

        $cedula = $this->texto($datos, "cedula");

        if ($cedula === "") {
            RespuestaJson::error("Falta la cédula del empleado", 422);
        }

        if (!preg_match("/^[1-9][0-9]{7}$/", $cedula)) {
            RespuestaJson::error("Cédula incorrecta", 422);
        }

        //Un administrador no puede eliminar su propia cuenta
        if ($cedula === $_SESSION["cedula"]) {
            RespuestaJson::error("No podés eliminar tu propia cuenta", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new UsuarioDAO($conexion);

        if ($dao->listarUsuario($cedula) === null) {
            Conexion::desconectar();
            RespuestaJson::error("El usuario no existe", 404);
        }

        $resultado = $dao->eliminarUsuario($cedula);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo eliminar el empleado", 400);
        }

        RespuestaJson::exito(["mensaje" => "Empleado eliminado exitosamente"]);
    }
}
