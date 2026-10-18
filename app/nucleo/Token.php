<?php

require_once RUTA_VISTA . "/RespuestaJson.php";

/**
 * Clase estática que gestiona el token CSRF de la sesión.
 *
 * Cross-Site Request Forgery ~ Falsificación de solicitudes entre sitios.
 * En la API el token no viaja en un campo oculto del formulario, sino en la cabecera
 * HTTP "X-CSRF-Token" que agrega el JavaScript en cada petición que modifica datos.
 */
class Token {

    /**
     * Compara el token recibido en la cabecera con el guardado en la sesión.
     * Si no existe o no coincide, responde error 403 y detiene la ejecución.
     */
    public static function verificarCSRF(): void {
        //PHP guarda las cabeceras recibidas en $_SERVER con el prefijo HTTP_ y los guiones a guiones bajos
        $token = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";

        //hash_equals() compara en tiempo constante, evita filtrar información
        if (!isset($_SESSION["csrfToken"]) || !hash_equals($_SESSION["csrfToken"], $token)) {
            RespuestaJson::error("Solicitud rechazada", 403);
        }
    }

    /**
     * Genera el token (32 bytes aleatorios pasados a hexadecimal) si la sesión todavía no tiene uno.
     */
    public static function generarTokenCSRF(): void {
        if (!isset($_SESSION["csrfToken"])) {
            $_SESSION["csrfToken"] = bin2hex(random_bytes(32));
        }
    }
}
