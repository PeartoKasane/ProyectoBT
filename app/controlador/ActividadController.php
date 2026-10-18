<?php

require_once RUTA_MODELO . "/ActividadDAO.php";
require_once RUTA_VISTA . "/RespuestaJson.php";

require_once RUTA_NUCLEO . "/Conexion.php";
require_once RUTA_NUCLEO . "/Sesion.php";

/**
 * Controlador de la API que expone el registro de actividad (RNF4). Responde siempre en JSON.
 * Solo el administrador puede consultarlo. Es de solo lectura (GET), por eso no exige token CSRF.
 *
 * GET ?ruta=actividad[&desde=AAAA-MM-DD][&hasta=AAAA-MM-DD][&cedula=12345678][&limite=100]
 */
class ActividadController {
    private const LIMITE_POR_DEFECTO = 100;
    private const LIMITE_MAXIMO = 500;
    private const DIAS_POR_DEFECTO = 7;

    /**
     * @param string $metodo Método HTTP de la petición, solo se acepta GET.
     */
    public function gestionar(string $metodo): void {
        Sesion::verificarRol("administrador");

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
}
