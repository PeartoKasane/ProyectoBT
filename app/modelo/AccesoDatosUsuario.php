<?php
require_once __DIR__ . '/ConectorPDO.php';
require_once __DIR__ . '/Usuario.php';

class AccesoDatosUsuario {
    private $pdo;

    public function __construct($conexion = null) {
        $this->pdo = $conexion ?? ConectorPDO::getInstancia()->getConexion();
    }

    // DQL - Obtener usuario para Login y verificación de clave
    public function obtenerPorDocumento($documento) {
        $sql = "SELECT documento, nombre, apellido, email, clave, rol FROM usuarios WHERE documento = :doc LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':doc' => $documento]);
        $row = $stmt->fetch();

        if ($row) {
            return new Usuario(
                $row['documento'],
                $row['nombre'],
                $row['apellido'],
                $row['email'],
                $row['clave'],
                $row['rol']
            );
        }
        return null;
    }

    // DQL - Listar todos los usuarios para el Administrador (Sin logística)
    public function listarUsuarios() {
        $sql = "SELECT documento AS cedula, nombre, apellido, rol, 
                       (CASE WHEN rol = 'Administrador' THEN 1 ELSE 0 END) AS administrador
                FROM usuarios";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // DQL - Listar usuarios por rol específico (Para paneles de Docente / Dirección)
    public function listarPorRol($rol) {
        $sql = "SELECT documento AS cedula, nombre, apellido, email, rol 
                FROM usuarios 
                WHERE rol = :rol";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':rol' => $rol]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // DML - Guardar / Alta de empleado (Crea el hash criptográfico antes de almacenar)
    public function guardarEmpleado($cedula, $nombre, $apellido, $rol, $clavePlana) {
        // Generación de hash seguro con BCRYPT (sal aleatoria interna + hash binario/string)
        $claveHash = password_hash($clavePlana, PASSWORD_BCRYPT);

        $sql = "INSERT INTO usuarios (documento, nombre, apellido, rol, clave) 
                VALUES (:doc, :nom, :ape, :rol, :clave)
                ON DUPLICATE KEY UPDATE nombre = :nom, apellido = :ape, rol = :rol";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':doc' => $cedula,
            ':nom' => $nombre,
            ':ape' => $apellido,
            ':rol' => $rol,
            ':clave' => $claveHash
        ]);
    }

    // DML - Eliminar empleado
    public function eliminarEmpleado($cedula) {
        $sql = "DELETE FROM usuarios WHERE documento = :doc";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':doc' => $cedula]);
    }
}