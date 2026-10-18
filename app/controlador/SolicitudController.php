<?php

require_once RUTA_MODELO . "/ConectorPDO.php";
require_once RUTA_MODELO . "/SolicitudDAO.php";
require_once RUTA_MODELO . "/PrestamoDAO.php";
require_once RUTA_MODELO . "/FiltroPalabras.php";
require_once RUTA_VISTA . "/RespuestaJson.php";

require_once RUTA_NUCLEO . "/Conexion.php";
require_once RUTA_NUCLEO . "/Token.php";

/**
 * Controlador de la API que gestiona las solicitudes de los docentes (preparación de laboratorio y préstamo).
 * Responde siempre en JSON. Gestiona todas las operaciones del recurso.
 *
 * Cada método HTTP representa una operación distinta:
 * GET = consultar (listado, detalle con ?id= o salas con ?recurso=salas),
 * POST = registrar una solicitud (o un comentario con ?recurso=comentarios),
 * PATCH = cambiar el estado o asignar un responsable,
 * PUT = editar los datos de la solicitud (o el texto de un comentario con ?recurso=comentarios),
 * DELETE = eliminar la solicitud (o un comentario con ?recurso=comentarios).
 *
 * Quién puede hacer qué:
 * - Consultar: todos los roles. Administrador, técnico y dirección ven todas las solicitudes (RF29); el docente solo las suyas.
 * - Registrar una solicitud: solo el docente (RF27, RF28).
 * - Cambiar el estado o asignar: solo administrador y técnico (RF29, RF30).
 * - Comentar: todos los roles; el docente solo en sus propias solicitudes (RF31).
 * - Editar o eliminar una solicitud: el administrador, o el docente dueño mientras esté Pendiente.
 *   No se puede editar con un préstamo activo ni eliminar si tiene algún préstamo registrado.
 * - Editar o eliminar un comentario: su autor o el administrador.
 */
class SolicitudController {
    //Valores que el sistema acepta (los mismos que los ENUM de la base de datos)
    private const TIPOS = ["Preparación de laboratorio", "Préstamo"];
    private const ESTADOS = ["Pendiente", "En proceso", "Resuelta"];

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
            "POST" => $this->alta(),
            "PATCH" => $this->modificar(),
            "PUT" => $this->editar(),
            "DELETE" => $this->baja(),
            default => RespuestaJson::error("Método no permitido", 405),
        };
    }

    /**
     * Verifica que haya una sesión iniciada y que el usuario tenga al menos un rol.
     * El control más fino (qué rol puede hacer qué) se hace en cada operación.
     */
    private function verificarAcceso(): void {
        if (!isset($_SESSION["cedula"])) {
            RespuestaJson::error("Acceso denegado: sesión no iniciada", 401);
        }

        if (!$this->tieneRol("administrador", "docente", "direccion", "tecnico")) {
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
     * RF29: administrador, técnico y dirección consultan todas las solicitudes; el docente solo las suyas.
     */
    private function esGestor(): bool {
        return $this->tieneRol("administrador", "tecnico", "direccion");
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
     * GET sin parámetros lista las solicitudes; con ?id= retorna el detalle; con ?recurso=salas retorna las salas.
     */
    private function listar(): void {
        if ($this->recurso() === "salas") {
            $this->listarSalas();
        } elseif (isset($_GET["id"])) {
            //Si el valor no es texto (por ejemplo ?id[]=1) se lo trata como vacío, y detalle() responde error
            $this->detalle(is_string($_GET["id"]) ? trim($_GET["id"]) : "");
        } else {
            $this->listarSolicitudes();
        }
    }

    private function listarSolicitudes(): void {
        $conexion = Conexion::conectar();
        $dao = new SolicitudDAO($conexion);

        if ($this->esGestor()) {
            $solicitudes = $dao->listarSolicitudes();
        } else {
            $solicitudes = $dao->listarSolicitudesPorDocente($_SESSION["cedula"]);
        }

        //Se desconecta antes de responder porque RespuestaJson::exito() termina la ejecución con exit
        Conexion::desconectar();

        RespuestaJson::exito($solicitudes);
    }

    /**
     * Lista las salas para el formulario de nueva solicitud. Solo las necesita el docente, que es quien registra solicitudes.
     */
    private function listarSalas(): void {
        if (!$this->tieneRol("docente")) {
            RespuestaJson::error("Acceso denegado: solo un docente puede registrar solicitudes", 403);
        }

        $conexion = Conexion::conectar();
        $dao = new SolicitudDAO($conexion);
        $salas = $dao->listarSalas();
        Conexion::desconectar();

        RespuestaJson::exito($salas);
    }

    /**
     * Retorna una solicitud con sus comentarios. Si quien consulta es administrador o técnico y la solicitud es un préstamo,
     * incluye el préstamo activo (si lo hay) para ofrecer la entrega o la devolución; si es administrador, también la lista
     * de técnicos para poder asignar la solicitud.
     */
    private function detalle(string $id): void {
        if ($id === "") {
            RespuestaJson::error("El id de la solicitud es obligatorio", 400);
        }

        $idSolicitud = $this->aEntero($id);

        if ($idSolicitud === null) {
            RespuestaJson::error("Id de solicitud incorrecto", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new SolicitudDAO($conexion);

        $solicitud = $dao->listarSolicitud($idSolicitud);

        if ($solicitud === null) {
            Conexion::desconectar();
            RespuestaJson::error("La solicitud no existe", 404);
        }

        //Un docente no puede consultar las solicitudes de otro docente
        if (!$this->esGestor() && $solicitud["cedulaDocente"] !== $_SESSION["cedula"]) {
            Conexion::desconectar();
            RespuestaJson::error("Acceso denegado: la solicitud pertenece a otro docente", 403);
        }

        //Si la solicitud es de tipo Préstamo, se busca si ya tiene un préstamo activo asociado
        $prestamoActivo = null;
        if ($solicitud["tipo"] === "Préstamo" && $this->tieneRol("administrador", "tecnico")) {
            $prestamoDao = new PrestamoDAO($conexion);
            $prestamoActivo = $prestamoDao->listarPrestamoActivoPorSolicitud($idSolicitud);
        }

        $datos = [
            "solicitud" => $solicitud,
            "comentarios" => $dao->listarComentarios($idSolicitud),
            "historial" => $dao->listarHistorial($idSolicitud),
            "prestamoActivo" => $prestamoActivo,
            "tecnicos" => $this->tieneRol("administrador") ? $dao->listarTecnicos() : [],
        ];

        Conexion::desconectar();

        RespuestaJson::exito($datos);
    }

    /**
     * POST sin ?recurso= registra una solicitud; POST con ?recurso=comentarios agrega un comentario.
     */
    private function alta(): void {
        match ($this->recurso()) {
            "" => $this->registrarSolicitud(),
            "comentarios" => $this->comentar(),
            default => RespuestaJson::error("Recurso inexistente", 404),
        };
    }

    /**
     * Lee y valida los datos de una solicitud del cuerpo JSON (los mismos para el alta y para la edición).
     * Si algún dato es incorrecto, responde el error y detiene la ejecución.
     * La sala es obligatoria en una preparación de laboratorio y opcional en un préstamo;
     * el programa es obligatorio en una preparación y se ignora en un préstamo.
     * @return array Con las claves tipo, idSala (int|null), programa (string|null), detalle, fecha y hora (HH:MM:SS).
     */
    private function leerDatosSolicitud(array $datos): array {
        $tipo = $this->texto($datos, "tipo");
        $programa = $this->texto($datos, "programa");
        $detalle = $this->texto($datos, "detalle");
        $fecha = $this->texto($datos, "fecha");
        $hora = $this->texto($datos, "hora");

        if (!in_array($tipo, self::TIPOS, true) || $detalle === "" || $fecha === "" || $hora === "") {
            RespuestaJson::error("Existen campos vacíos o inválidos", 422);
        }

        //La sala puede llegar vacía (null o ""), en cuyo caso se considera no indicada
        $valorSala = $datos["idSala"] ?? null;
        $idSala = null;

        if ($valorSala !== null && $valorSala !== "") {
            $idSala = $this->aEntero($valorSala);

            if ($idSala === null) {
                RespuestaJson::error("Sala incorrecta", 422);
            }
        }

        if ($tipo === "Preparación de laboratorio") {
            if ($idSala === null) {
                RespuestaJson::error("Debe seleccionar el laboratorio o sala a preparar", 422);
            }

            if ($programa === "") {
                RespuestaJson::error("Debe indicar el programa que necesita", 422);
            }
        } else {
            //Para un préstamo la sala es opcional (puede pedirse para retirar del depósito general) y no lleva programa
            $programa = "";
        }

        //Verifica que la fecha tenga formato AAAA-MM-DD y que exista realmente (no acepta 2026-02-31)
        $fechaValidada = DateTime::createFromFormat("Y-m-d", $fecha);
        if ($fechaValidada === false || $fechaValidada->format("Y-m-d") !== $fecha) {
            RespuestaJson::error("Fecha incorrecta, use el formato AAAA-MM-DD", 422);
        }

        if (!preg_match("/^([01][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/", $hora)) {
            RespuestaJson::error("Hora incorrecta, use el formato HH:MM", 422);
        }

        if (strlen($hora) === 5) {
            $hora .= ":00";
        }

        if (mb_strlen($detalle) > 255 || mb_strlen($programa) > 100) {
            RespuestaJson::error("El detalle admite hasta 255 caracteres y el programa hasta 100", 422);
        }

        //RF25: filtro de palabras ofensivas
        if (FiltroPalabras::contienePalabraOfensiva($detalle) || FiltroPalabras::contienePalabraOfensiva($programa)) {
            RespuestaJson::error("El formulario contiene lenguaje inapropiado", 422);
        }

        return [
            "tipo" => $tipo,
            "idSala" => $idSala,
            "programa" => $programa !== "" ? $programa : null,
            "detalle" => $detalle,
            "fecha" => $fecha,
            "hora" => $hora,
        ];
    }

    /**
     * RF27, RF28: registra una solicitud. Solo el docente. Espera:
     * {"tipo": "Preparación de laboratorio", "idSala": 1, "programa": "...", "detalle": "...", "fecha": "2026-10-03", "hora": "08:00"}
     */
    private function registrarSolicitud(): void {
        if (!$this->tieneRol("docente")) {
            RespuestaJson::error("Acceso denegado: solo un docente puede registrar solicitudes", 403);
        }

        $solicitud = $this->leerDatosSolicitud($this->leerJson());

        $conexion = Conexion::conectar();
        $dao = new SolicitudDAO($conexion);

        if ($solicitud["idSala"] !== null && !$dao->existeSala($solicitud["idSala"])) {
            Conexion::desconectar();
            RespuestaJson::error("La sala no existe", 404);
        }

        $idSolicitud = $dao->registrarSolicitud(
            $solicitud["tipo"], $_SESSION["cedula"], $solicitud["idSala"], $solicitud["programa"],
            $solicitud["detalle"], $solicitud["fecha"], $solicitud["hora"]
        );
        Conexion::desconectar();

        if ($idSolicitud === null) {
            RespuestaJson::error("No se pudo registrar la solicitud", 400);
        }

        RespuestaJson::exito(["mensaje" => "Solicitud registrada exitosamente", "id" => $idSolicitud], 201);
    }

    /**
     * RF31: agrega un comentario a una solicitud. Espera {"id": 1, "comentario": "..."}.
     */
    private function comentar(): void {
        $datos = $this->leerJson();

        $idSolicitud = $this->aEntero($datos["id"] ?? null);
        $comentario = $this->texto($datos, "comentario");

        if ($idSolicitud === null) {
            RespuestaJson::error("Falta el id de la solicitud o es incorrecto", 422);
        }

        if ($comentario === "") {
            RespuestaJson::error("Falta completar el texto del comentario", 422);
        }

        if (mb_strlen($comentario) > 500) {
            RespuestaJson::error("El comentario admite hasta 500 caracteres", 422);
        }

        if (FiltroPalabras::contienePalabraOfensiva($comentario)) {
            RespuestaJson::error("El comentario contiene lenguaje inapropiado", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new SolicitudDAO($conexion);

        $solicitud = $dao->listarSolicitud($idSolicitud);

        if ($solicitud === null) {
            Conexion::desconectar();
            RespuestaJson::error("La solicitud no existe", 404);
        }

        //Un docente solo puede comentar sus propias solicitudes
        if (!$this->esGestor() && $solicitud["cedulaDocente"] !== $_SESSION["cedula"]) {
            Conexion::desconectar();
            RespuestaJson::error("Acceso denegado: la solicitud pertenece a otro docente", 403);
        }

        $resultado = $dao->agregarComentario($idSolicitud, $_SESSION["cedula"], $comentario);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo agregar el comentario", 400);
        }

        RespuestaJson::exito(["mensaje" => "Comentario agregado exitosamente"], 201);
    }

    /**
     * RF29, RF30: cambia el estado o asigna un responsable. Solo administrador y técnico.
     * Espera {"id": 1, "estado": "En proceso"} o {"id": 1, "cedulaAsignado": "12345678"}, pero no ambos a la vez.
     */
    private function modificar(): void {
        if (!$this->tieneRol("administrador", "tecnico")) {
            RespuestaJson::error("Acceso denegado: rol incorrecto", 403);
        }

        $datos = $this->leerJson();

        $idSolicitud = $this->aEntero($datos["id"] ?? null);

        if ($idSolicitud === null) {
            RespuestaJson::error("Falta el id de la solicitud o es incorrecto", 422);
        }

        $cambiaEstado = array_key_exists("estado", $datos);
        $asigna = array_key_exists("cedulaAsignado", $datos);

        if ($cambiaEstado === $asigna) {
            RespuestaJson::error("Indique solo una operación: estado o cedulaAsignado", 422);
        }

        if ($cambiaEstado) {
            $this->cambiarEstado($idSolicitud, $this->texto($datos, "estado"));
        } else {
            $this->asignar($idSolicitud, $this->texto($datos, "cedulaAsignado"));
        }
    }

    private function cambiarEstado(int $idSolicitud, string $estado): void {
        if (!in_array($estado, self::ESTADOS, true)) {
            RespuestaJson::error("Estado inválido", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new SolicitudDAO($conexion);

        if ($dao->listarSolicitud($idSolicitud) === null) {
            Conexion::desconectar();
            RespuestaJson::error("La solicitud no existe", 404);
        }

        $resultado = $dao->cambiarEstado($idSolicitud, $estado, $_SESSION["cedula"]);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo actualizar el estado de la solicitud", 400);
        }

        RespuestaJson::exito(["mensaje" => "Estado actualizado"]);
    }

    private function asignar(int $idSolicitud, string $cedulaAsignado): void {
        //RF30: el técnico solo puede autoasignarse; el administrador puede asignar a cualquier técnico
        $soloTecnico = $this->tieneRol("tecnico") && !$this->tieneRol("administrador");

        if ($soloTecnico) {
            $cedulaAsignado = $_SESSION["cedula"];
        }

        if ($cedulaAsignado === "") {
            RespuestaJson::error("Falta la cédula del responsable", 422);
        }

        if (!preg_match("/^[1-9][0-9]{7}$/", $cedulaAsignado)) {
            RespuestaJson::error("Cédula incorrecta", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new SolicitudDAO($conexion);

        if ($dao->listarSolicitud($idSolicitud) === null) {
            Conexion::desconectar();
            RespuestaJson::error("La solicitud no existe", 404);
        }

        //Si quien asigna es administrador, se verifica que la cédula elegida sea realmente un técnico
        if (!$soloTecnico && !$dao->esTecnico($cedulaAsignado)) {
            Conexion::desconectar();
            RespuestaJson::error("Solo se puede asignar la solicitud a un usuario con rol Técnico", 422);
        }

        $resultado = $dao->asignarResponsable($idSolicitud, $cedulaAsignado, $_SESSION["cedula"]);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo asignar la solicitud", 400);
        }

        RespuestaJson::exito(["mensaje" => "Solicitud asignada"]);
    }

    /**
     * Verifica que el usuario pueda editar o eliminar la solicitud: el administrador, o el docente dueño mientras esté Pendiente.
     * Si no puede, desconecta, responde el error (403 si no es el dueño o no es docente, 409 si ya no está Pendiente) y detiene la ejecución.
     * @param array $solicitud La solicitud, tal como la retorna SolicitudDAO::listarSolicitud().
     * @param string $accion "editar" o "eliminar", para armar el mensaje de error.
     */
    private function exigirPropietarioOAdministrador(array $solicitud, string $accion): void {
        if ($this->tieneRol("administrador")) {
            return;
        }

        if (!$this->tieneRol("docente") || $solicitud["cedulaDocente"] !== $_SESSION["cedula"]) {
            Conexion::desconectar();
            RespuestaJson::error("Acceso denegado: solo el administrador o el docente dueño pueden " . $accion . " la solicitud", 403);
        }

        if ($solicitud["estado"] !== "Pendiente") {
            Conexion::desconectar();
            RespuestaJson::error("El docente solo puede " . $accion . " la solicitud mientras está Pendiente", 409);
        }
    }

    /**
     * PUT sin ?recurso= edita una solicitud; PUT con ?recurso=comentarios edita un comentario.
     */
    private function editar(): void {
        match ($this->recurso()) {
            "" => $this->editarSolicitud(),
            "comentarios" => $this->editarComentario(),
            default => RespuestaJson::error("Recurso inexistente", 404),
        };
    }

    /**
     * Edita los datos de una solicitud. Espera {"id": 1, "tipo": "...", "idSala": 1, "programa": "...", "detalle": "...", "fecha": "...", "hora": "..."}
     * con las mismas validaciones que el alta. El estado y el responsable no se modifican acá (se usa PATCH).
     */
    private function editarSolicitud(): void {
        $datos = $this->leerJson();

        $idSolicitud = $this->aEntero($datos["id"] ?? null);

        if ($idSolicitud === null) {
            RespuestaJson::error("Falta el id de la solicitud o es incorrecto", 422);
        }

        $nueva = $this->leerDatosSolicitud($datos);

        $conexion = Conexion::conectar();
        $dao = new SolicitudDAO($conexion);

        $solicitud = $dao->listarSolicitud($idSolicitud);

        if ($solicitud === null) {
            Conexion::desconectar();
            RespuestaJson::error("La solicitud no existe", 404);
        }

        $this->exigirPropietarioOAdministrador($solicitud, "editar");

        //Mientras los equipos están entregados no se cambian los datos de la solicitud
        $prestamoDao = new PrestamoDAO($conexion);
        if ($prestamoDao->listarPrestamoActivoPorSolicitud($idSolicitud) !== null) {
            Conexion::desconectar();
            RespuestaJson::error("La solicitud tiene un préstamo activo: registre la devolución antes de editarla", 409);
        }

        if ($nueva["idSala"] !== null && !$dao->existeSala($nueva["idSala"])) {
            Conexion::desconectar();
            RespuestaJson::error("La sala no existe", 404);
        }

        $resultado = $dao->modificarSolicitud(
            $idSolicitud, $nueva["tipo"], $nueva["idSala"], $nueva["programa"], $nueva["detalle"], $nueva["fecha"], $nueva["hora"], $_SESSION["cedula"]
        );
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo modificar la solicitud", 400);
        }

        RespuestaJson::exito(["mensaje" => "Solicitud modificada exitosamente"]);
    }

    /**
     * Edita el texto de un comentario. Espera {"id": 1, "comentario": "..."} donde id es el del comentario.
     * Solo su autor o el administrador.
     */
    private function editarComentario(): void {
        $datos = $this->leerJson();

        $idComentario = $this->aEntero($datos["id"] ?? null);
        $texto = $this->texto($datos, "comentario");

        if ($idComentario === null) {
            RespuestaJson::error("Falta el id del comentario o es incorrecto", 422);
        }

        if ($texto === "") {
            RespuestaJson::error("Falta completar el texto del comentario", 422);
        }

        if (mb_strlen($texto) > 500) {
            RespuestaJson::error("El comentario admite hasta 500 caracteres", 422);
        }

        if (FiltroPalabras::contienePalabraOfensiva($texto)) {
            RespuestaJson::error("El comentario contiene lenguaje inapropiado", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new SolicitudDAO($conexion);

        $comentario = $dao->listarComentario($idComentario);

        if ($comentario === null) {
            Conexion::desconectar();
            RespuestaJson::error("El comentario no existe", 404);
        }

        $this->exigirAutorOAdministrador($comentario);

        $resultado = $dao->modificarComentario($idComentario, $texto);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo modificar el comentario", 400);
        }

        RespuestaJson::exito(["mensaje" => "Comentario modificado exitosamente"]);
    }

    /**
     * Verifica que el usuario sea el autor del comentario o el administrador.
     * Un comentario cuyo autor fue eliminado (cedula null) solo lo puede modificar el administrador.
     */
    private function exigirAutorOAdministrador(array $comentario): void {
        if ($this->tieneRol("administrador")) {
            return;
        }

        if ($comentario["cedula"] === null || $comentario["cedula"] !== $_SESSION["cedula"]) {
            Conexion::desconectar();
            RespuestaJson::error("Acceso denegado: solo el autor o el administrador pueden modificar el comentario", 403);
        }
    }

    /**
     * DELETE sin ?recurso= elimina una solicitud; DELETE con ?recurso=comentarios elimina un comentario.
     */
    private function baja(): void {
        match ($this->recurso()) {
            "" => $this->eliminarSolicitud(),
            "comentarios" => $this->eliminarComentario(),
            default => RespuestaJson::error("Recurso inexistente", 404),
        };
    }

    /**
     * Elimina una solicitud junto con sus comentarios (cascada). Espera {"id": 1}.
     * Si la solicitud tiene algún préstamo (activo o finalizado) responde 409, porque PRESTAMO la referencia sin cascada.
     */
    private function eliminarSolicitud(): void {
        $datos = $this->leerJson();

        $idSolicitud = $this->aEntero($datos["id"] ?? null);

        if ($idSolicitud === null) {
            RespuestaJson::error("Falta el id de la solicitud o es incorrecto", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new SolicitudDAO($conexion);

        $solicitud = $dao->listarSolicitud($idSolicitud);

        if ($solicitud === null) {
            Conexion::desconectar();
            RespuestaJson::error("La solicitud no existe", 404);
        }

        $this->exigirPropietarioOAdministrador($solicitud, "eliminar");

        if ($dao->tienePrestamos($idSolicitud)) {
            Conexion::desconectar();
            RespuestaJson::error("No se puede eliminar la solicitud: tiene préstamos registrados", 409);
        }

        $resultado = $dao->eliminarSolicitud($idSolicitud);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo eliminar la solicitud", 400);
        }

        RespuestaJson::exito(["mensaje" => "Solicitud eliminada exitosamente"]);
    }

    /**
     * Elimina un comentario. Espera {"id": 1} donde id es el del comentario. Solo su autor o el administrador.
     */
    private function eliminarComentario(): void {
        $datos = $this->leerJson();

        $idComentario = $this->aEntero($datos["id"] ?? null);

        if ($idComentario === null) {
            RespuestaJson::error("Falta el id del comentario o es incorrecto", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new SolicitudDAO($conexion);

        $comentario = $dao->listarComentario($idComentario);

        if ($comentario === null) {
            Conexion::desconectar();
            RespuestaJson::error("El comentario no existe", 404);
        }

        $this->exigirAutorOAdministrador($comentario);

        $resultado = $dao->eliminarComentario($idComentario);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo eliminar el comentario", 400);
        }

        RespuestaJson::exito(["mensaje" => "Comentario eliminado exitosamente"]);
    }
}
