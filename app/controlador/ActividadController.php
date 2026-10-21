<?php

require_once RUTA_MODELO . "/ActividadDAO.php";
require_once RUTA_VISTA . "/RespuestaJson.php";

require_once RUTA_NUCLEO . "/Conexion.php";
require_once RUTA_NUCLEO . "/Sesion.php";
require_once RUTA_NUCLEO . "/Token.php";

/**
 * Controlador de la API que expone el registro de actividad (RNF4). Responde siempre en JSON.
 * Solo el administrador puede operarlo. Las consultas (GET) no exigen token CSRF; las correcciones (PUT)
 * y las bajas (DELETE) sí, igual que en el resto de la API.
 *
 * GET ?ruta=actividad[&desde=AAAA-MM-DD][&hasta=AAAA-MM-DD][&cedula=12345678][&limite=100]
 * PUT ?ruta=actividad {"id": 1, "detalle": "texto"}
 * DELETE ?ruta=actividad {"id": 1}
 */
class ActividadController {
    //Cantidad de filas que se devuelven si no se indica ?limite=
    private const LIMITE_POR_DEFECTO = 100;
    //Tope de filas por consulta, evita respuestas demasiado pesadas
    private const LIMITE_MAXIMO = 500;
    //Si no se indica ?desde=, se consultan los últimos días
    private const DIAS_POR_DEFECTO = 7;

    /**
     * @param string $metodo Método HTTP de la petición, se aceptan GET, PUT y DELETE.
     */
    public function gestionar(string $metodo): void {
        Sesion::verificarRol("administrador");

        if ($metodo === "PUT" || $metodo === "DELETE") {
            //El token CSRF solo se exige en las operaciones que modifican datos
            Token::verificarCSRF();

            if ($metodo === "PUT") {
                $this->modificar();
            } else {
                $this->baja();
            }

            return;
        }

        if ($metodo !== "GET") {
            RespuestaJson::error("Método no permitido", 405);
        }

        $hasta = $this->leerFecha("hasta", date("Y-m-d"));
        $desde = $this->leerFecha("desde", date("Y-m-d", strtotime("-" . self::DIAS_POR_DEFECTO . " days")));

        if ($desde > $hasta) {
            RespuestaJson::error("La fecha inicial no puede ser posterior a la final", 422);
        }

        $cedula = is_string($_GET["cedula"] ?? null) ? trim($_GET["cedula"]) : "";

        if ($cedula !== "" && !preg_match("/^[1-9][0-9]{7}$/", $cedula)) {
            RespuestaJson::error("Cédula incorrecta", 422);
        }

        $limite = self::LIMITE_POR_DEFECTO;

        if (isset($_GET["limite"])) {
            if (!is_string($_GET["limite"]) || !preg_match("/^[0-9]{1,3}$/", $_GET["limite"]) || (int) $_GET["limite"] < 1 || (int) $_GET["limite"] > self::LIMITE_MAXIMO) {
                RespuestaJson::error("El límite debe estar entre 1 y " . self::LIMITE_MAXIMO, 422);
            }

            $limite = (int) $_GET["limite"];
        }

        $conexion = Conexion::conectar();
        $dao = new ActividadDAO($conexion);
        $actividad = $dao->listar($desde, $hasta, $cedula === "" ? null : $cedula, $limite);
        Conexion::desconectar();

        RespuestaJson::exito(["desde" => $desde, "hasta" => $hasta, "actividad" => $actividad]);
    }

    /**
     * Lee una fecha AAAA-MM-DD de la URL. Si no viene usa el valor por defecto; si es inválida responde 422.
     */
    private function leerFecha(string $campo, string $porDefecto): string {
        if (!isset($_GET[$campo]) || $_GET[$campo] === "") {
            return $porDefecto;
        }

        $valor = is_string($_GET[$campo]) ? trim($_GET[$campo]) : "";
        $fecha = DateTime::createFromFormat("Y-m-d", $valor);

        if ($fecha === false || $fecha->format("Y-m-d") !== $valor) {
            RespuestaJson::error("La fecha \"" . $campo . "\" es inválida, use AAAA-MM-DD", 422);
        }

        return $valor;
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
     * Convierte un identificador (entero o texto de dígitos) en entero positivo.
     * @return int|null El entero, o null si no es válido (negativo, cero, texto, arreglo, demasiado grande...).
     */
    private function aEntero(mixed $valor): ?int {
        if (is_int($valor)) {
            $numero = $valor;
        } elseif (is_string($valor) && preg_match("/^[0-9]{1,10}$/", $valor)) {
            $numero = (int) $valor;
        } else {
            return null;
        }

        //2147483647 es el máximo de una columna INT de MySQL
        return ($numero >= 1 && $numero <= 2147483647) ? $numero : null;
    }

    /**
     * Corrige el detalle de un registro de actividad. Espera {"id": 1, "detalle": "texto"}.
     * El usuario, la operación y la fecha del registro no se pueden cambiar.
     */
    private function modificar(): void {
        $datos = $this->leerJson();

        $idActividad = $this->aEntero($datos["id"] ?? null);
        $detalle = is_string($datos["detalle"] ?? null) ? trim($datos["detalle"]) : "";

        if ($idActividad === null) {
            RespuestaJson::error("Falta el id del registro o es incorrecto", 422);
        }

        if (mb_strlen($detalle) > 255) {
            RespuestaJson::error("El detalle admite hasta 255 caracteres", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new ActividadDAO($conexion);

        if ($dao->listarRegistro($idActividad) === null) {
            Conexion::desconectar();
            RespuestaJson::error("El registro de actividad no existe", 404);
        }

        //Un detalle vacío se guarda como NULL, igual que cuando el sistema registra la operación sin detalle
        $resultado = $dao->modificarDetalle($idActividad, $detalle !== "" ? $detalle : null);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo modificar el registro de actividad", 400);
        }

        RespuestaJson::exito(["mensaje" => "Registro de actividad modificado exitosamente"]);
    }

    /**
     * Elimina un registro de actividad. Espera {"id": 1}.
     */
    private function baja(): void {
        $datos = $this->leerJson();

        $idActividad = $this->aEntero($datos["id"] ?? null);

        if ($idActividad === null) {
            RespuestaJson::error("Falta el id del registro o es incorrecto", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new ActividadDAO($conexion);

        if ($dao->listarRegistro($idActividad) === null) {
            Conexion::desconectar();
            RespuestaJson::error("El registro de actividad no existe", 404);
        }

        $resultado = $dao->eliminar($idActividad);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo eliminar el registro de actividad", 400);
        }

        RespuestaJson::exito(["mensaje" => "Registro de actividad eliminado exitosamente"]);
    }
}
