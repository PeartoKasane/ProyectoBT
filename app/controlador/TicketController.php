<?php

require_once RUTA_MODELO . "/ConectorPDO.php";
require_once RUTA_MODELO . "/TicketDAO.php";
require_once RUTA_MODELO . "/FiltroPalabras.php";
require_once RUTA_VISTA . "/RespuestaJson.php";

require_once RUTA_NUCLEO . "/Conexion.php";
require_once RUTA_NUCLEO . "/Token.php";

/**
 * Controlador de la API que gestiona los tickets de incidencias.
 * Responde siempre en JSON. Gestiona todas las operaciones del recurso.
 *
 * Cada método HTTP representa una operación distinta:
 * GET = consultar (listado o detalle con ?id=), POST = registrar un ticket (o un comentario con ?recurso=comentarios),
 * PUT = editar los datos de un ticket (o un comentario con ?recurso=comentarios),
 * PATCH = cambiar el estado o asignar un responsable,
 * DELETE = eliminar un ticket (o un comentario con ?recurso=comentarios).
 *
 * Quién puede hacer qué:
 * - Consultar: todos los roles. Administrador, técnico y dirección ven todos los tickets (RF26); el docente solo los suyos.
 * - Registrar un ticket: solo el docente.
 * - Cambiar el estado o asignar: solo administrador y técnico (RF22).
 * - Comentar: todos los roles; el docente solo en sus propios tickets.
 * - Editar un ticket: el administrador, o el docente dueño mientras el ticket siga en el estado inicial (Pendiente).
 * - Eliminar un ticket: solo el administrador (sus equipos y comentarios se eliminan en cascada).
 * - Editar o eliminar un comentario: su autor o el administrador.
 */
class TicketController {
    //Valores que el sistema acepta (los mismos que los ENUM de la base de datos)
    private const TURNOS = ["Mañana", "Vespertino", "Nocturno"];
    private const INCIDENCIAS = ["No prende", "Teclado roto", "Mouse roto", "Monitor roto", "Otro problema"];
    private const ESTADOS = ["Pendiente", "Evaluando", "En proceso", "Terminado"];
    //Estado con el que nace todo ticket; mientras lo tenga, el docente dueño todavía puede editarlo
    private const ESTADO_INICIAL = "Pendiente";

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
            "PUT" => $this->editar(),
            "PATCH" => $this->modificar(),
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
     * RF26: administrador, técnico y dirección consultan todos los tickets; el docente solo los suyos.
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

    private function listar(): void {
        if (isset($_GET["id"])) {
            //Si el valor no es texto (por ejemplo ?id[]=1) se lo trata como vacío, y detalle() responde error
            $this->detalle(is_string($_GET["id"]) ? trim($_GET["id"]) : "");
        } else {
            $this->listarTickets();
        }
    }

    private function listarTickets(): void {
        $conexion = Conexion::conectar();
        $dao = new TicketDAO($conexion);

        if ($this->esGestor()) {
            $tickets = $dao->listarTickets();
        } else {
            $tickets = $dao->listarTicketsPorDocente($_SESSION["cedula"]);
        }

        //Se desconecta antes de responder porque RespuestaJson::exito() termina la ejecución con exit
        Conexion::desconectar();

        RespuestaJson::exito($tickets);
    }

    /**
     * Retorna un ticket con sus equipos y comentarios. Si quien consulta es administrador,
     * también incluye la lista de técnicos para poder asignar el ticket.
     */
    private function detalle(string $id): void {
        if ($id === "") {
            RespuestaJson::error("El id del ticket es obligatorio", 400);
        }

        $idTicket = $this->aEntero($id);

        if ($idTicket === null) {
            RespuestaJson::error("Id de ticket incorrecto", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new TicketDAO($conexion);

        $ticket = $dao->listarTicket($idTicket);

        if ($ticket === null) {
            Conexion::desconectar();
            RespuestaJson::error("El ticket no existe", 404);
        }

        //Un docente no puede consultar los tickets de otro docente
        if (!$this->esGestor() && $ticket["cedulaDocente"] !== $_SESSION["cedula"]) {
            Conexion::desconectar();
            RespuestaJson::error("Acceso denegado: el ticket pertenece a otro docente", 403);
        }

        $datos = [
            "ticket" => $ticket,
            "equipos" => $dao->listarEquiposPorTicket($idTicket),
            "comentarios" => $dao->listarComentarios($idTicket),
            "tecnicos" => $this->tieneRol("administrador") ? $dao->listarTecnicos() : [],
        ];

        Conexion::desconectar();

        RespuestaJson::exito($datos);
    }

    /**
     * POST sin ?recurso= registra un ticket; POST con ?recurso=comentarios agrega un comentario.
     */
    private function alta(): void {
        match ($this->recurso()) {
            "" => $this->registrarTicket(),
            "comentarios" => $this->comentar(),
            default => RespuestaJson::error("Recurso inexistente", 404),
        };
    }

    /**
     * Valida los datos de la planilla de un ticket (los mismos para el alta y para la edición) y los retorna ya normalizados.
     * Si algún dato es incorrecto, responde el error y detiene la ejecución.
     * Espera:
     * {"idSala": 1, "fecha": "2026-10-03", "horaEntrada": "08:00", "horaSalida": "10:00", "asignatura": "...", "grupo": "...", "turno": "Mañana",
     *  "equipos": [{"idEquipo": 1, "utilizado": true, "estudiante": "...", "incidencia": "No prende", "descripcion": "..."}]}
     * @param array $datos Cuerpo JSON recibido.
     * @return array Claves: idSala, fecha, horaEntrada, horaSalida, asignatura, grupo, turno, equipos.
     */
    private function leerDatosTicket(array $datos): array {
        $idSala = $this->aEntero($datos["idSala"] ?? null);
        $fecha = $this->texto($datos, "fecha");
        $horaEntrada = $this->texto($datos, "horaEntrada");
        $horaSalida = $this->texto($datos, "horaSalida");
        $asignatura = $this->texto($datos, "asignatura");
        $grupo = $this->texto($datos, "grupo");
        $turno = $this->texto($datos, "turno");

        if ($idSala === null || $fecha === "" || $horaEntrada === "" || $horaSalida === "" || $asignatura === "" || $grupo === "" || $turno === "") {
            RespuestaJson::error("Existen campos vacíos o inválidos", 422);
        }

        //Verifica que la fecha tenga formato AAAA-MM-DD y que exista realmente (no acepta 2026-02-31)
        $fechaValidada = DateTime::createFromFormat("Y-m-d", $fecha);
        if ($fechaValidada === false || $fechaValidada->format("Y-m-d") !== $fecha) {
            RespuestaJson::error("Fecha incorrecta, use el formato AAAA-MM-DD", 422);
        }

        $formatoHora = "/^([01][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/";
        if (!preg_match($formatoHora, $horaEntrada) || !preg_match($formatoHora, $horaSalida)) {
            RespuestaJson::error("Hora incorrecta, use el formato HH:MM", 422);
        }

        //Se completan los segundos para poder comparar las dos horas como texto
        if (strlen($horaEntrada) === 5) {
            $horaEntrada .= ":00";
        }
        if (strlen($horaSalida) === 5) {
            $horaSalida .= ":00";
        }

        if ($horaSalida <= $horaEntrada) {
            RespuestaJson::error("La hora de salida debe ser posterior a la hora de entrada", 422);
        }

        if (!in_array($turno, self::TURNOS, true)) {
            RespuestaJson::error("Turno inválido", 422);
        }

        if (mb_strlen($asignatura) > 80 || mb_strlen($grupo) > 20) {
            RespuestaJson::error("La asignatura admite hasta 80 caracteres y el grupo hasta 20", 422);
        }

        //RF25: filtro de palabras ofensivas
        if (FiltroPalabras::contienePalabraOfensiva($asignatura) || FiltroPalabras::contienePalabraOfensiva($grupo)) {
            RespuestaJson::error("El formulario contiene lenguaje inapropiado", 422);
        }

        return [
            "idSala" => $idSala,
            "fecha" => $fecha,
            "horaEntrada" => $horaEntrada,
            "horaSalida" => $horaSalida,
            "asignatura" => $asignatura,
            "grupo" => $grupo,
            "turno" => $turno,
            "equipos" => $this->leerEquipos($datos["equipos"] ?? null),
        ];
    }

    /**
     * Verifica que la sala exista y que cada equipo declarado pertenezca a ella.
     * Si algo falla, cierra la conexión y responde el error. Se usa tanto en el alta como en la edición.
     */
    private function verificarSalaYEquipos(TicketDAO $dao, int $idSala, array $equipos): void {
        if (!$dao->existeSala($idSala)) {
            Conexion::desconectar();
            RespuestaJson::error("La sala no existe", 404);
        }

        //Cada equipo declarado debe pertenecer a la sala indicada
        foreach ($equipos as $equipo) {
            if (!$dao->equipoPerteneceASala($equipo["idEquipo"], $idSala)) {
                Conexion::desconectar();
                RespuestaJson::error("El equipo " . $equipo["idEquipo"] . " no existe en la sala indicada", 422);
            }
        }
    }

    /**
     * Registra la planilla de un ticket (los campos se describen en leerDatosTicket()).
     */
    private function registrarTicket(): void {
        if (!$this->tieneRol("docente")) {
            RespuestaJson::error("Acceso denegado: solo un docente puede registrar tickets", 403);
        }

        $ticket = $this->leerDatosTicket($this->leerJson());

        $conexion = Conexion::conectar();
        $dao = new TicketDAO($conexion);

        $this->verificarSalaYEquipos($dao, $ticket["idSala"], $ticket["equipos"]);

        $idTicket = $dao->registrarTicket(
            $ticket["idSala"], $ticket["fecha"], $ticket["horaEntrada"], $ticket["horaSalida"], $ticket["asignatura"],
            $_SESSION["cedula"], $ticket["grupo"], $ticket["turno"], $ticket["equipos"]
        );
        Conexion::desconectar();

        if ($idTicket === null) {
            RespuestaJson::error("No se pudo registrar el ticket", 400);
        }

        RespuestaJson::exito(["mensaje" => "Ticket registrado exitosamente", "id" => $idTicket], 201);
    }

    /**
     * Valida el detalle de equipos de la planilla y lo retorna con el formato que espera TicketDAO::registrarTicket().
     * Si algún dato es incorrecto, responde el error y detiene la ejecución.
     * @param mixed $datosEquipos Valor de la clave "equipos" del JSON recibido.
     */
    private function leerEquipos(mixed $datosEquipos): array {
        if (!is_array($datosEquipos) || count($datosEquipos) === 0) {
            RespuestaJson::error("Debe indicar al menos un equipo", 422);
        }

        $equipos = [];
        $idsVistos = [];

        foreach ($datosEquipos as $datosEquipo) {
            if (!is_array($datosEquipo)) {
                RespuestaJson::error("El detalle de los equipos es inválido", 422);
            }

            $idEquipo = $this->aEntero($datosEquipo["idEquipo"] ?? null);

            if ($idEquipo === null) {
                RespuestaJson::error("Identificador de equipo incorrecto", 422);
            }

            if (in_array($idEquipo, $idsVistos, true)) {
                RespuestaJson::error("El equipo " . $idEquipo . " está repetido en la planilla", 422);
            }
            $idsVistos[] = $idEquipo;

            //En JSON un campo sin enviar equivale a una casilla sin marcar en el formulario HTML
            $utilizado = $datosEquipo["utilizado"] ?? false;
            if (!is_bool($utilizado)) {
                RespuestaJson::error("El campo utilizado debe ser true o false", 422);
            }

            $estudiante = $this->texto($datosEquipo, "estudiante");
            $incidencia = $this->texto($datosEquipo, "incidencia");
            $descripcion = $this->texto($datosEquipo, "descripcion");

            if ($incidencia !== "" && !in_array($incidencia, self::INCIDENCIAS, true)) {
                RespuestaJson::error("Una de las incidencias seleccionadas no es válida", 422);
            }

            //Si el equipo fue utilizado y tuvo incidencia, es obligatorio indicar el nombre del estudiante
            if ($utilizado && $incidencia !== "" && $estudiante === "") {
                RespuestaJson::error("Debe indicar el nombre del estudiante del equipo con incidencia", 422);
            }

            if (mb_strlen($estudiante) > 100 || mb_strlen($descripcion) > 255) {
                RespuestaJson::error("El estudiante admite hasta 100 caracteres y la descripción hasta 255", 422);
            }

            if (FiltroPalabras::contienePalabraOfensiva($estudiante) || FiltroPalabras::contienePalabraOfensiva($descripcion)) {
                RespuestaJson::error("El formulario contiene lenguaje inapropiado", 422);
            }

            $equipos[] = [
                "idEquipo" => $idEquipo,
                "utilizado" => $utilizado,
                "estudiante" => $estudiante !== "" ? $estudiante : null,
                "incidencia" => $incidencia !== "" ? $incidencia : null,
                "descripcion" => $descripcion !== "" ? $descripcion : null,
            ];
        }

        return $equipos;
    }

    /**
     * RF23: agrega un comentario a un ticket. Espera {"id": 1, "comentario": "..."}.
     */
    private function comentar(): void {
        $datos = $this->leerJson();

        $idTicket = $this->aEntero($datos["id"] ?? null);
        $comentario = $this->texto($datos, "comentario");

        if ($idTicket === null) {
            RespuestaJson::error("Falta el id del ticket o es incorrecto", 422);
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
        $dao = new TicketDAO($conexion);

        $ticket = $dao->listarTicket($idTicket);

        if ($ticket === null) {
            Conexion::desconectar();
            RespuestaJson::error("El ticket no existe", 404);
        }

        //Un docente solo puede comentar sus propios tickets
        if (!$this->esGestor() && $ticket["cedulaDocente"] !== $_SESSION["cedula"]) {
            Conexion::desconectar();
            RespuestaJson::error("Acceso denegado: el ticket pertenece a otro docente", 403);
        }

        $resultado = $dao->agregarComentario($idTicket, $_SESSION["cedula"], $comentario);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo agregar el comentario", 400);
        }

        RespuestaJson::exito(["mensaje" => "Comentario agregado exitosamente"], 201);
    }

    /**
     * PUT sin ?recurso= edita los datos de un ticket; PUT con ?recurso=comentarios edita un comentario.
     */
    private function editar(): void {
        match ($this->recurso()) {
            "" => $this->editarTicket(),
            "comentarios" => $this->editarComentario(),
            default => RespuestaJson::error("Recurso inexistente", 404),
        };
    }

    /**
     * Edita los datos de un ticket. Espera los mismos campos que el alta más el id: {"id": 1, "idSala": 1, ...}.
     * Los equipos enviados reemplazan a los anteriores. Puede el administrador, o el docente dueño mientras el ticket esté en el estado inicial.
     */
    private function editarTicket(): void {
        if (!$this->tieneRol("administrador", "docente")) {
            RespuestaJson::error("Acceso denegado: rol incorrecto", 403);
        }

        $datos = $this->leerJson();

        $idTicket = $this->aEntero($datos["id"] ?? null);

        if ($idTicket === null) {
            RespuestaJson::error("Falta el id del ticket o es incorrecto", 422);
        }

        $ticketNuevo = $this->leerDatosTicket($datos);

        $conexion = Conexion::conectar();
        $dao = new TicketDAO($conexion);

        $ticket = $dao->listarTicket($idTicket);

        if ($ticket === null) {
            Conexion::desconectar();
            RespuestaJson::error("El ticket no existe", 404);
        }

        //El docente solo edita sus propios tickets y únicamente mientras nadie empezó a tratarlos
        if (!$this->tieneRol("administrador")) {
            if ($ticket["cedulaDocente"] !== $_SESSION["cedula"]) {
                Conexion::desconectar();
                RespuestaJson::error("Acceso denegado: el ticket pertenece a otro docente", 403);
            }

            if ($ticket["estado"] !== self::ESTADO_INICIAL) {
                Conexion::desconectar();
                RespuestaJson::error("El ticket ya no se puede editar porque salió del estado " . self::ESTADO_INICIAL, 403);
            }
        }

        $this->verificarSalaYEquipos($dao, $ticketNuevo["idSala"], $ticketNuevo["equipos"]);

        $resultado = $dao->modificarTicket(
            $idTicket, $ticketNuevo["idSala"], $ticketNuevo["fecha"], $ticketNuevo["horaEntrada"], $ticketNuevo["horaSalida"],
            $ticketNuevo["asignatura"], $ticketNuevo["grupo"], $ticketNuevo["turno"], $ticketNuevo["equipos"]
        );
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo modificar el ticket", 400);
        }

        RespuestaJson::exito(["mensaje" => "Ticket modificado exitosamente"]);
    }

    /**
     * Edita el texto de un comentario. Espera {"id": 1, "comentario": "..."} con ?recurso=comentarios. Puede su autor o el administrador.
     */
    private function editarComentario(): void {
        $datos = $this->leerJson();

        $idComentario = $this->aEntero($datos["id"] ?? null);
        $comentario = $this->texto($datos, "comentario");

        if ($idComentario === null) {
            RespuestaJson::error("Falta el id del comentario o es incorrecto", 422);
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
        $dao = new TicketDAO($conexion);

        $this->verificarAutorComentario($dao, $idComentario);

        $resultado = $dao->modificarComentario($idComentario, $comentario);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo modificar el comentario", 400);
        }

        RespuestaJson::exito(["mensaje" => "Comentario modificado exitosamente"]);
    }

    /**
     * Verifica que el comentario exista y que quien opera sea su autor o el administrador.
     * Si algo falla, cierra la conexión y responde el error.
     */
    private function verificarAutorComentario(TicketDAO $dao, int $idComentario): void {
        $registro = $dao->listarComentario($idComentario);

        if ($registro === null) {
            Conexion::desconectar();
            RespuestaJson::error("El comentario no existe", 404);
        }

        //Si el autor fue eliminado la cédula es null y solo el administrador puede operar el comentario
        $esAutor = $registro["cedula"] !== null && $registro["cedula"] === $_SESSION["cedula"];

        if (!$esAutor && !$this->tieneRol("administrador")) {
            Conexion::desconectar();
            RespuestaJson::error("Acceso denegado: solo el autor o un administrador puede operar el comentario", 403);
        }
    }

    /**
     * DELETE sin ?recurso= elimina un ticket; DELETE con ?recurso=comentarios elimina un comentario.
     * Ambos esperan {"id": 1}.
     */
    private function baja(): void {
        match ($this->recurso()) {
            "" => $this->eliminarTicket(),
            "comentarios" => $this->eliminarComentario(),
            default => RespuestaJson::error("Recurso inexistente", 404),
        };
    }

    /**
     * Elimina un ticket. Solo el administrador. Sus equipos y comentarios se eliminan en cascada.
     */
    private function eliminarTicket(): void {
        if (!$this->tieneRol("administrador")) {
            RespuestaJson::error("Acceso denegado: solo un administrador puede eliminar tickets", 403);
        }

        $idTicket = $this->aEntero($this->leerJson()["id"] ?? null);

        if ($idTicket === null) {
            RespuestaJson::error("Falta el id del ticket o es incorrecto", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new TicketDAO($conexion);

        if ($dao->listarTicket($idTicket) === null) {
            Conexion::desconectar();
            RespuestaJson::error("El ticket no existe", 404);
        }

        $resultado = $dao->eliminarTicket($idTicket);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo eliminar el ticket", 400);
        }

        RespuestaJson::exito(["mensaje" => "Ticket eliminado exitosamente"]);
    }

    /**
     * Elimina un comentario. Puede su autor o el administrador.
     */
    private function eliminarComentario(): void {
        $idComentario = $this->aEntero($this->leerJson()["id"] ?? null);

        if ($idComentario === null) {
            RespuestaJson::error("Falta el id del comentario o es incorrecto", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new TicketDAO($conexion);

        $this->verificarAutorComentario($dao, $idComentario);

        $resultado = $dao->eliminarComentario($idComentario);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo eliminar el comentario", 400);
        }

        RespuestaJson::exito(["mensaje" => "Comentario eliminado exitosamente"]);
    }

    /**
     * RF21, RF22: cambia el estado o asigna un responsable. Solo administrador y técnico.
     * Espera {"id": 1, "estado": "En proceso"} o {"id": 1, "cedulaAsignado": "12345678"}, pero no ambos a la vez.
     */
    private function modificar(): void {
        if (!$this->tieneRol("administrador", "tecnico")) {
            RespuestaJson::error("Acceso denegado: rol incorrecto", 403);
        }

        $datos = $this->leerJson();

        $idTicket = $this->aEntero($datos["id"] ?? null);

        if ($idTicket === null) {
            RespuestaJson::error("Falta el id del ticket o es incorrecto", 422);
        }

        $cambiaEstado = array_key_exists("estado", $datos);
        $asigna = array_key_exists("cedulaAsignado", $datos);

        if ($cambiaEstado === $asigna) {
            RespuestaJson::error("Indique solo una operación: estado o cedulaAsignado", 422);
        }

        if ($cambiaEstado) {
            $this->cambiarEstado($idTicket, $this->texto($datos, "estado"));
        } else {
            $this->asignar($idTicket, $this->texto($datos, "cedulaAsignado"));
        }
    }

    private function cambiarEstado(int $idTicket, string $estado): void {
        if (!in_array($estado, self::ESTADOS, true)) {
            RespuestaJson::error("Estado inválido", 422);
        }

        $conexion = Conexion::conectar();
        $dao = new TicketDAO($conexion);

        if ($dao->listarTicket($idTicket) === null) {
            Conexion::desconectar();
            RespuestaJson::error("El ticket no existe", 404);
        }

        $resultado = $dao->cambiarEstado($idTicket, $estado);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo actualizar el estado del ticket", 400);
        }

        RespuestaJson::exito(["mensaje" => "Estado actualizado"]);
    }

    private function asignar(int $idTicket, string $cedulaAsignado): void {
        //RF22: el técnico solo puede autoasignarse; el administrador puede asignar a cualquier técnico
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
        $dao = new TicketDAO($conexion);

        if ($dao->listarTicket($idTicket) === null) {
            Conexion::desconectar();
            RespuestaJson::error("El ticket no existe", 404);
        }

        //Si quien asigna es administrador, se verifica que la cédula elegida sea realmente un técnico
        if (!$soloTecnico && !$dao->esTecnico($cedulaAsignado)) {
            Conexion::desconectar();
            RespuestaJson::error("Solo se puede asignar el ticket a un usuario con rol Técnico", 422);
        }

        $resultado = $dao->asignarResponsable($idTicket, $cedulaAsignado);
        Conexion::desconectar();

        if (!$resultado) {
            RespuestaJson::error("No se pudo asignar el ticket", 400);
        }

        RespuestaJson::exito(["mensaje" => "Ticket asignado"]);
    }
}
