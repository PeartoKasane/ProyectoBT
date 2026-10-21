<?php

/**
 * Clase que unifica todas las operaciones de acceso a datos
 * relacionadas con el inventario: salas (laboratorios y talleres), equipos y el historial de cada equipo.
 *
 * DAO ~ Data Access Object. Reúne en una sola clase todo el acceso a datos de la entidad.
 *
 * Los métodos de consulta retornan arreglos asociativos
 * (listos para convertir a JSON) en lugar de objetos Sala, Equipo o HistorialEquipo.
 */
class InventarioDAO {
    private PDO $conexion;

    /**
     * Constructor parametrizado que recibe una conexión a la base de datos.
     * @param PDO $conexion La conexión a la base de datos. PRECONDICIÓN: No debe ser NULL.
     */
    public function __construct(PDO $conexion) {
        $this->conexion = $conexion;
    }

    /**
     * Lista todas las salas, cada una con sus equipos (RF7).
     * Hace solo dos consultas en total, sin importar cuántas salas existan.
     * @return array Arreglo de salas; cada sala incluye la clave "equipos".
     */
    public function listarInventario(): array {
        $salas = $this->listarSalas();

        //Agrupa los equipos por sala para no repetir una consulta por cada una
        $sql = "SELECT id, numero, idSala, estado, incidencia FROM EQUIPO ORDER BY idSala, numero";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $consulta = null;

        $equiposPorSala = [];
        foreach ($filas as $fila) {
            $equipo = $this->mapearEquipo($fila);
            $equiposPorSala[$equipo["idSala"]][] = $equipo;
        }

        foreach ($salas as $indice => $sala) {
            $salas[$indice]["equipos"] = $equiposPorSala[$sala["id"]] ?? [];
        }

        return $salas;
    }

    /**
     * Lista todas las salas registradas, sin sus equipos.
     * @return array Arreglo de salas (cada una es un arreglo asociativo).
     */
    public function listarSalas(): array {
        $sql = "SELECT id, tipo, numero FROM SALA ORDER BY tipo, numero";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute();
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $consulta = null;

        $salas = [];
        foreach ($filas as $fila) {
            $salas[] = $this->mapearSala($fila);
        }

        return $salas;
    }

    /**
     * Busca una sala por su identificador.
     * @param int $idSala Identificador de la sala.
     * @return array|null La sala como arreglo asociativo, null si no existe.
     */
    public function listarSala(int $idSala): ?array {
        $sql = "SELECT id, tipo, numero FROM SALA WHERE id = :idSala";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["idSala" => $idSala]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        $consulta = null;

        if ($fila === false) {
            return null;
        }

        return $this->mapearSala($fila);
    }

    /**
     * Lista los equipos de una sala determinada.
     * @param int $idSala Identificador de la sala.
     * @return array Arreglo de equipos (cada uno es un arreglo asociativo).
     */
    public function listarEquiposPorSala(int $idSala): array {
        $sql = "SELECT id, numero, idSala, estado, incidencia FROM EQUIPO WHERE idSala = :idSala ORDER BY numero";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["idSala" => $idSala]);
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $consulta = null;

        $equipos = [];
        foreach ($filas as $fila) {
            $equipos[] = $this->mapearEquipo($fila);
        }

        return $equipos;
    }

    /**
     * Busca un equipo por su identificador.
     * @param int $idEquipo Identificador del equipo.
     * @return array|null El equipo como arreglo asociativo, null si no existe.
     */
    public function listarEquipo(int $idEquipo): ?array {
        $sql = "SELECT id, numero, idSala, estado, incidencia FROM EQUIPO WHERE id = :idEquipo";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["idEquipo" => $idEquipo]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        $consulta = null;

        if ($fila === false) {
            return null;
        }

        return $this->mapearEquipo($fila);
    }

    /**
     * RF5: Lista el historial de un equipo, del evento más reciente al más antiguo.
     * @param int $idEquipo Identificador del equipo.
     * @return array Arreglo de eventos (cada uno es un arreglo asociativo).
     */
    public function listarHistorialPorEquipo(int $idEquipo): array {
        $sql = "
            SELECT h.id, h.accion, h.detalle, h.fecha, u.nombre, u.apellido
            FROM HISTORIAL_EQUIPO AS h
            LEFT JOIN USUARIO AS u ON u.cedula = h.cedula
            WHERE h.equipo_id = :idEquipo
            ORDER BY h.fecha DESC
        ";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["idEquipo" => $idEquipo]);
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $consulta = null;

        $historial = [];
        foreach ($filas as $fila) {
            $historial[] = [
                "id" => (int) $fila["id"],
                "accion" => $fila["accion"],
                "detalle" => $fila["detalle"],
                "fecha" => $fila["fecha"],
                //Si el usuario fue eliminado, la clave foránea queda en NULL y se muestra un texto genérico
                "usuario" => $fila["nombre"] === null ? "Usuario eliminado" : $fila["nombre"] . " " . $fila["apellido"],
            ];
        }

        return $historial;
    }

    /**
     * Verifica si ya existe una sala con ese tipo y número.
     * @param string $tipo Laboratorio o Taller.
     * @param int $numero Número de la sala.
     * @return bool TRUE si existe, FALSE en caso contrario.
     */
    public function existeSala(string $tipo, int $numero): bool {
        $sql = "SELECT id FROM SALA WHERE tipo = :tipo AND numero = :numero";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["tipo" => $tipo, "numero" => $numero]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        $consulta = null;

        return $fila !== false;
    }

    /**
     * Verifica si existe una sala distinta de la indicada con ese tipo y número (para modificar sin chocar con otra sala).
     * @param string $tipo Laboratorio o Taller.
     * @param int $numero Número de la sala.
     * @param int $idSala Identificador de la sala que se está modificando, se excluye de la búsqueda.
     * @return bool TRUE si existe otra sala igual, FALSE en caso contrario.
     */
    public function existeOtraSala(string $tipo, int $numero, int $idSala): bool {
        $sql = "SELECT id FROM SALA WHERE tipo = :tipo AND numero = :numero AND id <> :idSala";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["tipo" => $tipo, "numero" => $numero, "idSala" => $idSala]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        $consulta = null;

        return $fila !== false;
    }

    /**
     * Verifica si ya existe un equipo con ese número dentro de la sala.
     * @param int $idSala Identificador de la sala.
     * @param int $numero Número del equipo.
     * @return bool TRUE si existe, FALSE en caso contrario.
     */
    public function existeEquipo(int $idSala, int $numero): bool {
        $sql = "SELECT id FROM EQUIPO WHERE idSala = :idSala AND numero = :numero";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["idSala" => $idSala, "numero" => $numero]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        $consulta = null;

        return $fila !== false;
    }

    /**
     * RF6: Registra una nueva sala (laboratorio o taller).
     * @param string $tipo Laboratorio o Taller.
     * @param int $numero Número de la sala.
     * @return int|null El identificador de la sala creada, null si el registro falla.
     */
    public function registrarSala(string $tipo, int $numero): ?int {
        try {
            $sql = "INSERT INTO SALA (tipo, numero) VALUES (:tipo, :numero)";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["tipo" => $tipo, "numero" => $numero]);

            return (int) $this->conexion->lastInsertId();
        } catch (PDOException $error) {
            return null;
        }
    }

    /**
     * RF6: Modifica el tipo y el número de una sala. Falla si otra sala ya tiene ese tipo y número (UNIQUE tipo + numero).
     * @param int $idSala Identificador de la sala a modificar.
     * @param string $tipo Laboratorio o Taller.
     * @param int $numero Número de la sala.
     * @return bool TRUE si la modificación se realiza correctamente, FALSE en caso contrario.
     */
    public function modificarSala(int $idSala, string $tipo, int $numero): bool {
        try {
            $sql = "UPDATE SALA SET tipo = :tipo, numero = :numero WHERE id = :idSala";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["tipo" => $tipo, "numero" => $numero, "idSala" => $idSala]);

            return true;
        } catch (PDOException $error) {
            return false;
        }
    }

    /**
     * Registra un nuevo equipo dentro de una sala existente y deja constancia del alta en su historial (RF5).
     *
     * @param int $numero Número del equipo dentro de la sala.
     * @param int $idSala Sala a la que pertenece.
     * @param string $estado Funcionando, Incidencia o Fuera de servicio.
     * @param string|null $cedula Cédula del usuario que realiza el alta, para el historial.
     *
     * @return int|null El identificador del equipo creado, null si el registro falla.
     */
    public function registrarEquipo(int $numero, int $idSala, string $estado, ?string $cedula): ?int {
        try {
            $this->conexion->beginTransaction();

            $sql = "INSERT INTO EQUIPO (numero, idSala, estado) VALUES (:numero, :idSala, :estado)";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["numero" => $numero, "idSala" => $idSala, "estado" => $estado]);

            $idEquipo = (int) $this->conexion->lastInsertId();

            $sqlHistorial = "INSERT INTO HISTORIAL_EQUIPO (equipo_id, cedula, accion, detalle) VALUES (:idEquipo, :cedula, 'Alta', :detalle)";
            $consultaHistorial = $this->conexion->prepare($sqlHistorial);
            $consultaHistorial->execute([
                "idEquipo" => $idEquipo,
                "cedula" => $cedula,
                "detalle" => "Equipo registrado con estado inicial \"" . $estado . "\"."
            ]);

            $this->conexion->commit();
            return $idEquipo;

        } catch (PDOException $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            return null;
        }
    }

    /**
     * RF2: Modifica el estado de un equipo y deja constancia del cambio en su historial (RF5).
     *
     * @param int $idEquipo Equipo a modificar.
     * @param string $estado Nuevo estado.
     * @param string|null $incidencia Descripción de la incidencia, null si el estado no es "Incidencia".
     * @param string|null $cedula Cédula del usuario que realiza la modificación, para el historial.
     *
     * @return bool TRUE si la modificación se realiza correctamente, FALSE en caso contrario.
     */
    public function modificarEquipo(int $idEquipo, string $estado, ?string $incidencia, ?string $cedula): bool {
        try {
            $this->conexion->beginTransaction();

            //Recupera el estado anterior para dejar constancia del cambio en el historial
            $sqlAnterior = "SELECT estado FROM EQUIPO WHERE id = :idEquipo";
            $consultaAnterior = $this->conexion->prepare($sqlAnterior);
            $consultaAnterior->execute(["idEquipo" => $idEquipo]);
            $anterior = $consultaAnterior->fetch(PDO::FETCH_ASSOC);

            if ($anterior === false) {
                $this->conexion->rollBack();
                return false;
            }

            $sql = "UPDATE EQUIPO SET estado = :estado, incidencia = :incidencia WHERE id = :idEquipo";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["estado" => $estado, "incidencia" => $incidencia, "idEquipo" => $idEquipo]);

            $detalle = "Estado modificado de \"" . $anterior["estado"] . "\" a \"" . $estado . "\".";
            if ($incidencia !== null) {
                $detalle .= " Incidencia: " . $incidencia;
            }

            $sqlHistorial = "INSERT INTO HISTORIAL_EQUIPO (equipo_id, cedula, accion, detalle) VALUES (:idEquipo, :cedula, 'Modificación', :detalle)";
            $consultaHistorial = $this->conexion->prepare($sqlHistorial);
            $consultaHistorial->execute(["idEquipo" => $idEquipo, "cedula" => $cedula, "detalle" => $detalle]);

            $this->conexion->commit();
            return true;

        } catch (PDOException $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            return false;
        }
    }

    /**
     * RF6: Elimina una sala. Por la relación ON DELETE CASCADE, sus equipos se eliminan junto con ella.
     * Falla si algún equipo de la sala está referenciado por un ticket o un préstamo.
     * @param int $idSala Identificador de la sala.
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function eliminarSala(int $idSala): bool {
        try {
            $sql = "DELETE FROM SALA WHERE id = :idSala";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["idSala" => $idSala]);

            return true;
        } catch (PDOException $error) {
            return false;
        }
    }

    /**
     * RF3: Elimina un equipo puntual del inventario.
     * Falla si el equipo está referenciado por un ticket o un préstamo.
     * @param int $idEquipo Identificador del equipo.
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function eliminarEquipo(int $idEquipo): bool {
        try {
            $sql = "DELETE FROM EQUIPO WHERE id = :idEquipo";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["idEquipo" => $idEquipo]);

            return true;
        } catch (PDOException $error) {
            return false;
        }
    }

    /**
     * Busca un evento del historial de un equipo por su identificador.
     * @param int $idEvento Identificador del evento.
     * @return array|null El evento (id, equipo_id, accion, detalle), null si no existe.
     */
    public function listarEventoHistorial(int $idEvento): ?array {
        $sql = "SELECT id, equipo_id, accion, detalle FROM HISTORIAL_EQUIPO WHERE id = :idEvento";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["idEvento" => $idEvento]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        $consulta = null;

        if ($fila === false) {
            return null;
        }

        return [
            "id" => (int) $fila["id"],
            "idEquipo" => (int) $fila["equipo_id"],
            "accion" => $fila["accion"],
            "detalle" => $fila["detalle"],
        ];
    }

    /**
     * Corrige el texto de un evento del historial de un equipo. Es una operación exclusiva del administrador:
     * la fecha y el usuario que lo originó nunca se modifican, para no alterar la trazabilidad.
     * @param int $idEvento Identificador del evento.
     * @param string $accion Nueva acción (hasta 30 caracteres).
     * @param string|null $detalle Nuevo detalle (hasta 255 caracteres), null para dejarlo vacío.
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function modificarEventoHistorial(int $idEvento, string $accion, ?string $detalle): bool {
        try {
            $sql = "UPDATE HISTORIAL_EQUIPO SET accion = :accion, detalle = :detalle WHERE id = :idEvento";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["accion" => $accion, "detalle" => $detalle, "idEvento" => $idEvento]);

            return true;
        } catch (PDOException $error) {
            return false;
        }
    }

    /**
     * Elimina un evento del historial de un equipo. Es una operación exclusiva del administrador.
     * @param int $idEvento Identificador del evento.
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function eliminarEventoHistorial(int $idEvento): bool {
        try {
            $sql = "DELETE FROM HISTORIAL_EQUIPO WHERE id = :idEvento";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["idEvento" => $idEvento]);

            return true;
        } catch (PDOException $error) {
            return false;
        }
    }

    /**
     * Convierte una fila de SALA en un arreglo con los tipos correctos.
     */
    private function mapearSala(array $fila): array {
        return [
            "id" => (int) $fila["id"],
            "tipo" => $fila["tipo"],
            "numero" => (int) $fila["numero"],
        ];
    }

    /**
     * Convierte una fila de EQUIPO en un arreglo con los tipos correctos.
     */
    private function mapearEquipo(array $fila): array {
        return [
            "id" => (int) $fila["id"],
            "numero" => (int) $fila["numero"],
            "idSala" => (int) $fila["idSala"],
            "estado" => $fila["estado"],
            "incidencia" => $fila["incidencia"],
        ];
    }
}
