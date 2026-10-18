<?php

/**
 * Clase que unifica las consultas de agregación del módulo de reportes (RF39-RF43)
 * y el resumen de personal por rol que muestra el panel de dirección.
 *
 * DAO ~ Data Access Object. Reúne las consultas de reportes y del resumen de personal.
 * Todas las consultas que dependen del filtro de fechas (RF43) reciben $desde y $hasta como texto AAAA-MM-DD.
 * Los métodos retornan arreglos asociativos (listos para convertir a JSON).
 */
class ReporteDAO {
    private PDO $conexion;

    public function __construct(PDO $conexion) {
        $this->conexion = $conexion;
    }

    /**
     * RF42: resumen general para apoyo en la toma de decisiones.
     */
    public function obtenerResumenGeneral(): array {
        $sql = "
            SELECT
                (SELECT COUNT(*) FROM USUARIO WHERE activo = TRUE) AS usuariosActivos,
                (SELECT COUNT(*) FROM EQUIPO) AS totalEquipos,
                (SELECT COUNT(*) FROM TICKET WHERE estado != 'Terminado') AS ticketsPendientes,
                (SELECT COUNT(*) FROM SOLICITUD WHERE estado != 'Resuelta') AS solicitudesPendientes,
                (SELECT COUNT(*) FROM PRESTAMO WHERE estado = 'Activo') AS prestamosActivos
        ";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute();
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        $consulta = null;

        return $fila;
    }

    /**
     * RF39: cantidad de tickets por estado, dentro del rango de fechas.
     * @return array Pares estado => total. Solo aparecen los estados que tienen tickets.
     */
    public function contarTicketsPorEstado(string $desde, string $hasta): array {
        $sql = "SELECT estado, COUNT(*) AS total FROM TICKET WHERE fecha BETWEEN :desde AND :hasta GROUP BY estado";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["desde" => $desde, "hasta" => $hasta]);
        $filas = $consulta->fetchAll(PDO::FETCH_KEY_PAIR);
        $consulta = null;

        return $filas;
    }

    /**
     * RF39: cantidad de tickets por prioridad, dentro del rango de fechas.
     * @return array Pares prioridad => total. Solo aparecen las prioridades que tienen tickets.
     */
    public function contarTicketsPorPrioridad(string $desde, string $hasta): array {
        $sql = "SELECT prioridad, COUNT(*) AS total FROM TICKET WHERE fecha BETWEEN :desde AND :hasta GROUP BY prioridad";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["desde" => $desde, "hasta" => $hasta]);
        $filas = $consulta->fetchAll(PDO::FETCH_KEY_PAIR);
        $consulta = null;

        return $filas;
    }

    /**
     * RF40: equipos con más incidencias reportadas, dentro del rango de fechas (los 10 primeros).
     */
    public function listarEquiposConMasIncidencias(string $desde, string $hasta): array {
        $sql = "
            SELECT e.numero AS numeroEquipo, s.tipo AS tipoSala, s.numero AS numeroSala, COUNT(*) AS totalIncidencias
            FROM TICKET_EQUIPO AS te
            JOIN EQUIPO AS e ON e.id = te.equipo_id
            JOIN SALA AS s ON s.id = e.idSala
            JOIN TICKET AS t ON t.id = te.ticket_id
            WHERE te.incidencia IS NOT NULL AND t.fecha BETWEEN :desde AND :hasta
            GROUP BY e.id
            ORDER BY totalIncidencias DESC
            LIMIT 10
        ";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["desde" => $desde, "hasta" => $hasta]);
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $consulta = null;

        return $filas;
    }

    /**
     * RF40: salas con más tickets (uso de las salas), dentro del rango de fechas.
     */
    public function listarSalasConMasTickets(string $desde, string $hasta): array {
        $sql = "
            SELECT s.tipo AS tipoSala, s.numero AS numeroSala, COUNT(*) AS totalTickets
            FROM TICKET AS t
            JOIN SALA AS s ON s.id = t.idSala
            WHERE t.fecha BETWEEN :desde AND :hasta
            GROUP BY s.id
            ORDER BY totalTickets DESC
        ";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["desde" => $desde, "hasta" => $hasta]);
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $consulta = null;

        return $filas;
    }

    /**
     * RF41: métricas de préstamos y devoluciones, dentro del rango de fechas.
     * Si no hay préstamos en el rango, las sumas y el promedio llegan como NULL.
     */
    public function obtenerMetricasPrestamos(string $desde, string $hasta): array {
        $sql = "
            SELECT
                COUNT(*) AS totalPrestamos,
                SUM(estado = 'Activo') AS activos,
                SUM(estado = 'Finalizado') AS finalizados,
                AVG(CASE WHEN fechaHoraFin IS NOT NULL THEN TIMESTAMPDIFF(HOUR, fechaHoraInicio, fechaHoraFin) END) AS promedioHoras
            FROM PRESTAMO
            WHERE DATE(fechaHoraInicio) BETWEEN :desde AND :hasta
        ";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["desde" => $desde, "hasta" => $hasta]);
        $fila = $consulta->fetch(PDO::FETCH_ASSOC);
        $consulta = null;

        return $fila;
    }

    /**
     * RF41: equipos más prestados, dentro del rango de fechas (los 10 primeros).
     */
    public function listarEquiposMasPrestados(string $desde, string $hasta): array {
        $sql = "
            SELECT e.numero AS numeroEquipo, s.tipo AS tipoSala, s.numero AS numeroSala, COUNT(*) AS totalPrestamos
            FROM PRESTAMO_EQUIPO AS pe
            JOIN EQUIPO AS e ON e.id = pe.equipo_id
            JOIN SALA AS s ON s.id = e.idSala
            JOIN PRESTAMO AS p ON p.id = pe.prestamo_id
            WHERE DATE(p.fechaHoraInicio) BETWEEN :desde AND :hasta
            GROUP BY e.id
            ORDER BY totalPrestamos DESC
            LIMIT 10
        ";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["desde" => $desde, "hasta" => $hasta]);
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $consulta = null;

        return $filas;
    }

    /**
     * Resumen institucional del panel de dirección: todos los usuarios con una marca por cada rol que poseen.
     * Un usuario puede tener varios roles a la vez, por eso se devuelve una marca por rol y no un solo texto.
     */
    public function listarPersonalConRoles(): array {
        $sql = "
            SELECT
                u.cedula,
                u.nombre,
                u.apellido,
                a.cedula IS NOT NULL AS administrador,
                d.cedula IS NOT NULL AS docente,
                dir.cedula IS NOT NULL AS direccion,
                t.cedula IS NOT NULL AS tecnico
            FROM USUARIO AS u
            LEFT JOIN ADMINISTRADOR AS a ON a.cedula = u.cedula
            LEFT JOIN DOCENTE AS d ON d.cedula = u.cedula
            LEFT JOIN DIRECCION AS dir ON dir.cedula = u.cedula
            LEFT JOIN TECNICO AS t ON t.cedula = u.cedula
        ";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute();
        $filas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        $consulta = null;

        return $filas;
    }
}
