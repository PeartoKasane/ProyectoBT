<?php

/**
 * Clase que unifica todas las operaciones de acceso a datos
 * relacionadas con los tickets de incidencias (consulta, alta, edición, baja, estado, asignación y comentarios).
 *
 * DAO ~ Data Access Object. Reúne en una sola clase todo el acceso a datos de la entidad.
 *
 * Los métodos de consulta retornan arreglos asociativos
 * (listos para convertir a JSON) en lugar de objetos Ticket, TicketEquipo o Comentario.
 */
class TicketDAO {
    private PDO $conexion;

    //La prioridad real depende del tipo de incidencia. 0 es la más urgente y 2 es la menos urgente (RF19).
    private const PRIORIDADES_POR_INCIDENCIA = [
        "No prende" => 0,
        "Teclado roto" => 1,
        "Mouse roto" => 2,
        "Monitor roto" => 2,
        "Otro problema" => 2,
    ];

    //RF24: si un mismo equipo repite incidencias dentro de esta cantidad de semanas, se sube su prioridad.
    private const SEMANAS_REINCIDENCIA = 4;

    //Consulta base compartida por todas las consultas de tickets, cada método le agrega su WHERE y su ORDER BY
    private const SELECT_TICKET = "
        SELECT
            t.id, t.idSala, t.fecha, t.horaEntrada, t.horaSalida, t.asignatura, t.grupo, t.turno,
            t.prioridad, t.estado, t.cedulaDocente, t.cedulaAsignado,
            s.tipo AS tipoSala, s.numero AS numeroSala,
            doc.nombre AS docenteNombre, doc.apellido AS docenteApellido,
            asig.nombre AS asignadoNombre, asig.apellido AS asignadoApellido
        FROM TICKET AS t
        JOIN SALA AS s ON s.id = t.idSala
        JOIN USUARIO AS doc ON doc.cedula = t.cedulaDocente
        LEFT JOIN USUARIO AS asig ON asig.cedula = t.cedulaAsignado
    ";

    /**
     * Constructor parametrizado que recibe una conexión a la base de datos.
     * @param PDO $conexion La conexión a la base de datos. PRECONDICIÓN: No debe ser NULL.
     */
    public function __construct(PDO $conexion) {
        $this->conexion = $conexion;
    }

    /**
     * RF26: Lista todos los tickets, del de mayor prioridad (más urgente) y más reciente primero.
     * @return array Arreglo de tickets (cada uno es un arreglo asociativo).
     */
    public function listarTickets(): array {
        $sql = self::SELECT_TICKET . " ORDER BY t.prioridad ASC, t.fechaCreacion DESC";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute();
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $consulta = null;

        return $this->mapearTickets($filas);
    }

    /**
     * Igual que listarTickets(), pero solo con los tickets de un docente puntual.
     * @param string $cedulaDocente Cédula del docente.
     * @return array Arreglo de tickets (cada uno es un arreglo asociativo).
     */
    public function listarTicketsPorDocente(string $cedulaDocente): array {
        $sql = self::SELECT_TICKET . " WHERE t.cedulaDocente = :cedulaDocente ORDER BY t.fechaCreacion DESC";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["cedulaDocente" => $cedulaDocente]);
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $consulta = null;

        return $this->mapearTickets($filas);
    }

    /**
     * Busca un ticket por su identificador.
     * @param int $idTicket Identificador del ticket.
     * @return array|null El ticket como arreglo asociativo, null si no existe.
     */
    public function listarTicket(int $idTicket): ?array {
        $sql = self::SELECT_TICKET . " WHERE t.id = :idTicket";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["idTicket" => $idTicket]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        $consulta = null;

        if ($fila === false) {
            return null;
        }

        return $this->mapearTicket($fila);
    }

    /**
     * Lista los equipos declarados en la planilla de un ticket.
     * @param int $idTicket Identificador del ticket.
     * @return array Arreglo de equipos (cada uno es un arreglo asociativo).
     */
    public function listarEquiposPorTicket(int $idTicket): array {
        $sql = "
            SELECT te.id, te.equipo_id, te.utilizado, te.estudiante, te.incidencia, te.descripcion, e.numero AS numeroEquipo
            FROM TICKET_EQUIPO AS te
            JOIN EQUIPO AS e ON e.id = te.equipo_id
            WHERE te.ticket_id = :idTicket
        ";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["idTicket" => $idTicket]);
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $consulta = null;

        $equipos = [];
        foreach ($filas as $fila) {
            $equipos[] = [
                "id" => (int) $fila["id"],
                "idEquipo" => (int) $fila["equipo_id"],
                "numeroEquipo" => (int) $fila["numeroEquipo"],
                "utilizado" => (bool) $fila["utilizado"],
                "estudiante" => $fila["estudiante"],
                "incidencia" => $fila["incidencia"],
                "descripcion" => $fila["descripcion"],
            ];
        }

        return $equipos;
    }

    /**
     * RF23: Lista los comentarios de un ticket, del más reciente al más antiguo.
     * @param int $idTicket Identificador del ticket.
     * @return array Arreglo de comentarios (cada uno es un arreglo asociativo).
     */
    public function listarComentarios(int $idTicket): array {
        $sql = "
            SELECT tc.id, tc.cedula, tc.comentario, tc.fecha, u.nombre, u.apellido
            FROM TICKET_COMENTARIO AS tc
            LEFT JOIN USUARIO AS u ON u.cedula = tc.cedula
            WHERE tc.ticket_id = :idTicket
            ORDER BY tc.fecha DESC
        ";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["idTicket" => $idTicket]);
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $consulta = null;

        $comentarios = [];
        foreach ($filas as $fila) {
            $comentarios[] = [
                "id" => (int) $fila["id"],
                //La cédula del autor permite al frontend saber si el comentario es propio; null si el usuario fue eliminado
                "cedula" => $fila["cedula"],
                "comentario" => $fila["comentario"],
                "fecha" => $fila["fecha"],
                //Si el usuario fue eliminado, la clave foránea queda en NULL y se muestra un texto genérico
                "usuario" => $fila["nombre"] === null ? "Usuario eliminado" : $fila["nombre"] . " " . $fila["apellido"],
            ];
        }

        return $comentarios;
    }

    /**
     * Lista los usuarios con rol Técnico, para que un administrador pueda elegir a quién asignar un ticket.
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
     * Verifica que un equipo exista y pertenezca a la sala indicada.
     * @param int $idEquipo Identificador del equipo.
     * @param int $idSala Identificador de la sala.
     * @return bool TRUE si el equipo pertenece a la sala, FALSE en caso contrario.
     */
    public function equipoPerteneceASala(int $idEquipo, int $idSala): bool {
        $sql = "SELECT id FROM EQUIPO WHERE id = :idEquipo AND idSala = :idSala";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["idEquipo" => $idEquipo, "idSala" => $idSala]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        $consulta = null;

        return $fila !== false;
    }

    /**
     * Calcula la prioridad inicial de un ticket a partir de las incidencias declaradas en la planilla (RF19).
     * @param array $equipos Mismo formato que registrarTicket().
     * @return int 0 (urgente), 1 (alta) o 2 (normal).
     */
    private function calcularPrioridad(array $equipos): int {
        $prioridad = 2;

        foreach ($equipos as $equipo) {
            if ($equipo["incidencia"] !== null) {
                $prioridadIncidencia = self::PRIORIDADES_POR_INCIDENCIA[$equipo["incidencia"]] ?? 2;
                $prioridad = min($prioridad, $prioridadIncidencia);
            }
        }

        return $prioridad;
    }

    /**
     * Inserta las filas de TICKET_EQUIPO de un ticket y aplica la regla de reincidencia (RF24).
     * Debe llamarse dentro de una transacción abierta por quien la invoca.
     *
     * @param int $idTicket Ticket al que pertenecen los equipos.
     * @param array $equipos Mismo formato que registrarTicket().
     * @param int $prioridad Prioridad calculada con calcularPrioridad().
     *
     * @return int La prioridad final del ticket (ya subida un nivel si hubo reincidencia).
     */
    private function guardarEquipos(int $idTicket, array $equipos, int $prioridad): int {
        $sqlEquipo = "
            INSERT INTO TICKET_EQUIPO (ticket_id, equipo_id, utilizado, estudiante, incidencia, descripcion)
            VALUES (:idTicket, :idEquipo, :utilizado, :estudiante, :incidencia, :descripcion)
        ";
        $consultaEquipo = $this->conexion->prepare($sqlEquipo);

        $sqlReincidencia = "
            SELECT COUNT(*) AS total
            FROM TICKET_EQUIPO AS te
            JOIN TICKET AS t ON t.id = te.ticket_id
            WHERE te.equipo_id = :idEquipo
                AND te.incidencia IS NOT NULL
                AND t.fechaCreacion >= DATE_SUB(NOW(), INTERVAL :semanas WEEK)
        ";
        $consultaReincidencia = $this->conexion->prepare($sqlReincidencia);

        $huboReincidencia = false;

        foreach ($equipos as $equipo) {
            $consultaEquipo->execute([
                "idTicket" => $idTicket,
                "idEquipo" => $equipo["idEquipo"],
                //(int) porque execute() envía los valores como texto, y un FALSE se enviaría como "" en vez de 0
                "utilizado" => (int) $equipo["utilizado"],
                "estudiante" => $equipo["estudiante"],
                "incidencia" => $equipo["incidencia"],
                "descripcion" => $equipo["descripcion"],
            ]);

            if ($equipo["incidencia"] !== null) {
                $consultaReincidencia->execute(["idEquipo" => $equipo["idEquipo"], "semanas" => self::SEMANAS_REINCIDENCIA]);
                $totalReincidencias = (int) $consultaReincidencia->fetch(PDO::FETCH_ASSOC)["total"];

                //Ya incluye el ticket recién insertado, por lo que 2 o más significa que se repitió
                if ($totalReincidencias >= 2) {
                    $huboReincidencia = true;
                }
            }
        }

        //RF24: si el mismo equipo tuvo incidencias repetidas en las últimas semanas, se aumenta la prioridad del ticket
        if ($huboReincidencia && $prioridad > 0) {
            $prioridad--;
        }

        return $prioridad;
    }

    /**
     * Registra un nuevo ticket junto con el detalle de sus equipos.
     * La prioridad se calcula a partir de las incidencias declaradas (RF19) y se sube si hay reincidencia (RF24).
     *
     * @param int $idSala Sala utilizada.
     * @param string $fecha Fecha de uso (AAAA-MM-DD).
     * @param string $horaEntrada Hora de entrada (HH:MM:SS).
     * @param string $horaSalida Hora de salida (HH:MM:SS).
     * @param string $asignatura Asignatura dictada.
     * @param string $cedulaDocente Cédula del docente que completa la planilla.
     * @param string $grupo Grupo.
     * @param string $turno Mañana, Vespertino o Nocturno.
     * @param array $equipos Cada elemento: ["idEquipo" => int, "utilizado" => bool, "estudiante" => ?string, "incidencia" => ?string, "descripcion" => ?string]
     *
     * @return int|null El identificador del ticket creado, null si el registro falla.
     */
    public function registrarTicket(
        int $idSala,
        string $fecha,
        string $horaEntrada,
        string $horaSalida,
        string $asignatura,
        string $cedulaDocente,
        string $grupo,
        string $turno,
        array $equipos
    ): ?int {
        try {
            $this->conexion->beginTransaction();

            $prioridad = $this->calcularPrioridad($equipos);

            $sqlTicket = "
                INSERT INTO TICKET (idSala, fecha, horaEntrada, horaSalida, asignatura, cedulaDocente, grupo, turno, prioridad)
                VALUES (:idSala, :fecha, :horaEntrada, :horaSalida, :asignatura, :cedulaDocente, :grupo, :turno, :prioridad)
            ";
            $consultaTicket = $this->conexion->prepare($sqlTicket);
            $consultaTicket->execute([
                "idSala" => $idSala, "fecha" => $fecha, "horaEntrada" => $horaEntrada, "horaSalida" => $horaSalida,
                "asignatura" => $asignatura, "cedulaDocente" => $cedulaDocente, "grupo" => $grupo, "turno" => $turno,
                "prioridad" => $prioridad
            ]);

            $idTicket = (int) $this->conexion->lastInsertId();

            $prioridadFinal = $this->guardarEquipos($idTicket, $equipos, $prioridad);

            //RF5: queda en el historial de cada equipo utilizado el ticket en el que se usó
            $sqlHistorial = "
                INSERT INTO HISTORIAL_EQUIPO (equipo_id, cedula, accion, detalle)
                VALUES (:idEquipo, :cedula, 'Uso en ticket', :detalle)
            ";
            $consultaHistorial = $this->conexion->prepare($sqlHistorial);

            foreach ($equipos as $equipo) {
                if ($equipo["utilizado"]) {
                    $detalle = "Ticket #" . $idTicket . ($equipo["incidencia"] !== null ? " - Incidencia: " . $equipo["incidencia"] : "");
                    $consultaHistorial->execute([
                        "idEquipo" => $equipo["idEquipo"], "cedula" => $cedulaDocente, "detalle" => mb_substr($detalle, 0, 255)
                    ]);
                }
            }

            if ($prioridadFinal !== $prioridad) {
                $sqlActualizarPrioridad = "UPDATE TICKET SET prioridad = :prioridad WHERE id = :idTicket";
                $this->conexion->prepare($sqlActualizarPrioridad)->execute(["prioridad" => $prioridadFinal, "idTicket" => $idTicket]);
            }

            $this->conexion->commit();
            return $idTicket;

        } catch (PDOException $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            return null;
        }
    }

    /**
     * Modifica los datos de un ticket y reemplaza el detalle de sus equipos, todo dentro de una transacción.
     * La prioridad se recalcula con las nuevas incidencias. No toca el estado, el docente ni el responsable asignado.
     *
     * @param int $idTicket Ticket a modificar.
     * @param array $equipos Mismo formato que registrarTicket(). Reemplaza a las filas anteriores de TICKET_EQUIPO.
     * Los demás parámetros son los mismos que en registrarTicket().
     *
     * @return bool TRUE si la modificación se realiza correctamente, FALSE en caso contrario (no se cambia nada).
     */
    public function modificarTicket(
        int $idTicket,
        int $idSala,
        string $fecha,
        string $horaEntrada,
        string $horaSalida,
        string $asignatura,
        string $grupo,
        string $turno,
        array $equipos
    ): bool {
        try {
            $this->conexion->beginTransaction();

            $prioridad = $this->calcularPrioridad($equipos);

            //Se borran antes los equipos viejos para que la regla de reincidencia no cuente las filas que se reemplazan
            $sqlBorrar = "DELETE FROM TICKET_EQUIPO WHERE ticket_id = :idTicket";
            $this->conexion->prepare($sqlBorrar)->execute(["idTicket" => $idTicket]);

            $prioridadFinal = $this->guardarEquipos($idTicket, $equipos, $prioridad);

            $sqlTicket = "
                UPDATE TICKET
                SET idSala = :idSala, fecha = :fecha, horaEntrada = :horaEntrada, horaSalida = :horaSalida,
                    asignatura = :asignatura, grupo = :grupo, turno = :turno, prioridad = :prioridad
                WHERE id = :idTicket
            ";
            $this->conexion->prepare($sqlTicket)->execute([
                "idSala" => $idSala, "fecha" => $fecha, "horaEntrada" => $horaEntrada, "horaSalida" => $horaSalida,
                "asignatura" => $asignatura, "grupo" => $grupo, "turno" => $turno,
                "prioridad" => $prioridadFinal, "idTicket" => $idTicket
            ]);

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
     * Elimina un ticket. Por la relación ON DELETE CASCADE, sus equipos (TICKET_EQUIPO) y comentarios (TICKET_COMENTARIO) se eliminan con él.
     * @param int $idTicket Identificador del ticket.
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function eliminarTicket(int $idTicket): bool {
        try {
            $sql = "DELETE FROM TICKET WHERE id = :idTicket";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["idTicket" => $idTicket]);

            return true;
        } catch (PDOException $error) {
            return false;
        }
    }

    /**
     * RF21: cambia el estado de un ticket.
     * @param int $idTicket Identificador del ticket.
     * @param string $estado Pendiente, Evaluando, En proceso o Terminado.
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function cambiarEstado(int $idTicket, string $estado): bool {
        try {
            $sql = "UPDATE TICKET SET estado = :estado WHERE id = :idTicket";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["estado" => $estado, "idTicket" => $idTicket]);

            return true;
        } catch (PDOException $error) {
            return false;
        }
    }

    /**
     * RF22: asigna un responsable al ticket.
     * La regla de quién puede asignar a quién (técnico solo a sí mismo, administrador a cualquier técnico)
     * se valida en el controlador, no acá.
     *
     * @param int $idTicket Identificador del ticket.
     * @param string $cedula Cédula del responsable.
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function asignarResponsable(int $idTicket, string $cedula): bool {
        try {
            //Al asignarse, el ticket pasa automáticamente a "Evaluando" si todavía estaba "Pendiente"
            $sql = "
                UPDATE TICKET
                SET cedulaAsignado = :cedula, estado = IF(estado = 'Pendiente', 'Evaluando', estado)
                WHERE id = :idTicket
            ";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["cedula" => $cedula, "idTicket" => $idTicket]);

            return true;
        } catch (PDOException $error) {
            return false;
        }
    }

    /**
     * RF23: agrega un comentario o diagnóstico a un ticket.
     * @param int $idTicket Identificador del ticket.
     * @param string|null $cedula Cédula de quien comenta.
     * @param string $comentario Texto del comentario.
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function agregarComentario(int $idTicket, ?string $cedula, string $comentario): bool {
        try {
            $sql = "INSERT INTO TICKET_COMENTARIO (ticket_id, cedula, comentario) VALUES (:idTicket, :cedula, :comentario)";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["idTicket" => $idTicket, "cedula" => $cedula, "comentario" => $comentario]);

            return true;
        } catch (PDOException $error) {
            return false;
        }
    }

    /**
     * Busca un comentario por su identificador.
     * @param int $idComentario Identificador del comentario.
     * @return array|null El comentario (id, ticket_id, cedula del autor, comentario), null si no existe.
     */
    public function listarComentario(int $idComentario): ?array {
        $sql = "SELECT id, ticket_id, cedula, comentario FROM TICKET_COMENTARIO WHERE id = :idComentario";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["idComentario" => $idComentario]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        $consulta = null;

        if ($fila === false) {
            return null;
        }

        return [
            "id" => (int) $fila["id"],
            "idTicket" => (int) $fila["ticket_id"],
            "cedula" => $fila["cedula"],
            "comentario" => $fila["comentario"],
        ];
    }

    /**
     * Modifica el texto de un comentario.
     * @param int $idComentario Identificador del comentario.
     * @param string $comentario Nuevo texto.
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function modificarComentario(int $idComentario, string $comentario): bool {
        try {
            $sql = "UPDATE TICKET_COMENTARIO SET comentario = :comentario WHERE id = :idComentario";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["comentario" => $comentario, "idComentario" => $idComentario]);

            return true;
        } catch (PDOException $error) {
            return false;
        }
    }

    /**
     * Elimina un comentario.
     * @param int $idComentario Identificador del comentario.
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function eliminarComentario(int $idComentario): bool {
        try {
            $sql = "DELETE FROM TICKET_COMENTARIO WHERE id = :idComentario";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute(["idComentario" => $idComentario]);

            return true;
        } catch (PDOException $error) {
            return false;
        }
    }

    /**
     * Convierte todas las filas de la consulta de tickets en arreglos listos para JSON.
     */
    private function mapearTickets(array $filas): array {
        $tickets = [];

        foreach ($filas as $fila) {
            $tickets[] = $this->mapearTicket($fila);
        }

        return $tickets;
    }

    /**
     * Convierte una fila de la consulta de tickets en un arreglo con los tipos correctos
     * (números como int) y con los nombres ya unidos.
     */
    private function mapearTicket(array $fila): array {
        $prioridad = (int) $fila["prioridad"];

        return [
            "id" => (int) $fila["id"],
            "idSala" => (int) $fila["idSala"],
            "fecha" => $fila["fecha"],
            "horaEntrada" => $fila["horaEntrada"],
            "horaSalida" => $fila["horaSalida"],
            "asignatura" => $fila["asignatura"],
            "grupo" => $fila["grupo"],
            "turno" => $fila["turno"],
            "prioridad" => $prioridad,
            "prioridadTexto" => match ($prioridad) {
                0 => "Urgente",
                1 => "Alta",
                default => "Normal",
            },
            "estado" => $fila["estado"],
            "tipoSala" => $fila["tipoSala"],
            "numeroSala" => (int) $fila["numeroSala"],
            "cedulaDocente" => $fila["cedulaDocente"],
            "docente" => $fila["docenteNombre"] . " " . $fila["docenteApellido"],
            "cedulaAsignado" => $fila["cedulaAsignado"],
            //null mientras ningún técnico lo haya tomado
            "asignado" => $fila["asignadoNombre"] === null ? null : $fila["asignadoNombre"] . " " . $fila["asignadoApellido"],
        ];
    }
}
