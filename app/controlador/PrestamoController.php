<?php

require_once RUTA_MODELO . "/ConectorPDO.php";
require_once RUTA_MODELO . "/PrestamoDAO.php";
require_once RUTA_MODELO . "/SolicitudDAO.php";
require_once RUTA_MODELO . "/FiltroPalabras.php";
require_once RUTA_VISTA . "/RespuestaJson.php";

require_once RUTA_NUCLEO . "/Conexion.php";
require_once RUTA_NUCLEO . "/Token.php";

/**
 * Controlador de la API que gestiona los préstamos de equipos y sus devoluciones.
 * Responde siempre en JSON. Gestiona todas las operaciones del recurso.
 *
 * Cada método HTTP representa una operación distinta:
 * GET = consultar (historial, detalle con ?id= o equipos libres con ?recurso=equiposDisponibles),
 * POST = registrar la entrega de equipos de una solicitud de tipo Préstamo,
 * PATCH = registrar la devolución,
 * PUT = editar los datos de quien retiró los equipos,
 * DELETE = eliminar el préstamo (sus equipos se eliminan en cascada).
 *
 * Quién puede hacer qué:
 * - Consultar el historial y el detalle (RF16): administrador, técnico y dirección.
 * - Registrar entregas y devoluciones (RF9 a RF15) y editar un préstamo: solo administrador y técnico.
 * - Eliminar un préstamo: solo el administrador (también si sigue activo).
 */
class PrestamoController {
    private const TIPOS_DOCUMENTO = ["Cédula", "Pasaporte", "Otro"];

    /**
     * Verifica los permisos y deriva la petición a la operación correspondiente.
     * @param string $metodo Método HTTP de la petición.
     */
    public function gestionar(string $metodo): void {
        $this->verificarAcceso();

        //El token CSRF solo se exige en las operaciones que modifican datos
        if (in_array($metodo, ["POST", "PUT", "PATCH", "DELETE"], true)) {
            Token::verificarCSRF();
        }

        match ($metodo) {
            "GET" => $this->listar(),
            "POST" => $this->registrarPrestamo(),
            "PATCH" => $this->registrarDevolucion(),
            "PUT" => $this->editarPrestamo(),
            "DELETE" => $this->eliminarPrestamo(),
            default => RespuestaJson::error("Método no permitido", 405),
        };
    }

    /**
     * Verifica que haya una sesión iniciada y que el usuario sea administrador, técnico o dirección (RF16).
     * Las operaciones que modifican datos exigen además ser administrador o técnico.
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
     * Responde error 403 si el usuario no es administrador ni técnico (dirección solo consulta).
     */
    private function exigirGestor(): void {
        if (!$this->tieneRol("administrador", "tecnico")) {
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
     * Convierte un identificador (número o texto de dígitos) en entero positivo.
     * @return int|null El entero, o null si no es un identificador válido (negativo, cero, texto, arreglo, demasiado grande...).
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
     * Convierte una lista de ids recibida en el JSON en un arreglo de enteros sin repetidos.
     * Si algún valor es incorrecto, responde el error y detiene la ejecución.
     * @param mixed $valores Valor de la clave del JSON recibido.
     * @param string $mensaje Mensaje de error si algún identificador es incorrecto.
     */
    private function leerIds(mixed $valores, string $mensaje): array {
        if (!is_array($valores)) {
            RespuestaJson::error($mensaje, 422);
        }

        $ids = [];

        foreach ($valores as $valor) {
            $id = $this->aEntero($valor);

            if ($id === null) {
                RespuestaJson::error($mensaje, 422);
            }

            if (!in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * Lee y valida los datos de quien retira los equipos (los mismos para la entrega y para la edición).
     * Si algún dato es incorrecto, responde el error y detiene la ejecución.
     * @return array Con las claves nombre, tipoDocumento y documento.
     */
    private function leerDatosSolicitante(array $datos): array {
        $nombre = $this->texto($datos, "solicitanteNombre");
        $tipoDocumento = $this->texto($datos, "solicitanteTipoDocumento");
        $documento = $this->texto($datos, "solicitanteDocumento");

        if ($nombre === "" || $documento === "") {
            RespuestaJson::error("Existen campos vacíos o inválidos", 422);
        }

        if (!in_array($tipoDocumento, self::TIPOS_DOCUMENTO, true)) {
            RespuestaJson::error("Tipo de documento inválido", 422);
        }

        if (mb_strlen($nombre) > 100 || mb_strlen($documento) > 20) {
            RespuestaJson::error("El nombre admite hasta 100 caracteres y el documento hasta 20", 422);
        }

        //RF25: filtro de palabras ofensivas
        if (FiltroPalabras::contienePalabraOfensiva($nombre)) {
            RespuestaJson::error("El formulario contiene lenguaje inapropiado", 422);
        }

        return ["nombre" => $nombre, "tipoDocumento" => $tipoDocumento, "documento" => $documento];
    }

    /**
     * GET sin parámetros lista el historial; con ?id= retorna el detalle; con ?recurso=equiposDisponibles retorna los equipos libres.
     */
    private function listar(): void {
        if ($this->recurso() === "equiposDisponibles") {
            $this->listarEquiposDisponibles();
        } elseif (isset($_GET["id"])) {
            //Si el valor no es texto (por ejemplo ?id[]=1) se lo trata como vacío, y detalle() responde error
            $this->detalle(is_string($_GET["id"]) ? trim($_GET["id"]) : "");
        } else {
            $this->listarPrestamos();
        }
    }

    private function listarPrestamos(): void {
        $conexion = Conexion::conectar();
        $dao = new PrestamoDAO($conexion);
        $prestamos = $dao->listarPrestamos();

        //Se desconecta antes de responder porque RespuestaJson::exito() termina la ejecución con exit
        Conexion::desconectar();

        RespuestaJson::exito($prestamos);
    }

    /**
     * Retorna un préstamo con los equipos entregados.
     */
    private function detalle(string $id): void {
        if ($id === "") {
            RespuestaJson::error("El id del préstamo es obligatorio", 400);
        }

        $idPrestamo = $this->aEntero($id);

        if ($idPrestamo === null) {
            RespuestaJson::error("Id de préstamo incorrecto", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new PrestamoDAO($conexion);

        $prestamo = $dao->listarPrestamo($idPrestamo);

        if ($prestamo === null) {
            Conexion::desconectar();
            RespuestaJson::error("El préstamo no existe", 404);
        }

        $datos = [
            "prestamo" => $prestamo,
            "equipos" => $dao->listarEquiposPorPrestamo($idPrestamo),
        ];

        Conexion::desconectar();

        RespuestaJson::exito($datos);
    }

    /**
     * Retorna la solicitud de tipo Préstamo y las salas con los equipos que no están en un préstamo activo.
     * Espera ?recurso=equiposDisponibles&idSolicitud=N. Solo administrador y técnico.
     */
    private function listarEquiposDisponibles(): void {
        $this->exigirGestor();

        $idSolicitud = $this->aEntero(is_string($_GET["idSolicitud"] ?? null) ? trim($_GET["idSolicitud"]) : null);

        if ($idSolicitud === null) {
            RespuestaJson::error("Falta el id de la solicitud o es incorrecto", 422);
        }

        $conexion = Conexion::conectar();
        $solicitudDao = new SolicitudDAO($conexion);
        $dao = new PrestamoDAO($conexion);

        $solicitud = $solicitudDao->listarSolicitud($idSolicitud);

        if ($solicitud === null) {
            Conexion::desconectar();
            RespuestaJson::error("La solicitud no existe", 404);
        }

        if ($solicitud["tipo"] !== "Préstamo") {
            Conexion::desconectar();
            RespuestaJson::error("La solicitud indicada no es de tipo Préstamo", 422);
        }

        if ($dao->listarPrestamoActivoPorSolicitud($idSolicitud) !== null) {
            Conexion::desconectar();
            RespuestaJson::error("Esta solicitud ya tiene un préstamo activo. Registre la devolución antes de crear uno nuevo", 409);
        }

        $datos = [
            "solicitud" => $solicitud,
            "salas" => $dao->listarEquiposDisponibles(),
        ];

        Conexion::desconectar();

        RespuestaJson::exito($datos);
    }

    /**
     * RF9, RF11, RF12, RF13, RF15: registra la entrega de equipos de una solicitud de tipo Préstamo. Espera:
     * {"idSolicitud": 1, "solicitanteNombre": "...", "solicitanteTipoDocumento": "Cédula", "solicitanteDocumento": "...", "idsEquipo": [1, 2]}
     */
    private function registrarPrestamo(): void {
        $this->exigirGestor();

        $datos = $this->leerJson();

        $idSolicitud = $this->aEntero($datos["idSolicitud"] ?? null);

        if ($idSolicitud === null) {
            RespuestaJson::error("Existen campos vacíos o inválidos", 422);
        }

        $solicitante = $this->leerDatosSolicitante($datos);
        $solicitanteNombre = $solicitante["nombre"];
        $tipoDocumento = $solicitante["tipoDocumento"];
        $documento = $solicitante["documento"];

        $idsEquipo = $this->leerIds($datos["idsEquipo"] ?? null, "Identificador de equipo incorrecto");

        if (count($idsEquipo) === 0) {
            RespuestaJson::error("Debe seleccionar al menos un equipo", 422);
        }

        $conexion = Conexion::conectar();
        $solicitudDao = new SolicitudDAO($conexion);
        $dao = new PrestamoDAO($conexion);

        $solicitud = $solicitudDao->listarSolicitud($idSolicitud);

        if ($solicitud === null) {
            Conexion::desconectar();
            RespuestaJson::error("La solicitud no existe", 404);
        }

        if ($solicitud["tipo"] !== "Préstamo") {
            Conexion::desconectar();
            RespuestaJson::error("La solicitud indicada no es de tipo Préstamo", 422);
        }

        if ($dao->listarPrestamoActivoPorSolicitud($idSolicitud) !== null) {
            Conexion::desconectar();
            RespuestaJson::error("Esta solicitud ya tiene un préstamo activo. Registre la devolución antes de crear uno nuevo", 409);
        }

        //Solo se pueden entregar equipos que existan y que no estén en un préstamo activo en este momento
        $idsPrestados = $dao->listarIdsEquiposPrestados();

        foreach ($idsEquipo as $idEquipo) {
            if (!$dao->existeEquipo($idEquipo)) {
                Conexion::desconectar();
                RespuestaJson::error("El equipo " . $idEquipo . " no existe", 404);
            }

            if (in_array($idEquipo, $idsPrestados, true)) {
                Conexion::desconectar();
                RespuestaJson::error("El equipo " . $idEquipo . " ya está en un préstamo activo", 409);
            }
        }

        $idPrestamo = $dao->registrarPrestamo(
            $idSolicitud, $solicitanteNombre, $tipoDocumento, $documento, $_SESSION["cedula"], $idsEquipo
        );
        Conexion::desconectar();

        if ($idPrestamo === null) {
            RespuestaJson::error("No se pudo registrar el préstamo", 400);
        }

        RespuestaJson::exito(["mensaje" => "Préstamo registrado exitosamente", "id" => $idPrestamo], 201);
    }

    /**
     * RF10, RF14: registra la devolución de un préstamo activo. Espera {"id": 1, "noSeUso": [3, 4]},
     * donde noSeUso lista los ids de los equipos del préstamo (campo "id" de cada equipo del detalle) que se devuelven sin haber sido utilizados.
     */
    private function registrarDevolucion(): void {
        $this->exigirGestor();

        $datos = $this->leerJson();

        $idPrestamo = $this->aEntero($datos["id"] ?? null);

        if ($idPrestamo === null) {
            RespuestaJson::error("Falta el id del préstamo o es incorrecto", 422);
        }

        //Un campo sin enviar equivale a ninguna casilla marcada en el formulario
        $idsNoSeUso = $this->leerIds($datos["noSeUso"] ?? [], "Identificador de equipo incorrecto");

        $conexion = Conexion::conectar();
        $dao = new PrestamoDAO($conexion);

        $prestamo = $dao->listarPrestamo($idPrestamo);

        if ($prestamo === null) {
            Conexion::desconectar();
            RespuestaJson::error("El préstamo no existe", 404);
        }

        if ($prestamo["estado"] !== "Activo") {
            Conexion::desconectar();
            RespuestaJson::error("El préstamo ya fue devuelto", 409);
        }

        $resultado = $dao->registrarDevolucion($idPrestamo, $idsNoSeUso, $_SESSION["cedula"]);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo registrar la devolución", 400);
        }

        RespuestaJson::exito(["mensaje" => "Devolución registrada exitosamente"]);
    }

    /**
     * Edita los datos de quien retiró los equipos de un préstamo activo. Espera
     * {"id": 1, "solicitanteNombre": "...", "solicitanteTipoDocumento": "Cédula", "solicitanteDocumento": "..."}
     * con las mismas validaciones que la entrega. Solo administrador y técnico; 409 si el préstamo ya fue devuelto.
     */
    private function editarPrestamo(): void {
        $this->exigirGestor();

        $datos = $this->leerJson();

        $idPrestamo = $this->aEntero($datos["id"] ?? null);

        if ($idPrestamo === null) {
            RespuestaJson::error("Falta el id del préstamo o es incorrecto", 422);
        }

        $solicitante = $this->leerDatosSolicitante($datos);

        $conexion = Conexion::conectar();
        $dao = new PrestamoDAO($conexion);

        $prestamo = $dao->listarPrestamo($idPrestamo);

        if ($prestamo === null) {
            Conexion::desconectar();
            RespuestaJson::error("El préstamo no existe", 404);
        }

        if ($prestamo["estado"] !== "Activo") {
            Conexion::desconectar();
            RespuestaJson::error("El préstamo ya fue devuelto y no se puede editar", 409);
        }

        $resultado = $dao->modificarPrestamo($idPrestamo, $solicitante["nombre"], $solicitante["tipoDocumento"], $solicitante["documento"]);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo modificar el préstamo", 400);
        }

        RespuestaJson::exito(["mensaje" => "Préstamo modificado exitosamente"]);
    }

    /**
     * Elimina un préstamo y sus equipos (cascada). Espera {"id": 1}. Solo el administrador, aunque el préstamo siga activo
     * (en ese caso los equipos quedan libres). Un técnico o dirección reciben 403.
     */
    private function eliminarPrestamo(): void {
        if (!$this->tieneRol("administrador")) {
            RespuestaJson::error("Acceso denegado: solo el administrador puede eliminar préstamos", 403);
        }

        $datos = $this->leerJson();

        $idPrestamo = $this->aEntero($datos["id"] ?? null);

        if ($idPrestamo === null) {
            RespuestaJson::error("Falta el id del préstamo o es incorrecto", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new PrestamoDAO($conexion);

        if ($dao->listarPrestamo($idPrestamo) === null) {
            Conexion::desconectar();
            RespuestaJson::error("El préstamo no existe", 404);
        }

        $resultado = $dao->eliminarPrestamo($idPrestamo);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo eliminar el préstamo", 400);
        }

        RespuestaJson::exito(["mensaje" => "Préstamo eliminado exitosamente"]);
    }
}
