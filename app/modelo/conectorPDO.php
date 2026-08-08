<?php
//Instalación del driver https://www.php.net/manual/en/pdo.installation.php
//LEER ATENTAMENTE CÓMO SE CONFIGURA TANTO EN LINUX COMO EN WINDOWS
//Especificar en php.ini el extension_dir (debe apuntar a ext) y la extension pdo_mysql para este caso
class ConectorPDO
{
    private string $servername;
    private string $username;
    private string $password;
    private string $dbname;
    private ?PDO $conexion;

    public function __construct (string $servername, string $username, string $password, string $dbname) {
        $this->servername = $servername;
        $this->username = $username;
        $this->password = $password;
        $this->dbname = $dbname;
        $this->conexion = null;
    }

    public function establecerConexion(): PDO {
        try {
            $this->conexion = new PDO("mysql:host=$this->servername;dbname=$this->dbname", $this->username, $this->password);
            // set the PDO error mode to exception
            $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Error al conectar..." . $e->getMessage();
        }
        return $this->conexion;
    }

    public function desconectar() {
        $this->conexion = null;
    }
};

//Código para depuración
//$ConectorPDO = new ConectorPDO ("localhost:3306", "leandro", "123", "test");
//$ConectorPDO->establecerConexion();

?>