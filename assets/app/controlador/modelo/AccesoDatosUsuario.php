<?php


class AccesoDatosUsuario {
    private PDO $conexion;

  
    public function __construct (PDO $conexion) {
        $this->conexion = $conexion;
    }

  
    public function buscarUsuario(string $cedula): ?Usuario
    {
        $sql = "
            SELECT
                u.cedula,
                u.claveHash,
                u.activo,

                CASE
                    WHEN a.cedula IS NOT NULL THEN 1
                    ELSE 0
                END AS administrador,

                CASE
                    WHEN l.cedula IS NOT NULL THEN 1
                    ELSE 0
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

        $datos = $consulta->fetch(PDO::FETCH_ASSOC);

        if ($datos === false) {
            return null;
        }

        return new Usuario(
            $datos["cedula"],
            $datos["claveHash"],
            (bool) $datos["activo"],
            (bool) $datos["administrador"],
            (bool) $datos["logistica"]
        );
    }
}

?>