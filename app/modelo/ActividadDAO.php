<?php

/**
 * DAO ~ Data Access Object de la tabla ACTIVIDAD (RNF4).
 * Es un registro de auditoría: las filas las agrega el sistema automáticamente y solo el administrador
 * puede corregir su detalle o eliminarlas (modificarDetalle() y eliminar()).
 */
class ActividadDAO {
    private PDO $conexion;

    /**
     * @param PDO $conexion La conexión a la base de datos. PRECONDICIÓN: No debe ser NULL.
     */
    public function __construct(PDO $conexion) {
        $this->conexion = $conexion;
    }

    /**
     * Registra una operación realizada por un usuario.
     * @return bool TRUE si se guardó, FALSE en caso contrario.
     */
    public function registrar(?string $cedula, string $metodo, string $ruta, ?string $recurso, ?string $detalle, int $estado): bool {
        try {
            $sql = "
                INSERT INTO ACTIVIDAD (cedula, metodo, ruta, recurso, detalle, estado)
                VALUES (:cedula, :metodo, :ruta, :recurso, :detalle, :estado)
            ";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute([
                "cedula" => $cedula, "metodo" => $metodo, "ruta" => $ruta,
                "recurso" => $recurso, "detalle" => $detalle, "estado" => $estado
            ]);

            return true;
        } catch (PDOException $error) {
            error_log($error->getMessage());
            return false;
        }
    }

    /**
     * Lista las operaciones registradas, de la más reciente a la más antigua.
     * @param string $desde Fecha inicial AAAA-MM-DD.
     * @param string $hasta Fecha final AAAA-MM-DD.
     * @param string|null $cedula Si se informa, solo las operaciones de ese usuario.
     * @param int $limite Cantidad máxima de filas.
     * @return array Filas con id, cedula, nombre, apellido, metodo, ruta, recurso, detalle, estado y fecha.
     */
    public function listar(string $desde, string $hasta, ?string $cedula, int $limite): array {
        $sql = "
            SELECT a.id, a.cedula, u.nombre, u.apellido, a.metodo, a.ruta, a.recurso, a.detalle, a.estado, a.fecha
            FROM ACTIVIDAD AS a
            LEFT JOIN USUARIO AS u ON u.cedula = a.cedula
            WHERE DATE(a.fecha) BETWEEN :desde AND :hasta
        ";

        if ($cedula !== null) {
            $sql .= " AND a.cedula = :cedula";
        }

        $sql .= " ORDER BY a.fecha DESC, a.id DESC LIMIT :limite";

        $consulta = $this->conexion->prepare($sql);
        $consulta->bindValue("desde", $desde);
        $consulta->bindValue("hasta", $hasta);

        if ($cedula !== null) {
            $consulta->bindValue("cedula", $cedula);
        }

        $consulta->bindValue("limite", $limite, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca un registro de actividad por su identificador.
     * @param int $idActividad Identificador del registro.
     * @return array|null El registro (id, cedula, metodo, ruta, recurso, detalle, estado, fecha), null si no existe.
     */
    public function listarRegistro(int $idActividad): ?array {
        $sql = "SELECT id, cedula, metodo, ruta, recurso, detalle, estado, fecha FROM ACTIVIDAD WHERE id = :idActividad";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["idActividad" => $idActividad]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        $consulta = null;

        return $fila === false ? null : $fila;
    }

    /**
     * Corrige el detalle de un registro de actividad. El usuario, la operación y la fecha nunca se modifican.
     * @param int $idActividad Identificador del registro.
     * @param string|null $detalle Nuevo detalle (hasta 255 caracteres), null para dejarlo vacío.
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function modificarDetalle(int $idActividad, ?string $detalle): bool {
        try {
            $sql = "UPDATE ACTIVIDAD SET detalle = :detalle WHERE id = :idActividad";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["detalle" => $detalle, "idActividad" => $idActividad]);

            return true;
        } catch (PDOException $error) {
            return false;
        }
    }

    /**
     * Elimina un registro de actividad.
     * @param int $idActividad Identificador del registro.
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function eliminar(int $idActividad): bool {
        try {
            $sql = "DELETE FROM ACTIVIDAD WHERE id = :idActividad";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["idActividad" => $idActividad]);

            return true;
        } catch (PDOException $error) {
            return false;
        }
    }
}
