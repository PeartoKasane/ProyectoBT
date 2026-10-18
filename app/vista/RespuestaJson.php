<?php

/**
 * Clase encargada de armar y enviar las respuestas de la API en formato JSON.
 *
 * CONTRATO ÚNICO de toda respuesta (correcta o con error):
 *   {
 *     "status":  código HTTP de la respuesta (200, 201, 400, 401, 403, 404, 405, 409, 422, 500...),
 *     "message": texto legible para el usuario,
 *     "data":    información devuelta, o null cuando no hay (por ejemplo en los errores)
 *   }
 */
class RespuestaJson {

    //Mensaje por defecto cuando el controlador no informa uno propio
    private const MENSAJES = [
        200 => "Operación realizada correctamente",
        201 => "Recurso creado correctamente"
    ];

    /**
     * Envía una respuesta correcta y termina la ejecución.
     * @param mixed $datos Información a devolver (arreglo, objeto, etc.). Si es un arreglo con la clave "mensaje",
     *                     ese texto pasa a ser el "message" de la respuesta y el resto queda en "data".
     * @param int $status Código de estado HTTP, 200 por defecto.
     * @param string|null $mensaje Texto para el campo "message". Si es null se usa el de $datos o uno por defecto.
     */
    public static function exito($datos, int $status = 200, ?string $mensaje = null): void {
        if ($mensaje === null && is_array($datos) && isset($datos["mensaje"]) && is_string($datos["mensaje"])) {
            $mensaje = $datos["mensaje"];
            unset($datos["mensaje"]);

            //Si el arreglo solo tenía el mensaje no queda información para "data"
            if ($datos === []) {
                $datos = null;
            }
        }

        self::enviar($status, $mensaje ?? (self::MENSAJES[$status] ?? "Operación realizada correctamente"), $datos);
    }

    /**
     * Envía una respuesta con error y termina la ejecución.
     * @param string $mensaje Explicación del error para mostrar al usuario.
     * @param int $status Código de estado HTTP (400, 401, 403, 404, 405, 409, 422, 500...).
     */
    public static function error(string $mensaje, int $status): void {
        self::enviar($status, $mensaje, null);
    }

    private static function enviar(int $status, string $mensaje, $datos): void {
        http_response_code($status);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(
            ["status" => $status, "message" => $mensaje, "data" => $datos],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }
}
