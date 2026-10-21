<?php

require_once RUTA_MODELO . "/ConectorPDO.php";
require_once RUTA_MODELO . "/InventarioDAO.php";
require_once RUTA_MODELO . "/FiltroPalabras.php";
require_once RUTA_VISTA . "/RespuestaJson.php";

require_once RUTA_NUCLEO . "/Conexion.php";
require_once RUTA_NUCLEO . "/Token.php";

/**
 * Controlador de la API del inventario tecnológico (RF1 a RF8). Responde siempre en JSON.
 * Gestiona salas, equipos y el historial de cada equipo con el parámetro ?recurso=salas, equipos o historial.
 *
 * - Consultar: todos los roles (RF4).
 * - Alta, modificación y baja de salas, equipos y eventos del historial: solo el administrador (RF3, RF6).
 *
 * GET = consultar, POST = registrar, PUT = modificar, DELETE = eliminar.
 */
class InventarioController {
    //Valores que el sistema acepta (los mismos que los ENUM de la base de datos)
    private const ESTADOS = ["Funcionando", "Incidencia", "Fuera de servicio"];
    private const TIPOS_SALA = ["Laboratorio", "Taller"];

    /**
     * Verifica los permisos y deriva la petición a la operación correspondiente.
     * @param string $metodo Método HTTP de la petición.
     */
    public function gestionar(string $metodo): void {
        $this->verificarAcceso();

        if (in_array($metodo, ["POST", "PUT", "PATCH", "DELETE"], true)) {
            //RF3, RF6: la gestión del inventario está reservada al administrador
            if (!($_SESSION["administrador"] ?? false)) {
                RespuestaJson::error("Acceso denegado: rol incorrecto", 403);
            }

            //El token CSRF solo se exige en las operaciones que modifican datos
            Token::verificarCSRF();
        }

        match ($metodo) {
            "GET" => $this->listar(),
            "POST" => $this->alta(),
            "PUT" => $this->modificar(),
            "DELETE" => $this->baja(),
            default => RespuestaJson::error("Método no permitido", 405),
        };
    }

    /**
     * Verifica que haya una sesión iniciada y que el usuario tenga al menos un rol (RF4: los 4 roles consultan el inventario).
     */
    private function verificarAcceso(): void {
        if (!isset($_SESSION["cedula"])) {
            RespuestaJson::error("Acceso denegado: sesión no iniciada", 401);
        }

        $tieneAlgunRol = ($_SESSION["administrador"] ?? false) || ($_SESSION["docente"] ?? false) || ($_SESSION["direccion"] ?? false) || ($_SESSION["tecnico"] ?? false);

        if (!$tieneAlgunRol) {
            RespuestaJson::error("Acceso denegado: rol incorrecto", 403);
        }
    }

    /**
     * Lee el cuerpo de la petición y lo convierte de JSON a arreglo. Si no es JSON válido, responde error 400.
     */
    private function leerJson(): array {
        $datos = json_decode(file_get_contents("php://input"), true);

        if (!is_array($datos)) {
            RespuestaJson::error("JSON inválido", 400);
        }

        return $datos;
    }

    /**
     * Retorna el campo como texto sin espacios en los extremos, o "" si no existe o no es texto.
     * Evita errores si el cliente envía un valor de otro tipo (número, arreglo, etc.).
     */
    private function texto(array $datos, string $campo): string {
        return is_string($datos[$campo] ?? null) ? trim($datos[$campo]) : "";
    }

    /**
     * Convierte un identificador o número (entero o texto de dígitos) en entero positivo.
     * @return int|null El entero, o null si no es válido (negativo, cero, texto, arreglo, demasiado grande...).
     */
    private function aEntero(mixed $valor): ?int {
        if (is_int($valor)) {
            $numero = $valor;
        } elseif (is_string($valor) && preg_match("/^[0-9]{1,10}$/", $valor)) {
            $numero = (int) $valor;
        } else {
            return null;
        }

        //2147483647 es el máximo de una columna INT de MySQL
        return ($numero >= 1 && $numero <= 2147483647) ? $numero : null;
    }

    /**
     * Retorna el valor del parámetro ?recurso= de la URL, o "" si no viene.
     */
    private function recurso(): string {
        return is_string($_GET["recurso"] ?? null) ? trim($_GET["recurso"]) : "";
    }

    /**
     * GET sin parámetros lista las salas con sus equipos; ?idSala= retorna una sala; ?idEquipo= retorna un equipo con su historial.
     */
    private function listar(): void {
        if (isset($_GET["idEquipo"])) {
            //Si el valor no es texto (por ejemplo ?idEquipo[]=1) se lo trata como vacío, y el método responde error
            $this->listarEquipo(is_string($_GET["idEquipo"]) ? trim($_GET["idEquipo"]) : "");
        } elseif (isset($_GET["idSala"])) {
            $this->listarSala(is_string($_GET["idSala"]) ? trim($_GET["idSala"]) : "");
        } else {
            $this->listarInventario();
        }
    }

    /**
     * Retorna todas las salas, cada una con la lista de sus equipos (GET sin parámetros).
     */
    private function listarInventario(): void {
        $conexion = Conexion::conectar();
        $dao = new InventarioDAO($conexion);
        $salas = $dao->listarInventario();
        //Se desconecta antes de responder porque RespuestaJson::exito() termina la ejecución con exit
        Conexion::desconectar();

        RespuestaJson::exito($salas);
    }

    /**
     * Retorna una sala con sus equipos (GET con ?idSala=).
     * @param string $id Identificador de la sala recibido en la URL, todavía sin validar.
     */
    private function listarSala(string $id): void {
        if ($id === "") {
            RespuestaJson::error("El id de la sala es obligatorio", 400);
        }

        $idSala = $this->aEntero($id);

        if ($idSala === null) {
            RespuestaJson::error("Id de sala incorrecto", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new InventarioDAO($conexion);

        $sala = $dao->listarSala($idSala);

        if ($sala === null) {
            Conexion::desconectar();
            RespuestaJson::error("La sala no existe", 404);
        }

        $sala["equipos"] = $dao->listarEquiposPorSala($idSala);
        Conexion::desconectar();

        RespuestaJson::exito($sala);
    }

    /**
     * RF5: retorna un equipo, la sala a la que pertenece y su historial.
     */
    private function listarEquipo(string $id): void {
        if ($id === "") {
            RespuestaJson::error("El id del equipo es obligatorio", 400);
        }

        $idEquipo = $this->aEntero($id);

        if ($idEquipo === null) {
            RespuestaJson::error("Id de equipo incorrecto", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new InventarioDAO($conexion);

        $equipo = $dao->listarEquipo($idEquipo);

        if ($equipo === null) {
            Conexion::desconectar();
            RespuestaJson::error("El equipo no existe", 404);
        }

        $datos = [
            "equipo" => $equipo,
            "sala" => $dao->listarSala($equipo["idSala"]),
            "historial" => $dao->listarHistorialPorEquipo($idEquipo),
        ];

        Conexion::desconectar();

        RespuestaJson::exito($datos);
    }

    /**
     * POST con ?recurso=salas registra una sala; con ?recurso=equipos registra un equipo.
     */
    private function alta(): void {
        match ($this->recurso()) {
            "salas" => $this->altaSala(),
            "equipos" => $this->altaEquipo(),
            default => RespuestaJson::error("Recurso inválido, use ?recurso=salas o ?recurso=equipos", 400),
        };
    }

    /**
     * RF6: registra una sala. Espera {"tipo": "Laboratorio", "numero": 3}.
     */
    private function altaSala(): void {
        $datos = $this->leerJson();

        $tipo = $this->texto($datos, "tipo");
        $numero = $this->aEntero($datos["numero"] ?? null);

        if ($tipo === "" || ($datos["numero"] ?? null) === null) {
            RespuestaJson::error("Existen campos vacíos", 422);
        }

        if (!in_array($tipo, self::TIPOS_SALA, true)) {
            RespuestaJson::error("Tipo de sala inválido", 422);
        }

        if ($numero === null) {
            RespuestaJson::error("Número de sala incorrecto", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new InventarioDAO($conexion);

        if ($dao->existeSala($tipo, $numero)) {
            Conexion::desconectar();
            RespuestaJson::error("Ya existe una sala igual", 409);
        }

        $idSala = $dao->registrarSala($tipo, $numero);
        Conexion::desconectar();

        if ($idSala === null) {
            RespuestaJson::error("No se pudo registrar la sala", 400);
        }

        RespuestaJson::exito(["mensaje" => "Sala registrada exitosamente", "id" => $idSala], 201);
    }

    /**
     * RF8: registra un equipo dentro de una sala. Espera {"numero": 5, "idSala": 1, "estado": "Funcionando"}.
     * Si no se envía el estado, se registra como "Funcionando".
     */
    private function altaEquipo(): void {
        $datos = $this->leerJson();

        $estado = $this->texto($datos, "estado");
        $numero = $this->aEntero($datos["numero"] ?? null);
        $idSala = $this->aEntero($datos["idSala"] ?? null);

        if ($estado === "") {
            $estado = "Funcionando";
        }

        if ($numero === null || $idSala === null) {
            RespuestaJson::error("Existen campos vacíos o inválidos", 422);
        }

        if (!in_array($estado, self::ESTADOS, true)) {
            RespuestaJson::error("Estado inválido", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new InventarioDAO($conexion);

        if ($dao->listarSala($idSala) === null) {
            Conexion::desconectar();
            RespuestaJson::error("La sala no existe", 404);
        }

        if ($dao->existeEquipo($idSala, $numero)) {
            Conexion::desconectar();
            RespuestaJson::error("Ya existe un equipo con ese número en esa sala", 409);
        }

        $idEquipo = $dao->registrarEquipo($numero, $idSala, $estado, $_SESSION["cedula"]);
        Conexion::desconectar();

        if ($idEquipo === null) {
            RespuestaJson::error("No se pudo registrar el equipo", 400);
        }

        RespuestaJson::exito(["mensaje" => "Equipo registrado exitosamente", "id" => $idEquipo], 201);
    }

    /**
     * PUT con ?recurso=salas modifica una sala; con ?recurso=equipos modifica un equipo;
     * con ?recurso=historial corrige un evento del historial de equipos.
     */
    private function modificar(): void {
        match ($this->recurso()) {
            "salas" => $this->modificarSala(),
            "equipos" => $this->modificarEquipo(),
            "historial" => $this->modificarHistorial(),
            default => RespuestaJson::error("Recurso inválido, use ?recurso=salas, ?recurso=equipos o ?recurso=historial", 400),
        };
    }

    /**
     * RF6: modifica el tipo y el número de una sala. Espera {"id": 1, "tipo": "Laboratorio", "numero": 3}.
     */
    private function modificarSala(): void {
        $datos = $this->leerJson();

        $idSala = $this->aEntero($datos["id"] ?? null);
        $tipo = $this->texto($datos, "tipo");
        $numero = $this->aEntero($datos["numero"] ?? null);

        if ($idSala === null || $tipo === "" || ($datos["numero"] ?? null) === null) {
            RespuestaJson::error("Existen campos vacíos", 422);
        }

        if (!in_array($tipo, self::TIPOS_SALA, true)) {
            RespuestaJson::error("Tipo de sala inválido", 422);
        }

        if ($numero === null) {
            RespuestaJson::error("Número de sala incorrecto", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new InventarioDAO($conexion);

        if ($dao->listarSala($idSala) === null) {
            Conexion::desconectar();
            RespuestaJson::error("La sala no existe", 404);
        }

        if ($dao->existeOtraSala($tipo, $numero, $idSala)) {
            Conexion::desconectar();
            RespuestaJson::error("Ya existe una sala igual", 409);
        }

        $resultado = $dao->modificarSala($idSala, $tipo, $numero);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo modificar la sala", 400);
        }

        RespuestaJson::exito(["mensaje" => "Sala modificada exitosamente"]);
    }

    /**
     * RF2: modifica el estado de un equipo.
     * Espera {"id": 1, "estado": "Incidencia", "incidencia": "Descripción"} con ?recurso=equipos.
     */
    private function modificarEquipo(): void {
        $datos = $this->leerJson();

        $idEquipo = $this->aEntero($datos["id"] ?? null);
        $estado = $this->texto($datos, "estado");
        $incidencia = $this->texto($datos, "incidencia");

        if ($idEquipo === null || $estado === "") {
            RespuestaJson::error("Existen campos vacíos o inválidos", 422);
        }

        if (!in_array($estado, self::ESTADOS, true)) {
            RespuestaJson::error("Estado inválido", 422);
        }

        //Si el estado no es "Incidencia", no tiene sentido guardar una descripción de incidencia
        if ($estado !== "Incidencia") {
            $incidencia = null;
        } elseif ($incidencia === "") {
            RespuestaJson::error("Debe describir la incidencia para marcar el equipo con ese estado", 422);
        } elseif (FiltroPalabras::contienePalabraOfensiva($incidencia)) {
            //RF25: no se admiten palabras ofensivas en los textos ingresados
            RespuestaJson::error("La descripción de la incidencia contiene palabras no permitidas", 422);
        } elseif (mb_strlen($incidencia) > 255) {
            RespuestaJson::error("La incidencia admite hasta 255 caracteres", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new InventarioDAO($conexion);

        if ($dao->listarEquipo($idEquipo) === null) {
            Conexion::desconectar();
            RespuestaJson::error("El equipo no existe", 404);
        }

        $resultado = $dao->modificarEquipo($idEquipo, $estado, $incidencia, $_SESSION["cedula"]);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo modificar el equipo", 400);
        }

        RespuestaJson::exito(["mensaje" => "Equipo modificado exitosamente"]);
    }

    /**
     * RF3, RF6: elimina una sala (junto con sus equipos), un equipo o un evento del historial.
     * Espera {"id": 1} con ?recurso=salas, ?recurso=equipos o ?recurso=historial.
     */
    private function baja(): void {
        $recurso = $this->recurso();

        if ($recurso === "historial") {
            $this->eliminarHistorial();
            return;
        }

        if ($recurso !== "salas" && $recurso !== "equipos") {
            RespuestaJson::error("Recurso inválido, use ?recurso=salas, ?recurso=equipos o ?recurso=historial", 400);
        }

        $datos = $this->leerJson();

        $id = $this->aEntero($datos["id"] ?? null);

        if ($id === null) {
            RespuestaJson::error("Falta el id o es incorrecto", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new InventarioDAO($conexion);

        if ($recurso === "salas") {
            if ($dao->listarSala($id) === null) {
                Conexion::desconectar();
                RespuestaJson::error("La sala no existe", 404);
            }

            $resultado = $dao->eliminarSala($id);
            $mensajeExito = "Sala eliminada exitosamente (junto con sus equipos)";
            $mensajeError = "No se pudo eliminar la sala, es posible que alguno de sus equipos tenga tickets o préstamos asociados";
        } else {
            if ($dao->listarEquipo($id) === null) {
                Conexion::desconectar();
                RespuestaJson::error("El equipo no existe", 404);
            }

            $resultado = $dao->eliminarEquipo($id);
            $mensajeExito = "Equipo eliminado exitosamente";
            $mensajeError = "No se pudo eliminar el equipo, es posible que tenga tickets o préstamos asociados";
        }

        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error($mensajeError, 400);
        }

        RespuestaJson::exito(["mensaje" => $mensajeExito]);
    }

    /**
     * Corrige un evento del historial de un equipo. Espera {"id": 1, "accion": "Modificación", "detalle": "Texto"}.
     * Solo el administrador. Es una bitácora: la fecha y el usuario que originó el evento no se pueden cambiar.
     */
    private function modificarHistorial(): void {
        $datos = $this->leerJson();

        $idEvento = $this->aEntero($datos["id"] ?? null);
        $accion = $this->texto($datos, "accion");
        $detalle = $this->texto($datos, "detalle");

        if ($idEvento === null || $accion === "") {
            RespuestaJson::error("Existen campos vacíos o inválidos", 422);
        }

        if (mb_strlen($accion) > 30) {
            RespuestaJson::error("La acción admite hasta 30 caracteres", 422);
        }

        if (mb_strlen($detalle) > 255) {
            RespuestaJson::error("El detalle admite hasta 255 caracteres", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new InventarioDAO($conexion);

        if ($dao->listarEventoHistorial($idEvento) === null) {
            Conexion::desconectar();
            RespuestaJson::error("El evento del historial no existe", 404);
        }

        //Un detalle vacío se guarda como NULL, igual que cuando el sistema registra el evento sin detalle
        $resultado = $dao->modificarEventoHistorial($idEvento, $accion, $detalle !== "" ? $detalle : null);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo modificar el evento del historial", 400);
        }

        RespuestaJson::exito(["mensaje" => "Evento del historial modificado exitosamente"]);
    }

    /**
     * Elimina un evento del historial de un equipo. Espera {"id": 1}. Solo el administrador.
     */
    private function eliminarHistorial(): void {
        $datos = $this->leerJson();

        $idEvento = $this->aEntero($datos["id"] ?? null);

        if ($idEvento === null) {
            RespuestaJson::error("Falta el id del evento o es incorrecto", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new InventarioDAO($conexion);

        if ($dao->listarEventoHistorial($idEvento) === null) {
            Conexion::desconectar();
            RespuestaJson::error("El evento del historial no existe", 404);
        }

        $resultado = $dao->eliminarEventoHistorial($idEvento);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo eliminar el evento del historial", 400);
        }

        RespuestaJson::exito(["mensaje" => "Evento del historial eliminado exitosamente"]);
    }
}
