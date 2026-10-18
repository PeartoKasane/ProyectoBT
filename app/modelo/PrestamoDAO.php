<?php

/**
 * Clase que unifica todas las operaciones de acceso a datos
 * relacionadas con los préstamos de equipos (consulta, entrega y devolución).
 *
 * DAO ~ Data Access Object. Reúne en una sola clase todo el acceso a datos de la entidad.
 *
 * Los métodos de consulta retornan arreglos asociativos
 * (listos para convertir a JSON) en lugar de objetos Prestamo o PrestamoEquipo.
 */
class PrestamoDAO {
    private PDO $conexion;

    //Consulta base compartida por todas las consultas de préstamos, cada método le agrega su WHERE y su ORDER BY
    private const SELECT_PRESTAMO = "
        SELECT
            p.id, p.solicitanteNombre, p.solicitanteTipoDocumento, p.solicitanteDocumento,
            p.fechaHoraInicio, p.fechaHoraFin, p.estado,
            r.nombre AS responsableNombre, r.apellido AS responsableApellido,
            s.id AS idSolicitud
        FROM PRESTAMO AS p
        JOIN USUARIO AS r ON r.cedula = p.cedulaResponsable
        JOIN SOLICITUD AS s ON s.id = p.solicitud_id
    ";

    /**
     * Constructor parametrizado que recibe una conexión a la base de datos.
     * @param PDO $conexion La conexión a la base de datos. PRECONDICIÓN: No debe ser NULL.
     */
    public function __construct(PDO $conexion) {
        $this->conexion = $conexion;
    }

    /**
     * RF16: historial completo de préstamos y devoluciones, del más reciente al más antiguo.
     * @return array Arreglo de préstamos (cada uno es un arreglo asociativo).
     */
    public function listarPrestamos(): array {
        $sql = self::SELECT_PRESTAMO . " ORDER BY p.fechaHoraInicio DESC";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute();
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $consulta = null;

        $prestamos = [];
        foreach ($filas as $fila) {
            $prestamos[] = $this->mapearPrestamo($fila);
        }

        return $prestamos;
    }

    /**
     * Busca un préstamo por su identificador.
     * @param int $idPrestamo Identificador del préstamo.
     * @return array|null El préstamo como arreglo asociativo, null si no existe.
     */
    public function listarPrestamo(int $idPrestamo): ?array {
        $sql = self::SELECT_PRESTAMO . " WHERE p.id = :idPrestamo";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["idPrestamo" => $idPrestamo]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        $consulta = null;

        if ($fila === false) {
            return null;
        }

        return $this->mapearPrestamo($fila);
    }

    /**
     * Busca el préstamo activo (todavía sin devolver) de una solicitud, si existe.
     * @param int $idSolicitud Identificador de la solicitud.
     * @return array|null El préstamo como arreglo asociativo, null si la solicitud no tiene un préstamo activo.
     */
    public function listarPrestamoActivoPorSolicitud(int $idSolicitud): ?array {
        $sql = self::SELECT_PRESTAMO . " WHERE p.solicitud_id = :idSolicitud AND p.estado = 'Activo'";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["idSolicitud" => $idSolicitud]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        $consulta = null;

        if ($fila === false) {
            return null;
        }

        return $this->mapearPrestamo($fila);
    }

    /**
     * Lista los equipos entregados en un préstamo.
     * @param int $idPrestamo Identificador del préstamo.
     * @return array Arreglo de equipos (cada uno es un arreglo asociativo).
     */
    public function listarEquiposPorPrestamo(int $idPrestamo): array {
        $sql = "
            SELECT pe.id, pe.noSeUso, e.numero AS numeroEquipo, sala.tipo AS tipoSala, sala.numero AS numeroSala
            FROM PRESTAMO_EQUIPO AS pe
            JOIN EQUIPO AS e ON e.id = pe.equipo_id
            JOIN SALA AS sala ON sala.id = e.idSala
            WHERE pe.prestamo_id = :idPrestamo
        ";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["idPrestamo" => $idPrestamo]);
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $consulta = null;

        $equipos = [];
        foreach ($filas as $fila) {
            $equipos[] = [
                "id" => (int) $fila["id"],
                "noSeUso" => (bool) $fila["noSeUso"],
                "numeroEquipo" => (int) $fila["numeroEquipo"],
                "tipoSala" => $fila["tipoSala"],
                "numeroSala" => (int) $fila["numeroSala"],
            ];
        }

        return $equipos;
    }

    /**
     * Ids de los equipos que están en un préstamo activo en este momento (no disponibles para prestar de nuevo).
     * @return array Arreglo de enteros.
     */
    public function listarIdsEquiposPrestados(): array {
        $sql = "
            SELECT pe.equipo_id
            FROM PRESTAMO_EQUIPO AS pe
            JOIN PRESTAMO AS p ON p.id = pe.prestamo_id
            WHERE p.estado = 'Activo'
        ";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute();
        $filas = $consulta->fetchAll(PDO::FETCH_COLUMN);
        $consulta = null;

        return array_map("intval", $filas);
    }

    /**
     * RF31: registra un evento en el historial de la solicitud de origen. Se invoca dentro de la transacción de quien modifica.
     * @throws PDOException Si falla, para que la operación completa se deshaga.
     */
    private function registrarEventoSolicitud(int $idSolicitud, ?string $cedula, string $accion, ?string $detalle): void {
        $sql = "
            INSERT INTO HISTORIAL_SOLICITUD (solicitud_id, cedula, accion, detalle)
            VALUES (:idSolicitud, :cedula, :accion, :detalle)
        ";
        $this->conexion->prepare($sql)->execute([
            "idSolicitud" => $idSolicitud, "cedula" => $cedula, "accion" => $accion, "detalle" => $detalle
        ]);
    }

    /**
     * Lista las salas con los equipos que no están en un préstamo activo, para elegir qué entregar.
     * Las salas que no tienen ningún equipo disponible no se incluyen.
     * @return array Arreglo de salas, cada una con su arreglo "equipos" (id y número de cada equipo).
     */
    public function listarEquiposDisponibles(): array {
        $sql = "
            SELECT sala.id AS idSala, sala.tipo, sala.numero AS numeroSala, e.id AS idEquipo, e.numero AS numeroEquipo
            FROM EQUIPO AS e
            JOIN SALA AS sala ON sala.id = e.idSala
            WHERE e.id NOT IN (
                SELECT pe.equipo_id
                FROM PRESTAMO_EQUIPO AS pe
                JOIN PRESTAMO AS p ON p.id = pe.prestamo_id
                WHERE p.estado = 'Activo'
            )
            ORDER BY sala.tipo, sala.numero, e.numero
        ";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute();
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $consulta = null;

        //Se agrupan los equipos por sala conservando el orden de la consulta
        $salas = [];
        foreach ($filas as $fila) {
            $idSala = (int) $fila["idSala"];

            if (!isset($salas[$idSala])) {
                $salas[$idSala] = [
                    "id" => $idSala,
                    "tipo" => $fila["tipo"],
                    "numero" => (int) $fila["numeroSala"],
                    "equipos" => [],
                ];
            }

            $salas[$idSala]["equipos"][] = ["id" => (int) $fila["idEquipo"], "numero" => (int) $fila["numeroEquipo"]];
        }

        return array_values($salas);
    }

    /**
     * Verifica si existe un equipo con ese identificador.
     * @param int $idEquipo Identificador del equipo.
     * @return bool TRUE si existe, FALSE en caso contrario.
     */
    public function existeEquipo(int $idEquipo): bool {
        $sql = "SELECT id FROM EQUIPO WHERE id = :idEquipo";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["idEquipo" => $idEquipo]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        $consulta = null;

        return $fila !== false;
    }

    /**
     * RF9, RF11, RF12, RF13, RF15: registra la entrega real de equipos de un préstamo.
     * Todo se hace en una transacción: el préstamo, sus equipos y el cambio de la solicitud de origen a "En proceso".
     *
     * @param int $idSolicitud Solicitud de tipo Préstamo que origina la entrega.
     * @param string $solicitanteNombre Nombre de quien retira los equipos.
     * @param string $solicitanteTipoDocumento Cédula, Pasaporte u Otro.
     * @param string $solicitanteDocumento Número de documento.
     * @param string $cedulaResponsable Cédula del usuario que registra la entrega.
     * @param array $idsEquipo Ids (enteros) de los equipos entregados.
     * @return int|null El identificador del préstamo creado, null si el registro falla.
     */
    public function registrarPrestamo(
        int $idSolicitud,
        string $solicitanteNombre,
        string $solicitanteTipoDocumento,
        string $solicitanteDocumento,
        string $cedulaResponsable,
        array $idsEquipo
    ): ?int {
        try {
            $this->conexion->beginTransaction();

            $sqlPrestamo = "
                INSERT INTO PRESTAMO (solicitud_id, solicitanteNombre, solicitanteTipoDocumento, solicitanteDocumento, cedulaResponsable)
                VALUES (:idSolicitud, :solicitanteNombre, :solicitanteTipoDocumento, :solicitanteDocumento, :cedulaResponsable)
            ";
            $consultaPrestamo = $this->conexion->prepare($sqlPrestamo);
            $consultaPrestamo->execute([
                "idSolicitud" => $idSolicitud,
                "solicitanteNombre" => $solicitanteNombre,
                "solicitanteTipoDocumento" => $solicitanteTipoDocumento,
                "solicitanteDocumento" => $solicitanteDocumento,
                "cedulaResponsable" => $cedulaResponsable,
            ]);

            $idPrestamo = (int) $this->conexion->lastInsertId();

            $sqlEquipo = "INSERT INTO PRESTAMO_EQUIPO (prestamo_id, equipo_id) VALUES (:idPrestamo, :idEquipo)";
            $consultaEquipo = $this->conexion->prepare($sqlEquipo);

            //RF5: el préstamo queda en el historial de cada equipo entregado
            $sqlHistorialEquipo = "
                INSERT INTO HISTORIAL_EQUIPO (equipo_id, cedula, accion, detalle)
                VALUES (:idEquipo, :cedula, 'Préstamo', :detalle)
            ";
            $consultaHistorialEquipo = $this->conexion->prepare($sqlHistorialEquipo);

            foreach ($idsEquipo as $idEquipo) {
                $consultaEquipo->execute(["idPrestamo" => $idPrestamo, "idEquipo" => $idEquipo]);
                $consultaHistorialEquipo->execute([
                    "idEquipo" => $idEquipo, "cedula" => $cedulaResponsable,
                    "detalle" => mb_substr("Préstamo #" . $idPrestamo . " a " . $solicitanteNombre, 0, 255)
                ]);
            }

            //La solicitud de origen pasa a "En proceso" mientras el préstamo está activo
            $sqlSolicitud = "UPDATE SOLICITUD SET estado = 'En proceso' WHERE id = :idSolicitud";
            $this->conexion->prepare($sqlSolicitud)->execute(["idSolicitud" => $idSolicitud]);

            //RF31: el préstamo y el cambio de estado quedan en el historial de la solicitud
            $this->registrarEventoSolicitud($idSolicitud, $cedulaResponsable, "Préstamo", "Préstamo #" . $idPrestamo . " registrado");
            $this->registrarEventoSolicitud($idSolicitud, $cedulaResponsable, "Estado", "Nuevo estado: En proceso");

            $this->conexion->commit();
            return $idPrestamo;

        } catch (PDOException $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            return null;
        }
    }

    /**
     * RF10, RF14: registra la devolución de un préstamo.
     * En una transacción: finaliza el préstamo, marca los equipos "No se usó" y resuelve la solicitud de origen.
     *
     * @param int $idPrestamo Identificador del préstamo.
     * @param array $idsNoSeUso Ids (enteros) de PRESTAMO_EQUIPO que se marcan como "No se usó".
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function registrarDevolucion(int $idPrestamo, array $idsNoSeUso, ?string $cedulaActor = null): bool {
        try {
            $this->conexion->beginTransaction();

            $sqlPrestamo = "UPDATE PRESTAMO SET fechaHoraFin = NOW(), estado = 'Finalizado' WHERE id = :idPrestamo";
            $this->conexion->prepare($sqlPrestamo)->execute(["idPrestamo" => $idPrestamo]);

            if (count($idsNoSeUso) > 0) {
                $marcadores = implode(",", array_fill(0, count($idsNoSeUso), "?"));
                $sqlNoSeUso = "UPDATE PRESTAMO_EQUIPO SET noSeUso = TRUE WHERE id IN ($marcadores) AND prestamo_id = ?";
                $consultaNoSeUso = $this->conexion->prepare($sqlNoSeUso);
                $consultaNoSeUso->execute([...$idsNoSeUso, $idPrestamo]);
            }

            //Al devolverse el préstamo, se marca la solicitud de origen como resuelta
            $sqlSolicitud = "
                UPDATE SOLICITUD SET estado = 'Resuelta'
                WHERE id = (SELECT solicitud_id FROM PRESTAMO WHERE id = :idPrestamo)
            ";
            $this->conexion->prepare($sqlSolicitud)->execute(["idPrestamo" => $idPrestamo]);

            //RF5: la devolución queda en el historial de cada equipo del préstamo
            $sqlEquipos = "SELECT equipo_id, noSeUso FROM PRESTAMO_EQUIPO WHERE prestamo_id = :idPrestamo";
            $consultaEquipos = $this->conexion->prepare($sqlEquipos);
            $consultaEquipos->execute(["idPrestamo" => $idPrestamo]);

            $sqlHistorialEquipo = "
                INSERT INTO HISTORIAL_EQUIPO (equipo_id, cedula, accion, detalle)
                VALUES (:idEquipo, :cedula, 'Devolución', :detalle)
            ";
            $consultaHistorialEquipo = $this->conexion->prepare($sqlHistorialEquipo);

            foreach ($consultaEquipos->fetchAll(PDO::FETCH_ASSOC) as $equipo) {
                $consultaHistorialEquipo->execute([
                    "idEquipo" => $equipo["equipo_id"], "cedula" => $cedulaActor,
                    "detalle" => "Préstamo #" . $idPrestamo . ($equipo["noSeUso"] ? " - No se usó" : "")
                ]);
            }

            //RF31: la devolución y la resolución quedan en el historial de la solicitud de origen
            $sqlOrigen = "SELECT solicitud_id FROM PRESTAMO WHERE id = :idPrestamo";
            $consultaOrigen = $this->conexion->prepare($sqlOrigen);
            $consultaOrigen->execute(["idPrestamo" => $idPrestamo]);
            $idSolicitudOrigen = (int) $consultaOrigen->fetchColumn();

            $this->registrarEventoSolicitud($idSolicitudOrigen, $cedulaActor, "Devolución", "Préstamo #" . $idPrestamo . " devuelto");
            $this->registrarEventoSolicitud($idSolicitudOrigen, $cedulaActor, "Estado", "Nuevo estado: Resuelta");

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
     * Modifica los datos de quien retiró los equipos. No cambia los equipos ni el estado del préstamo.
     * @param int $idPrestamo Identificador del préstamo.
     * @param string $solicitanteNombre Nombre de quien retira los equipos.
     * @param string $solicitanteTipoDocumento Cédula, Pasaporte u Otro.
     * @param string $solicitanteDocumento Número de documento.
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function modificarPrestamo(
        int $idPrestamo,
        string $solicitanteNombre,
        string $solicitanteTipoDocumento,
        string $solicitanteDocumento
    ): bool {
        try {
            $sql = "
                UPDATE PRESTAMO
                SET solicitanteNombre = :solicitanteNombre, solicitanteTipoDocumento = :solicitanteTipoDocumento,
                    solicitanteDocumento = :solicitanteDocumento
                WHERE id = :idPrestamo
            ";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute([
                "solicitanteNombre" => $solicitanteNombre,
                "solicitanteTipoDocumento" => $solicitanteTipoDocumento,
                "solicitanteDocumento" => $solicitanteDocumento,
                "idPrestamo" => $idPrestamo,
            ]);

            return true;
        } catch (PDOException $error) {
            return false;
        }
    }

    /**
     * Elimina un préstamo. Sus equipos (PRESTAMO_EQUIPO) se eliminan en cascada, por lo que los equipos
     * de un préstamo activo vuelven a estar disponibles. No modifica el estado de la solicitud de origen.
     * @param int $idPrestamo Identificador del préstamo.
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function eliminarPrestamo(int $idPrestamo): bool {
        try {
            $sql = "DELETE FROM PRESTAMO WHERE id = :idPrestamo";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["idPrestamo" => $idPrestamo]);

            return true;
        } catch (PDOException $error) {
            return false;
        }
    }

    /**
     * Convierte una fila de la consulta de préstamos en un arreglo con los tipos correctos
     * (números como int) y con el nombre del responsable ya unido.
     */
    private function mapearPrestamo(array $fila): array {
        return [
            "id" => (int) $fila["id"],
            "solicitanteNombre" => $fila["solicitanteNombre"],
            "solicitanteTipoDocumento" => $fila["solicitanteTipoDocumento"],
            "solicitanteDocumento" => $fila["solicitanteDocumento"],
            "fechaHoraInicio" => $fila["fechaHoraInicio"],
            //null mientras el préstamo siga activo
            "fechaHoraFin" => $fila["fechaHoraFin"],
            "estado" => $fila["estado"],
            "responsable" => $fila["responsableNombre"] . " " . $fila["responsableApellido"],
            "idSolicitud" => (int) $fila["idSolicitud"],
        ];
    }
}
