<?php

require_once RUTA_MODELO . "/ActividadDAO.php";
require_once RUTA_NUCLEO . "/Conexion.php";

/**
 * RNF4: registra las operaciones que modifican datos para asegurar la trazabilidad.
 *
 * Se programa una sola vez desde el entrypoint (index.php) y el registro se escribe al terminar la petición,
 * cuando ya se conoce el código de estado: solo quedan las operaciones realizadas con éxito.
 * Nunca se guardan contraseñas ni el cuerpo completo: únicamente algunos identificadores.
 */
class Actividad {
    private const METODOS_REGISTRADOS = ["POST", "PUT", "PATCH", "DELETE"];

    //Campos del cuerpo JSON que se anotan en el detalle (identifican sobre qué se operó)
    private const CAMPOS_DETALLE = ["id", "cedula", "idSolicitud", "estado", "activo", "rol", "cedulaAsignado"];

    /**
     * Programa el registro de la petición actual si modifica datos.
     * @param string|null $cedulaPrevia Cédula de la sesión antes de procesar (el cierre de sesión la destruye).
     */
    public static function programarRegistro(string $metodo, string $ruta, ?string $cedulaPrevia): void {
        if (!in_array($metodo, self::METODOS_REGISTRADOS, true)) {
            return;
        }

        //El aviso automático de que se abandonó la página se envía en cada recarga o cambio de página: no es una operación del usuario
        if ($ruta === "logout" && ($_GET["recurso"] ?? "") === "salida") {
            return;
        }

        //El cuerpo se lee ahora, antes de que lo procese el controlador
        $detalle = self::armarDetalle(file_get_contents("php://input"));

        register_shutdown_function(function () use ($metodo, $ruta, $cedulaPrevia, $detalle) {
            $estado = http_response_code();

            //Solo se registran las operaciones que se completaron
            if ($estado === false || $estado >= 400) {
                return;
            }

            try {
                $conexion = Conexion::intentarConectar();

                if ($conexion === null) {
                    return;
                }

                $recurso = is_string($_GET["recurso"] ?? null) ? mb_substr(trim($_GET["recurso"]), 0, 30) : null;
                $cedula = $_SESSION["cedula"] ?? $cedulaPrevia;

                $dao = new ActividadDAO($conexion);
                $dao->registrar($cedula, $metodo, mb_substr($ruta, 0, 30), $recurso === "" ? null : $recurso, $detalle, $estado);
                Conexion::desconectar();
            } catch (Throwable $error) {
                //Un fallo del registro nunca debe afectar la respuesta ya enviada
                error_log($error->getMessage());
            }
        });
    }

    private static function armarDetalle(string|false $cuerpo): ?string {
        $datos = json_decode($cuerpo === false ? "" : $cuerpo, true);

        if (!is_array($datos)) {
            return null;
        }

        $partes = [];

        foreach (self::CAMPOS_DETALLE as $campo) {
            if (isset($datos[$campo]) && (is_scalar($datos[$campo]))) {
                $partes[] = $campo . "=" . (is_bool($datos[$campo]) ? ($datos[$campo] ? "true" : "false") : $datos[$campo]);
            }
        }

        return $partes === [] ? null : mb_substr(implode(" ", $partes), 0, 255);
    }
}
