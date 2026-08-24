<?php
class ConectorPDO {
    private static $instancia = null;
    private $conexion;

    // Configuración para Pinggy TCP
    private $host = 'npope-167-60-191-50.run.pinggy-free.link';      //Remplazar con el nuevo host de pinggy cada vez que se reinicie
    private $port = 42931;        //Remplazar con el nuevo puerto de pinggy cada vez que se reinicie 
    private $db   = 'PeartoS.A'; 
    private $user = 'root';
    private $pass = '';       
    private $charset = 'utf8mb4';

    private function __construct() {
        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db};charset={$this->charset}";
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->conexion = new PDO($dsn, $this->user, $this->pass, $opciones);
        } catch (PDOException $e) {
            die("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }

    public static function getInstancia() {
        if (self::$instancia === null) {
            self::$instancia = new ConectorPDO();
        }
        return self::$instancia;
    }

    public function getConexion() {
        return $this->conexion;
    }
}
