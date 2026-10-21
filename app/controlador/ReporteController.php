<?php

require_once RUTA_MODELO . "/ConectorPDO.php";
require_once RUTA_MODELO . "/ReporteDAO.php";
require_once RUTA_VISTA . "/RespuestaJson.php";

require_once RUTA_NUCLEO . "/Conexion.php";

/**
 * Controlador de la API que gestiona los reportes y estadísticas (RF39-RF43).
 * Responde siempre en JSON. Gestiona los reportes y el resumen de personal.
 *
 * Es de solo lectura: únicamente se acepta GET, por eso no se exige token CSRF.
 * - GET ?ruta=reportes&desde=AAAA-MM-DD&hasta=AAAA-MM-DD = resumen general y todos los reportes del período.
 * - GET ?ruta=reportes&recurso=personal = listado de personal con sus roles (resumen institucional de dirección).
 *
 * Quién puede hacer qué:
 * - Reportes: administrador, técnico y dirección. El docente no accede (RF39-43).
 * - Personal por rol: solo dirección.
 */
class ReporteController {
    private const ESTADOS = ["Pendiente", "Evaluando", "En proceso", "Terminado"];
    private const NOMBRES_PRIORIDAD = [0 => "Urgente", 1 => "Alta", 2 => "Normal"];

    /**
     * Verifica los permisos y deriva la petición a la operación correspondiente.
     * @param string $metodo Método HTTP de la petición.
     */
    public function gestionar(string $metodo): void {
        $this->verificarAcceso();

        match ($metodo) {
            "GET" => $this->consultar(),
            default => RespuestaJson::error("Método no permitido", 405),
        };
    }

    /**
     * Verifica que haya una sesión iniciada y que el usuario sea administrador, técnico o dirección.
     */
    private function verificarAcceso(): void {
        if (!isset($_SESSION["cedula"])) {
            RespuestaJson::error("Acceso denegado: sesión no iniciada", 401);
        }

        if (!$this->tieneRol("administrador", "tecnico", "direccion")) {
            RespuestaJson::error("Acceso denegado: rol incorrecto", 403);
        }
    }

    /**
     * Retorna TRUE si el usuario de la sesión tiene alguno de los roles indicados.
     * @param string ...$roles Claves de rol en $_SESSION: "administrador", "docente", "direccion" o "tecnico".
     */
    private function tieneRol(string ...$roles): bool {
        foreach ($roles as $rol) {
            if ($_SESSION[$rol] ?? false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retorna el valor del parámetro ?recurso= de la URL, o "" si no viene.
     */
    private function recurso(): string {
        return is_string($_GET["recurso"] ?? null) ? trim($_GET["recurso"]) : "";
    }

    /**
     * Valida el rango de fechas y retorna los reportes y estadísticas (GET). Con ?recurso=personal retorna el resumen de personal (solo dirección).
     */
    private function consultar(): void {
        match ($this->recurso()) {
            "" => $this->generarReportes(),
            "personal" => $this->listarPersonal(),
            default => RespuestaJson::error("Recurso no encontrado", 404),
        };
    }

    /**
     * RF43: lee y valida el filtro de fechas. Si no viene alguna, por defecto son los últimos 30 días.
     * Si "desde" es posterior a "hasta" se intercambian.
     * Si una fecha viene con formato incorrecto o no existe, responde error 422.
     * @return array Arreglo [desde, hasta] con formato AAAA-MM-DD.
     */
    private function leerRangoFechas(): array {
        $hasta = $this->leerFecha("hasta", date("Y-m-d"));
        $desde = $this->leerFecha("desde", date("Y-m-d", strtotime("-30 days")));

        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return [$desde, $hasta];
    }

    /**
     * Retorna la fecha del parámetro de la URL, el valor por defecto si no viene o viene vacío, o error 422 si es inválida.
     */
    private function leerFecha(string $campo, string $porDefecto): string {
        $valor = $_GET[$campo] ?? "";

        if (!is_string($valor)) {
            RespuestaJson::error("La fecha '$campo' es incorrecta", 422);
        }

        $valor = trim($valor);

        if ($valor === "") {
            return $porDefecto;
        }

        if (!preg_match("/^(\d{4})-(\d{2})-(\d{2})$/", $valor, $partes) || !checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1])) {
            RespuestaJson::error("La fecha '$campo' es incorrecta, el formato es AAAA-MM-DD", 422);
        }

        return $valor;
    }

    /**
     * RF39-RF42: resumen general más todos los reportes del período filtrado.
     */
    private function generarReportes(): void {
        [$desde, $hasta] = $this->leerRangoFechas();

        $conexion = Conexion::conectar();
        $dao = new ReporteDAO($conexion);

        $resumen = $dao->obtenerResumenGeneral();
        $porEstado = $dao->contarTicketsPorEstado($desde, $hasta);
        $porPrioridad = $dao->contarTicketsPorPrioridad($desde, $hasta);
        $equiposIncidencias = $dao->listarEquiposConMasIncidencias($desde, $hasta);
        $salasTickets = $dao->listarSalasConMasTickets($desde, $hasta);
        $prestamos = $dao->obtenerMetricasPrestamos($desde, $hasta);
        $equiposPrestados = $dao->listarEquiposMasPrestados($desde, $hasta);

        //Se desconecta antes de responder porque RespuestaJson::exito() termina la ejecución con exit
        Conexion::desconectar();

        //Todos los estados y prioridades aparecen siempre, con 0 si no tienen tickets
        $ticketsPorEstado = [];
        foreach (self::ESTADOS as $estado) {
            $ticketsPorEstado[] = ["estado" => $estado, "total" => (int) ($porEstado[$estado] ?? 0)];
        }

        $ticketsPorPrioridad = [];
        foreach (self::NOMBRES_PRIORIDAD as $prioridad => $nombre) {
            $ticketsPorPrioridad[] = ["prioridad" => $prioridad, "nombre" => $nombre, "total" => (int) ($porPrioridad[$prioridad] ?? 0)];
        }

        RespuestaJson::exito([
            "desde" => $desde,
            "hasta" => $hasta,
            "resumenGeneral" => array_map("intval", $resumen),
            "ticketsPorEstado" => $ticketsPorEstado,
            "ticketsPorPrioridad" => $ticketsPorPrioridad,
            "equiposConMasIncidencias" => $this->convertirEnteros($equiposIncidencias, ["numeroEquipo", "numeroSala", "totalIncidencias"]),
            "salasConMasTickets" => $this->convertirEnteros($salasTickets, ["numeroSala", "totalTickets"]),
            "metricasPrestamos" => [
                "totalPrestamos" => (int) $prestamos["totalPrestamos"],
                "activos" => (int) $prestamos["activos"],
                "finalizados" => (int) $prestamos["finalizados"],
                //Sin préstamos finalizados el promedio es NULL, el frontend muestra "-"
                "promedioHoras" => $prestamos["promedioHoras"] !== null ? round((float) $prestamos["promedioHoras"], 1) : null
            ],
            "equiposMasPrestados" => $this->convertirEnteros($equiposPrestados, ["numeroEquipo", "numeroSala", "totalPrestamos"])
        ]);
    }

    /**
     * Resumen institucional de dirección: cada usuario con la lista de sus roles. Solo dirección.
     */
    private function listarPersonal(): void {
        if (!$this->tieneRol("direccion")) {
            RespuestaJson::error("Acceso denegado: rol incorrecto", 403);
        }

        $conexion = Conexion::conectar();
        $dao = new ReporteDAO($conexion);

        $usuarios = $dao->listarPersonalConRoles();

        Conexion::desconectar();

        $personal = [];
        foreach ($usuarios as $usuario) {
            $roles = [];

            if ($usuario["administrador"]) {
                $roles[] = "Administrador";
            }
            if ($usuario["docente"]) {
                $roles[] = "Docente";
            }
            if ($usuario["direccion"]) {
                $roles[] = "Dirección";
            }
            if ($usuario["tecnico"]) {
                $roles[] = "Técnico";
            }

            $personal[] = [
                "cedula" => $usuario["cedula"],
                "nombre" => $usuario["nombre"],
                "apellido" => $usuario["apellido"],
                "roles" => $roles === [] ? "Sin rol" : implode(", ", $roles)
            ];
        }

        RespuestaJson::exito($personal);
    }

    /**
     * Convierte a entero los campos numéricos indicados de cada fila (PDO puede entregarlos como texto).
     */
    private function convertirEnteros(array $filas, array $campos): array {
        foreach ($filas as &$fila) {
            foreach ($campos as $campo) {
                $fila[$campo] = (int) $fila[$campo];
            }
        }
        unset($fila);

        return $filas;
    }
}
