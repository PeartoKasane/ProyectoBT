<?php

/**
 * Clase que unifica todas las operaciones de acceso a datos
 * relacionadas con las solicitudes (consulta, alta, estado, asignación y comentarios).
 *
 * DAO ~ Data Access Object. Reúne en una sola clase todo el acceso a datos de la entidad.
 *
 * Los métodos de consulta retornan arreglos asociativos
 * (listos para convertir a JSON) en lugar de objetos Solicitud o Comentario.
 */
class SolicitudDAO {
    private PDO $conexion;

    //Consulta base compartida por todas las consultas de solicitudes, cada método le agrega su WHERE y su ORDER BY
    private const SELECT_SOLICITUD = "
        SELECT
            s.id, s.tipo, s.programa, s.detalle, s.fecha, s.hora, s.estado,
            s.idSala, s.cedulaDocente, s.cedulaAsignado,
            sala.tipo AS tipoSala, sala.numero AS numeroSala,
            doc.nombre AS docenteNombre, doc.apellido AS docenteApellido,
            asig.nombre AS asignadoNombre, asig.apellido AS asignadoApellido
        FROM SOLICITUD AS s
        LEFT JOIN SALA AS sala ON sala.id = s.idSala
        JOIN USUARIO AS doc ON doc.cedula = s.cedulaDocente
        LEFT JOIN USUARIO AS asig ON asig.cedula = s.cedulaAsignado
    ";

    /**
     * Constructor parametrizado que recibe una conexión a la base de datos.
     * @param PDO $conexion La conexión a la base de datos. PRECONDICIÓN: No debe ser NULL.
     */
    public function __construct(PDO $conexion) {
        $this->conexion = $conexion;
    }

    /**
     * RF29: Lista todas las solicitudes, de la más reciente a la más antigua.
     * @return array Arreglo de solicitudes (cada una es un arreglo asociativo).
     */
    public function listarSolicitudes(): array {
        $sql = self::SELECT_SOLICITUD . " ORDER BY s.fechaCreacion DESC";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute();
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $consulta = null;

        return $this->mapearSolicitudes($filas);
    }

    /**
     * Igual que listarSolicitudes(), pero solo con las solicitudes de un docente puntual.
     * @param string $cedulaDocente Cédula del docente.
     * @return array Arreglo de solicitudes (cada una es un arreglo asociativo).
     */
    public function listarSolicitudesPorDocente(string $cedulaDocente): array {
        $sql = self::SELECT_SOLICITUD . " WHERE s.cedulaDocente = :cedulaDocente ORDER BY s.fechaCreacion DESC";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["cedulaDocente" => $cedulaDocente]);
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $consulta = null;

        return $this->mapearSolicitudes($filas);
    }

    /**
     * Busca una solicitud por su identificador.
     * @param int $idSolicitud Identificador de la solicitud.
     * @return array|null La solicitud como arreglo asociativo, null si no existe.
     */
    public function listarSolicitud(int $idSolicitud): ?array {
        $sql = self::SELECT_SOLICITUD . " WHERE s.id = :idSolicitud";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["idSolicitud" => $idSolicitud]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        $consulta = null;

        if ($fila === false) {
            return null;
        }

        return $this->mapearSolicitud($fila);
    }

    /**
     * Lista los comentarios de una solicitud, del más reciente al más antiguo.
     * @param int $idSolicitud Identificador de la solicitud.
     * @return array Arreglo de comentarios (cada uno es un arreglo asociativo).
     */
    public function listarComentarios(int $idSolicitud): array {
        $sql = "
            SELECT sc.id, sc.comentario, sc.fecha, sc.cedula, u.nombre, u.apellido
            FROM SOLICITUD_COMENTARIO AS sc
            LEFT JOIN USUARIO AS u ON u.cedula = sc.cedula
            WHERE sc.solicitud_id = :idSolicitud
            ORDER BY sc.fecha DESC
        ";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["idSolicitud" => $idSolicitud]);
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $consulta = null;

        $comentarios = [];
        foreach ($filas as $fila) {
            $comentarios[] = [
                "id" => (int) $fila["id"],
                "comentario" => $fila["comentario"],
                "fecha" => $fila["fecha"],
                //Cédula del autor, null si el usuario fue eliminado. Permite saber quién puede editar o eliminar el comentario
                "cedula" => $fila["cedula"],
                //Si el usuario fue eliminado, la clave foránea queda en NULL y se muestra un texto genérico
                "usuario" => $fila["nombre"] === null ? "Usuario eliminado" : $fila["nombre"] . " " . $fila["apellido"],
            ];
        }

        return $comentarios;
    }

    /**
     * RF31: lista las acciones realizadas sobre una solicitud, de la más reciente a la más antigua.
     * @param int $idSolicitud Identificador de la solicitud.
     * @return array Eventos con id, accion, detalle, fecha, nombre y apellido de quien los realizó.
     */
    public function listarHistorial(int $idSolicitud): array {
        $sql = "
            SELECT h.id, h.accion, h.detalle, h.fecha, u.nombre, u.apellido
            FROM HISTORIAL_SOLICITUD AS h
            LEFT JOIN USUARIO AS u ON u.cedula = h.cedula
            WHERE h.solicitud_id = :idSolicitud
            ORDER BY h.fecha DESC, h.id DESC
        ";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["idSolicitud" => $idSolicitud]);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * RF31: registra un evento en el historial de la solicitud. Se invoca dentro de la transacción de quien modifica.
     * @throws PDOException Si falla, para que la operación completa se deshaga.
     */
    private function registrarEvento(int $idSolicitud, ?string $cedula, string $accion, ?string $detalle): void {
        $sql = "
            INSERT INTO HISTORIAL_SOLICITUD (solicitud_id, cedula, accion, detalle)
            VALUES (:idSolicitud, :cedula, :accion, :detalle)
        ";
        $this->conexion->prepare($sql)->execute([
            "idSolicitud" => $idSolicitud, "cedula" => $cedula, "accion" => $accion, "detalle" => $detalle
        ]);
    }

    /**
     * Lista las salas registradas, para que el docente elija el laboratorio o sala de su solicitud.
     * @return array Arreglo de salas con id, tipo y número.
     */
    public function listarSalas(): array {
        $sql = "SELECT id, tipo, numero FROM SALA ORDER BY tipo, numero";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute();
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $consulta = null;

        $salas = [];
        foreach ($filas as $fila) {
            $salas[] = [
                "id" => (int) $fila["id"],
                "tipo" => $fila["tipo"],
                "numero" => (int) $fila["numero"],
            ];
        }

        return $salas;
    }

    /**
     * Verifica si existe una sala con ese identificador.
     * @param int $idSala Identificador de la sala.
     * @return bool TRUE si existe, FALSE en caso contrario.
     */
    public function existeSala(int $idSala): bool {
        $sql = "SELECT id FROM SALA WHERE id = :idSala";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["idSala" => $idSala]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        $consulta = null;

        return $fila !== false;
    }

    /**
     * Lista los usuarios con rol Técnico, para que un administrador pueda elegir a quién asignar una solicitud.
     * @return array Arreglo con cédula, nombre y apellido de cada técnico.
     */
    public function listarTecnicos(): array {
        $sql = "SELECT u.cedula, u.nombre, u.apellido FROM USUARIO AS u JOIN TECNICO AS t ON t.cedula = u.cedula ORDER BY u.nombre";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute();
        $tecnicos = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $consulta = null;

        return $tecnicos;
    }

    /**
     * Verifica si una cédula pertenece a un usuario con rol Técnico.
     * @param string $cedula Cédula del usuario.
     * @return bool TRUE si es técnico, FALSE en caso contrario.
     */
    public function esTecnico(string $cedula): bool {
        $sql = "SELECT cedula FROM TECNICO WHERE cedula = :cedula";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["cedula" => $cedula]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        $consulta = null;

        return $fila !== false;
    }

    /**
     * RF27, RF28: registra una nueva solicitud.
     * @param string $tipo "Preparación de laboratorio" o "Préstamo".
     * @param string $cedulaDocente Cédula del docente que la genera.
     * @param int|null $idSala Sala involucrada (opcional en un préstamo).
     * @param string|null $programa Programa que necesita (solo en una preparación de laboratorio).
     * @param string $detalle Descripción de lo que se necesita.
     * @param string $fecha Fecha en que se necesita (AAAA-MM-DD).
     * @param string $hora Hora en que se necesita (HH:MM:SS).
     * @return int|null El identificador de la solicitud creada, null si el registro falla.
     */
    public function registrarSolicitud(
        string $tipo,
        string $cedulaDocente,
        ?int $idSala,
        ?string $programa,
        string $detalle,
        string $fecha,
        string $hora
    ): ?int {
        try {
            $this->conexion->beginTransaction();

            $sql = "
                INSERT INTO SOLICITUD (tipo, cedulaDocente, idSala, programa, detalle, fecha, hora)
                VALUES (:tipo, :cedulaDocente, :idSala, :programa, :detalle, :fecha, :hora)
            ";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute([
                "tipo" => $tipo, "cedulaDocente" => $cedulaDocente, "idSala" => $idSala,
                "programa" => $programa, "detalle" => $detalle, "fecha" => $fecha, "hora" => $hora
            ]);

            $idSolicitud = (int) $this->conexion->lastInsertId();

            //RF31: queda registrada la creación de la solicitud
            $this->registrarEvento($idSolicitud, $cedulaDocente, "Creación", $tipo);

            $this->conexion->commit();
            return $idSolicitud;
        } catch (PDOException $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            return null;
        }
    }

    /**
     * RF29: cambia el estado de una solicitud.
     * @param int $idSolicitud Identificador de la solicitud.
     * @param string $estado Pendiente, En proceso o Resuelta.
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function cambiarEstado(int $idSolicitud, string $estado, ?string $cedulaActor = null): bool {
        try {
            $this->conexion->beginTransaction();

            $sql = "UPDATE SOLICITUD SET estado = :estado WHERE id = :idSolicitud";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["estado" => $estado, "idSolicitud" => $idSolicitud]);

            //RF31: queda registrado el cambio de estado y quién lo hizo
            $this->registrarEvento($idSolicitud, $cedulaActor, "Estado", "Nuevo estado: " . $estado);

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
     * RF30: asigna un responsable a la solicitud.
     * La regla de quién puede asignar a quién (técnico solo a sí mismo, administrador a cualquier técnico)
     * se valida en el controlador, no acá.
     *
     * @param int $idSolicitud Identificador de la solicitud.
     * @param string $cedula Cédula del responsable.
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function asignarResponsable(int $idSolicitud, string $cedula, ?string $cedulaActor = null): bool {
        try {
            $this->conexion->beginTransaction();

            //Al asignarse, la solicitud pasa automáticamente a "En proceso" si todavía estaba "Pendiente"
            $sql = "
                UPDATE SOLICITUD
                SET cedulaAsignado = :cedula, estado = IF(estado = 'Pendiente', 'En proceso', estado)
                WHERE id = :idSolicitud
            ";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["cedula" => $cedula, "idSolicitud" => $idSolicitud]);

            //RF31: queda registrada la asignación y quién la hizo
            $this->registrarEvento($idSolicitud, $cedulaActor, "Asignación", "Asignada a la cédula " . $cedula);

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
     * RF31: agrega un comentario de seguimiento a una solicitud.
     * @param int $idSolicitud Identificador de la solicitud.
     * @param string|null $cedula Cédula de quien comenta.
     * @param string $comentario Texto del comentario.
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function agregarComentario(int $idSolicitud, ?string $cedula, string $comentario): bool {
        try {
            $sql = "INSERT INTO SOLICITUD_COMENTARIO (solicitud_id, cedula, comentario) VALUES (:idSolicitud, :cedula, :comentario)";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["idSolicitud" => $idSolicitud, "cedula" => $cedula, "comentario" => $comentario]);

            return true;
        } catch (PDOException $error) {
            return false;
        }
    }

    /**
     * Busca un comentario de solicitud por su identificador.
     * @param int $idComentario Identificador del comentario.
     * @return array|null El comentario (id, idSolicitud, cedula del autor, texto), null si no existe.
     */
    public function listarComentario(int $idComentario): ?array {
        $sql = "SELECT id, solicitud_id, cedula, comentario FROM SOLICITUD_COMENTARIO WHERE id = :idComentario";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["idComentario" => $idComentario]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        $consulta = null;

        if ($fila === false) {
            return null;
        }

        return [
            "id" => (int) $fila["id"],
            "idSolicitud" => (int) $fila["solicitud_id"],
            "cedula" => $fila["cedula"],
            "comentario" => $fila["comentario"],
        ];
    }

    /**
     * Modifica el texto de un comentario de solicitud.
     * @param int $idComentario Identificador del comentario.
     * @param string $comentario Nuevo texto.
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function modificarComentario(int $idComentario, string $comentario): bool {
        try {
            $sql = "UPDATE SOLICITUD_COMENTARIO SET comentario = :comentario WHERE id = :idComentario";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["comentario" => $comentario, "idComentario" => $idComentario]);

            return true;
        } catch (PDOException $error) {
            return false;
        }
    }

    /**
     * Elimina un comentario de solicitud.
     * @param int $idComentario Identificador del comentario.
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function eliminarComentario(int $idComentario): bool {
        try {
            $sql = "DELETE FROM SOLICITUD_COMENTARIO WHERE id = :idComentario";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["idComentario" => $idComentario]);

            return true;
        } catch (PDOException $error) {
            return false;
        }
    }

    /**
     * Modifica los datos de una solicitud (no su estado ni su responsable, que tienen sus propias operaciones).
     * Los parámetros tienen el mismo significado que en registrarSolicitud().
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function modificarSolicitud(
        int $idSolicitud,
        string $tipo,
        ?int $idSala,
        ?string $programa,
        string $detalle,
        string $fecha,
        string $hora,
        ?string $cedulaActor = null
    ): bool {
        try {
            $this->conexion->beginTransaction();

            $sql = "
                UPDATE SOLICITUD
                SET tipo = :tipo, idSala = :idSala, programa = :programa, detalle = :detalle, fecha = :fecha, hora = :hora
                WHERE id = :idSolicitud
            ";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute([
                "tipo" => $tipo, "idSala" => $idSala, "programa" => $programa, "detalle" => $detalle,
                "fecha" => $fecha, "hora" => $hora, "idSolicitud" => $idSolicitud
            ]);

            //RF31: queda registrada la modificación de los datos de la solicitud
            $this->registrarEvento($idSolicitud, $cedulaActor, "Modificación", $tipo);

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
     * Verifica si una solicitud tiene algún préstamo registrado (activo o finalizado).
     * PRESTAMO referencia a SOLICITUD sin borrado en cascada, por lo que una solicitud con préstamos no se puede eliminar.
     * @param int $idSolicitud Identificador de la solicitud.
     * @return bool TRUE si tiene al menos un préstamo, FALSE en caso contrario.
     */
    public function tienePrestamos(int $idSolicitud): bool {
        $sql = "SELECT id FROM PRESTAMO WHERE solicitud_id = :idSolicitud LIMIT 1";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["idSolicitud" => $idSolicitud]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        $consulta = null;

        return $fila !== false;
    }

    /**
     * Elimina una solicitud. Sus comentarios se eliminan en cascada.
     * @param int $idSolicitud Identificador de la solicitud.
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario (por ejemplo, si un préstamo la referencia).
     */
    public function eliminarSolicitud(int $idSolicitud): bool {
        try {
            $sql = "DELETE FROM SOLICITUD WHERE id = :idSolicitud";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["idSolicitud" => $idSolicitud]);

            return true;
        } catch (PDOException $error) {
            return false;
        }
    }

    /**
     * Busca un evento del historial de una solicitud por su identificador.
     * @param int $idEvento Identificador del evento.
     * @return array|null El evento (id, solicitud_id, accion, detalle), null si no existe.
     */
    public function listarEventoHistorial(int $idEvento): ?array {
        $sql = "SELECT id, solicitud_id, accion, detalle FROM HISTORIAL_SOLICITUD WHERE id = :idEvento";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["idEvento" => $idEvento]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        $consulta = null;

        if ($fila === false) {
            return null;
        }

        return [
            "id" => (int) $fila["id"],
            "idSolicitud" => (int) $fila["solicitud_id"],
            "accion" => $fila["accion"],
            "detalle" => $fila["detalle"],
        ];
    }

    /**
     * Corrige el texto de un evento del historial de una solicitud. Es una operación exclusiva del administrador:
     * la fecha y el usuario que lo originó nunca se modifican, para no alterar la trazabilidad.
     * @param int $idEvento Identificador del evento.
     * @param string $accion Nueva acción (hasta 30 caracteres).
     * @param string|null $detalle Nuevo detalle (hasta 255 caracteres), null para dejarlo vacío.
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function modificarEventoHistorial(int $idEvento, string $accion, ?string $detalle): bool {
        try {
            $sql = "UPDATE HISTORIAL_SOLICITUD SET accion = :accion, detalle = :detalle WHERE id = :idEvento";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["accion" => $accion, "detalle" => $detalle, "idEvento" => $idEvento]);

            return true;
        } catch (PDOException $error) {
            return false;
        }
    }

    /**
     * Elimina un evento del historial de una solicitud. Es una operación exclusiva del administrador.
     * @param int $idEvento Identificador del evento.
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function eliminarEventoHistorial(int $idEvento): bool {
        try {
            $sql = "DELETE FROM HISTORIAL_SOLICITUD WHERE id = :idEvento";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["idEvento" => $idEvento]);

            return true;
        } catch (PDOException $error) {
            return false;
        }
    }

    /**
     * Convierte todas las filas de la consulta de solicitudes en arreglos listos para JSON.
     */
    private function mapearSolicitudes(array $filas): array {
        $solicitudes = [];

        foreach ($filas as $fila) {
            $solicitudes[] = $this->mapearSolicitud($fila);
        }

        return $solicitudes;
    }

    /**
     * Convierte una fila de la consulta de solicitudes en un arreglo con los tipos correctos
     * (números como int) y con los nombres ya unidos.
     */
    private function mapearSolicitud(array $fila): array {
        return [
            "id" => (int) $fila["id"],
            "tipo" => $fila["tipo"],
            "programa" => $fila["programa"],
            "detalle" => $fila["detalle"],
            "fecha" => $fila["fecha"],
            "hora" => $fila["hora"],
            "estado" => $fila["estado"],
            //null si la solicitud no indica una sala (posible en un préstamo)
            "idSala" => $fila["idSala"] !== null ? (int) $fila["idSala"] : null,
            "tipoSala" => $fila["tipoSala"],
            "numeroSala" => $fila["numeroSala"] !== null ? (int) $fila["numeroSala"] : null,
            "cedulaDocente" => $fila["cedulaDocente"],
            "docente" => $fila["docenteNombre"] . " " . $fila["docenteApellido"],
            "cedulaAsignado" => $fila["cedulaAsignado"],
            //null mientras ningún responsable la haya tomado
            "asignado" => $fila["asignadoNombre"] === null ? null : $fila["asignadoNombre"] . " " . $fila["asignadoApellido"],
        ];
    }
}
