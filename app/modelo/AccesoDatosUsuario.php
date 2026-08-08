<?php

/**
 * Clase que simula una recuperación de credenciales correspondientes a la base de datos.
 */
class AccesoDatosUsuario {
    private PDO $conexion;

    /**
     * Constructor parametrizado que recibe una conexión a la base de datos.
     * @param PDO $conexion La conexion a la base de datos. PRECONDICION: No debe ser NULL.
     */
    public function __construct (PDO $conexion) {
        $this->conexion = $conexion;
    }

    /**
     * Busca un usuario por su cédula y determina el rol.
     * @param string $cedula La cedula del usuario sin puntos ni guiones.
     * @return Usuario|null Los datos del usuario, retorna su objeto si existe, null en caso contrario.
     */
    public function buscarUsuario(string $cedula): ?Usuario
    {
        $sql = "
            SELECT
                u.cedula,
                u.claveHash,
                u.sesionActiva,

                CASE
                    WHEN a.cedula IS NOT NULL THEN TRUE
                    ELSE FALSE
                END AS administrador,

                CASE
                    WHEN l.cedula IS NOT NULL THEN TRUE
                    ELSE FALSE
                END AS logistica

            FROM USUARIO AS u

            LEFT JOIN ADMINISTRADOR AS a
                ON a.cedula = u.cedula

            LEFT JOIN LOGISTICA AS l
                ON l.cedula = u.cedula

            WHERE u.cedula = :cedula
        ";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute(["cedula" => $cedula]);

        $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

        //Una vez usada la consulta, desconectar el objeto PDOStatement. https://www.php.net/manual/en/pdo.connections.php
        $consulta = null;

        if ($usuario === false) {
            return null;
        }

        return new Usuario(
            $usuario["cedula"],
            $usuario["claveHash"],
            (bool) $usuario["sesionActiva"],
            (bool) $usuario["administrador"],
            (bool) $usuario["logistica"]
        );
    }

    public function listarUsuarios (): array {
        $sql = "
            SELECT
                u.cedula,
                u.nombre,
                u.apellido,

                CASE
                    WHEN a.cedula IS NOT NULL THEN TRUE
                    ELSE FALSE
                END AS administrador,

                CASE
                    WHEN l.cedula IS NOT NULL THEN TRUE
                    ELSE FALSE
                END AS logistica

            FROM USUARIO AS u

            LEFT JOIN ADMINISTRADOR AS a
                ON a.cedula = u.cedula

            LEFT JOIN LOGISTICA AS l
                ON l.cedula = u.cedula";

        $consulta = $this->conexion->query($sql);

        $usuarios = $consulta->fetchAll(PDO::FETCH_ASSOC);

        $consulta = null;

        return $usuarios;
    }
}

?>