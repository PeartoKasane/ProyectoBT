<?php

//El DAO instancia objetos Usuario, por lo que carga la clase él mismo para no depender de quién lo incluya
require_once RUTA_MODELO . "/Usuario.php";

/**
 * Clase que unifica todas las operaciones de acceso a datos
 * relacionadas con la entidad Usuario (buscar, listar, alta, baja, modificación, estado y sesión).
 *
 * DAO ~ Data Access Object. Reúne en una sola clase todo el acceso a datos de la entidad.
 */
class UsuarioDAO {
    private PDO $conexion;

    /**
     * Constructor parametrizado que recibe una conexión a la base de datos.
     * @param PDO $conexion La conexión a la base de datos. PRECONDICIÓN: No debe ser NULL.
     */
    public function __construct(PDO $conexion) {
        $this->conexion = $conexion;
    }

    /**
     * Busca un usuario por su cédula y determina sus roles.
     * @param string $cedula La cédula del usuario sin puntos ni guiones.
     * @return Usuario|null El objeto Usuario si existe, null en caso contrario.
     */
    public function buscarUsuario(string $cedula): ?Usuario {
        $sql = "
            SELECT
                u.cedula,
                u.nombre,
                u.apellido,
                u.email,
                u.claveHash,
                u.activo,
                u.sesionActiva,
                u.inicioSesion,

                CASE
                    WHEN a.cedula IS NOT NULL THEN TRUE
                    ELSE FALSE
                END AS administrador,

                CASE
                    WHEN d.cedula IS NOT NULL THEN TRUE
                    ELSE FALSE
                END AS docente,

                CASE
                    WHEN dir.cedula IS NOT NULL THEN TRUE
                    ELSE FALSE
                END AS direccion,

                CASE
                    WHEN t.cedula IS NOT NULL THEN TRUE
                    ELSE FALSE
                END AS tecnico

            FROM USUARIO AS u

            LEFT JOIN ADMINISTRADOR AS a
                ON a.cedula = u.cedula

            LEFT JOIN DOCENTE AS d
                ON d.cedula = u.cedula

            LEFT JOIN DIRECCION AS dir
                ON dir.cedula = u.cedula

            LEFT JOIN TECNICO AS t
                ON t.cedula = u.cedula

            WHERE u.cedula = :cedula
        ";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute(["cedula" => $cedula]);

        $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

        //Una vez usada la consulta, desconectar el objeto PDOStatement. https://www.php.net/manual/en/pdo.connections.php
        $consulta = null;

        if ($usuario === false) {
            return null;
        }

        return new Usuario(
            $usuario["cedula"],
            $usuario["nombre"],
            $usuario["apellido"],
            $usuario["email"],
            $usuario["claveHash"],
            (bool) $usuario["activo"],
            (bool) $usuario["sesionActiva"],
            $usuario["inicioSesion"],
            (bool) $usuario["administrador"],
            (bool) $usuario["docente"],
            (bool) $usuario["direccion"],
            (bool) $usuario["tecnico"]
        );
    }

    /**
     * Retorna los datos públicos de un usuario (sin la contraseña) para mostrarlos o editarlos.
     * El rol se informa como texto; si tuviera más de uno, se prioriza en este orden:
     * Administrador, Docente, Dirección, Técnico (el mismo criterio que usa la vista administrador.php).
     * @param string $cedula La cédula del usuario sin puntos ni guiones.
     * @return array|null Arreglo con cedula, nombre, apellido, email, activo y rol; null si no existe.
     */
    public function listarUsuario(string $cedula): ?array {
        $sql = "
            SELECT
                u.cedula,
                u.nombre,
                u.apellido,
                u.email,
                u.activo,

                CASE
                    WHEN a.cedula IS NOT NULL THEN 'Administrador'
                    WHEN d.cedula IS NOT NULL THEN 'Docente'
                    WHEN dir.cedula IS NOT NULL THEN 'Dirección'
                    WHEN t.cedula IS NOT NULL THEN 'Técnico'
                    ELSE ''
                END AS rol

            FROM USUARIO AS u

            LEFT JOIN ADMINISTRADOR AS a
                ON a.cedula = u.cedula

            LEFT JOIN DOCENTE AS d
                ON d.cedula = u.cedula

            LEFT JOIN DIRECCION AS dir
                ON dir.cedula = u.cedula

            LEFT JOIN TECNICO AS t
                ON t.cedula = u.cedula

            WHERE u.cedula = :cedula
        ";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(["cedula" => $cedula]);

        $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

        $consulta = null;

        if ($usuario === false) {
            return null;
        }

        //MySQL devuelve el BOOLEAN como 0 o 1, se lo convierte para que el JSON muestre true o false
        $usuario["activo"] = (bool) $usuario["activo"];

        return $usuario;
    }

    /**
     * Retorna los datos públicos de todos los usuarios (sin la contraseña).
     * @return array Lista de arreglos con cedula, nombre, apellido, email, activo y rol.
     */
    public function listarUsuarios(): array {
        $sql = "
            SELECT
                u.cedula,
                u.nombre,
                u.apellido,
                u.email,
                u.activo,

                CASE
                    WHEN a.cedula IS NOT NULL THEN 'Administrador'
                    WHEN d.cedula IS NOT NULL THEN 'Docente'
                    WHEN dir.cedula IS NOT NULL THEN 'Dirección'
                    WHEN t.cedula IS NOT NULL THEN 'Técnico'
                    ELSE ''
                END AS rol

            FROM USUARIO AS u

            LEFT JOIN ADMINISTRADOR AS a
                ON a.cedula = u.cedula

            LEFT JOIN DOCENTE AS d
                ON d.cedula = u.cedula

            LEFT JOIN DIRECCION AS dir
                ON dir.cedula = u.cedula

            LEFT JOIN TECNICO AS t
                ON t.cedula = u.cedula";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute();

        $usuarios = $consulta->fetchAll(PDO::FETCH_ASSOC);

        $consulta = null;

        for ($i = 0; $i < count($usuarios); $i++) {
            $usuarios[$i]["activo"] = (bool) $usuarios[$i]["activo"];
        }

        return $usuarios;
    }

    /**
     * Registra un nuevo usuario con su rol.
     *
     * @param string $cedula Cédula del usuario.
     * @param string $nombre Nombre del usuario.
     * @param string $apellido Apellido del usuario.
     * @param string $email Correo electrónico del usuario, puede ser vacío.
     * @param string $claveHash Hash de la contraseña.
     * @param string $rol Rol del empleado en el sistema: Administrador, Docente, Dirección o Técnico.
     *
     * @return bool TRUE si el registro se completa correctamente, FALSE en caso contrario.
     */
    public function registrarUsuario(string $cedula, string $nombre, string $apellido, string $email, string $claveHash, string $rol): bool {

        try {
            //Método que ejecuta de forma agrupada todas las instrucciones dirigidas a la base de datos
            //Si una instrucción falla, retorna excepción con la posibilidad de deshacer cambios con rollBack()
            $this->conexion->beginTransaction();

            $sqlUsuario = "INSERT INTO USUARIO (cedula, nombre, apellido, email, claveHash) VALUES (:cedula, :nombre, :apellido, :email, :claveHash)";

            $consultaUsuario = $this->conexion->prepare($sqlUsuario);

            //Un correo vacío se guarda como NULL, así el sistema muestra "Sin registrar" en vez de un texto en blanco
            $consultaUsuario->execute(["cedula" => $cedula, "nombre" => $nombre, "apellido" => $apellido, "email" => $email !== "" ? $email : null, "claveHash" => $claveHash]);

            switch ($rol) {
                case "Administrador":
                    $sqlRol = "INSERT INTO ADMINISTRADOR (cedula) VALUES (:cedula)";
                    break;
                case "Docente":
                    $sqlRol = "INSERT INTO DOCENTE (cedula) VALUES (:cedula)";
                    break;
                case "Dirección":
                    $sqlRol = "INSERT INTO DIRECCION (cedula) VALUES (:cedula)";
                    break;
                case "Técnico":
                    $sqlRol = "INSERT INTO TECNICO (cedula) VALUES (:cedula)";
                    break;
                default:
                    $this->conexion->rollBack();
                    return false;
            }

            $consultaRol = $this->conexion->prepare($sqlRol);

            $consultaRol->execute(["cedula" => $cedula]);

            //Confirma todas las operaciones realizadas.
            $this->conexion->commit();

            return true;

        } catch (PDOException $error) {

            //Verifica si se encuentra en una transacción
            if ($this->conexion->inTransaction()) {
                //Deshace los cambios causados por la excepción
                $this->conexion->rollBack();
            }

            return false;
        }
    }

    /**
     * Modifica los datos de un usuario y su rol.
     *
     * @param string $cedula Cédula del usuario a modificar.
     * @param string $nombre Nuevo nombre.
     * @param string $apellido Nuevo apellido.
     * @param string $email Nuevo correo electrónico, puede ser vacío.
     * @param string $claveHash Nuevo hash de contraseña.
     * @param string $rol Rol seleccionado.
     *
     * @return bool TRUE si la modificación se realiza correctamente, FALSE en caso contrario.
     */
    public function modificarUsuario(string $cedula, string $nombre, string $apellido, string $email, string $claveHash, string $rol): bool {
        try {
            $this->conexion->beginTransaction();

            $sqlUsuario = "UPDATE USUARIO SET nombre = :nombre, apellido = :apellido, email = :email, claveHash = :claveHash WHERE cedula = :cedula";
            $consultaUsuario = $this->conexion->prepare($sqlUsuario);
            $consultaUsuario->execute(["nombre" => $nombre, "apellido" => $apellido, "email" => $email !== "" ? $email : null, "claveHash" => $claveHash, "cedula" => $cedula]);

            //Antes de asignar el nuevo rol, se eliminan los posibles roles anteriores del usuario
            $sqlLimpiarAdministrador = "DELETE FROM ADMINISTRADOR WHERE cedula = :cedula";
            $this->conexion->prepare($sqlLimpiarAdministrador)->execute(["cedula" => $cedula]);

            $sqlLimpiarDocente = "DELETE FROM DOCENTE WHERE cedula = :cedula";
            $this->conexion->prepare($sqlLimpiarDocente)->execute(["cedula" => $cedula]);

            $sqlLimpiarDireccion = "DELETE FROM DIRECCION WHERE cedula = :cedula";
            $this->conexion->prepare($sqlLimpiarDireccion)->execute(["cedula" => $cedula]);

            $sqlLimpiarTecnico = "DELETE FROM TECNICO WHERE cedula = :cedula";
            $this->conexion->prepare($sqlLimpiarTecnico)->execute(["cedula" => $cedula]);

            switch ($rol) {
                case "Administrador":
                    $sqlRol = "INSERT INTO ADMINISTRADOR (cedula) VALUES (:cedula)";
                    break;
                case "Docente":
                    $sqlRol = "INSERT INTO DOCENTE (cedula) VALUES (:cedula)";
                    break;
                case "Dirección":
                    $sqlRol = "INSERT INTO DIRECCION (cedula) VALUES (:cedula)";
                    break;
                case "Técnico":
                    $sqlRol = "INSERT INTO TECNICO (cedula) VALUES (:cedula)";
                    break;
                default:
                    $this->conexion->rollBack();
                    return false;
            }

            $this->conexion->prepare($sqlRol)->execute(["cedula" => $cedula]);

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
     * Elimina un usuario y sus relaciones dentro del sistema.
     *
     * AVISO: En un sistema real, la baja de un usuario es lógica (ver cambiarEstadoActivo()).
     * Si el usuario ya tiene tickets, solicitudes o préstamos asociados, la base de datos impide
     * eliminarlo por las claves foráneas y el método retorna FALSE.
     *
     * @param string $cedula Cédula del usuario.
     *
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function eliminarUsuario(string $cedula): bool {

        try {
            $this->conexion->beginTransaction();

            $sqlAdministrador = "DELETE FROM ADMINISTRADOR WHERE cedula = :cedula";
            $consultaAdministrador = $this->conexion->prepare($sqlAdministrador);
            $consultaAdministrador->execute(["cedula" => $cedula]);

            $sqlDocente = "DELETE FROM DOCENTE WHERE cedula = :cedula";
            $consultaDocente = $this->conexion->prepare($sqlDocente);
            $consultaDocente->execute(["cedula" => $cedula]);

            $sqlDireccion = "DELETE FROM DIRECCION WHERE cedula = :cedula";
            $consultaDireccion = $this->conexion->prepare($sqlDireccion);
            $consultaDireccion->execute(["cedula" => $cedula]);

            $sqlTecnico = "DELETE FROM TECNICO WHERE cedula = :cedula";
            $consultaTecnico = $this->conexion->prepare($sqlTecnico);
            $consultaTecnico->execute(["cedula" => $cedula]);

            $sqlUsuario = "DELETE FROM USUARIO WHERE cedula = :cedula";
            $consultaUsuario = $this->conexion->prepare($sqlUsuario);
            $consultaUsuario->execute(["cedula" => $cedula]);

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
     * RF36: habilita o deshabilita una cuenta sin eliminarla.
     * @param string $cedula Cédula del usuario.
     * @param bool $activo TRUE para habilitar la cuenta, FALSE para deshabilitarla.
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function cambiarEstadoActivo(string $cedula, bool $activo): bool {
        try {
            $sql = "UPDATE USUARIO SET activo = :activo WHERE cedula = :cedula";
            $consulta = $this->conexion->prepare($sql);
            //(int) porque execute() envía los valores como texto, y un FALSE se enviaría como "" en vez de 0
            $consulta->execute(["activo" => (int) $activo, "cedula" => $cedula]);

            return true;
        } catch (PDOException $error) {
            return false;
        }
    }

    /**
     * RF37: registra si el usuario tiene una sesión activa o no.
     * @param string $cedula Cédula del usuario.
     * @param bool $activa TRUE al iniciar sesión, FALSE al cerrarla.
     * @return bool TRUE si la operación se completa correctamente, FALSE en caso contrario.
     */
    public function establecerSesionActiva(string $cedula, bool $activa): bool {
        try {
            //Al activarla se guarda el momento actual; al desactivarla se limpia
            $sql = "UPDATE USUARIO SET sesionActiva = :activa, inicioSesion = :inicioSesion WHERE cedula = :cedula";
            $consulta = $this->conexion->prepare($sql);
            //(int) por el mismo motivo que en cambiarEstadoActivo()
            $consulta->execute([
                "activa" => (int) $activa,
                "inicioSesion" => $activa ? date("Y-m-d H:i:s") : null,
                "cedula" => $cedula,
            ]);

            return true;
        } catch (PDOException $error) {
            return false;
        }
    }
}
