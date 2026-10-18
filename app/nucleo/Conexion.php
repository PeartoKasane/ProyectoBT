<?php

require_once RUTA_MODELO . "/ConectorPDO.php";
require_once RUTA_VISTA . "/RespuestaJson.php";

/**
 * Clase estática que centraliza la conexión a la base de datos para los controladores.
 * Obtiene la instancia única (Singleton) de ConectorPDO con las variables de entorno.
 */
class Conexion {

    private static function obtenerConector(): ConectorPDO {
        return ConectorPDO::obtenerInstancia($_ENV['DB_HOST'], (int) $_ENV['DB_PUERTO'], $_ENV['DB_USUARIO'], $_ENV['DB_CLAVE'], $_ENV['DB_NOMBRE']);
    }

    /**
     * Establece la conexión. Si no se logra, responde error 500 en JSON y detiene la ejecución.
     * @return PDO La conexión lista para usar.
     */
    public static function conectar(): PDO {
        try {
            return self::obtenerConector()->establecerConexion();
        } catch (PDOException $error) {
            //Error para verificar por consola, no se le informa el detalle al cliente
            error_log($error->getMessage());
            RespuestaJson::error("Error de conexión con la base de datos", 500);
        }
    }

    /**
     * Intenta establecer la conexión sin responder ni detener la ejecución si falla.
     * Sirve para operaciones que no deben impedir el resto del proceso (por ejemplo cerrar la sesión).
     * @return PDO|null La conexión, o null si no se pudo establecer.
     */
    public static function intentarConectar(): ?PDO {
        try {
            return self::obtenerConector()->establecerConexion();
        } catch (PDOException $error) {
            error_log($error->getMessage());
            return null;
        }
    }

    /**
     * Cierra la conexión actual.
     */
    public static function desconectar(): void {
        self::obtenerConector()->desconectar();
    }
}
