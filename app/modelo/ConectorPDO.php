<?php
//Instalación del driver https://www.php.net/manual/en/pdo.installation.php
//Especificar en php.ini el extension_dir (debe apuntar a ext) y la extension pdo_mysql para este caso

//SINGLETON
class ConectorPDO {
    private string $servername;
    private int $port;
    private string $username;
    private string $password;
    private string $dbname;
    private ?PDO $conexion = null;

    //Atributo donde se almacenará la instancia única de la clase
    //?self = ?ConectorPDO
    private static ?self $instancia = null;

    //El constructor es privado y nunca se referencia directamente, la única forma de obtener el objeto es obtenerInstancia()
    private function __construct(string $servername, int $port, string $username, string $password, string $dbname) {
        $this->servername = $servername;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->dbname = $dbname;
        $this->conexion = null;
    }

    //Se restringe la posibilidad de clonar el objeto
    private function __clone(): void {}

    //Se previene la manipulación del objeto para que no pueda ser deserializado, la deserialización genera una copia sobre una nueva instancia
    public function __wakeup(): void {
        throw new Exception("No se puede deserializar un Singleton.");
    }

    //Se evita la posibilidad de serializar un objeto
    public function __serialize(): array {
        throw new LogicException("El objeto no puede serializarse.");
    }

    //Se evita la posibilidad de deserializar un objeto
    public function __unserialize(array $data): void {
        throw new LogicException("El objeto no puede deserializarse.");
    }

    /**
     * Retorna la instancia única de la clase, creándola la primera vez que se la solicita.
     * Los parámetros solo se utilizan en esa primera creación.
     */
    public static function obtenerInstancia(string $servername, int $port, string $username, string $password, string $dbname): ConectorPDO {
        if (self::$instancia === null) {
            self::$instancia = new self($servername, $port, $username, $password, $dbname);
        }

        return self::$instancia;
    }

    /**
     * Establece la conexión o retorna la que ya está activa.
     * @throws PDOException Si no se puede conectar, quien llama decide cómo responder.
     */
    public function establecerConexion(): PDO {
        //Si la conexión está funcionando, se la retorna directamente
        if ($this->conexion !== null) {
            return $this->conexion;
        }

        //Se define charset=utf8mb4 para evitar problemas con caracteres especiales o letras de más de un byte
        $dsn = "mysql:host={$this->servername};port={$this->port};dbname={$this->dbname};charset=utf8mb4";

        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $this->conexion = new PDO($dsn, $this->username, $this->password, $opciones);

        return $this->conexion;
    }

    public function desconectar(): void {
        $this->conexion = null;
    }
}

//Código para depuración
//Si retorna código de error 500, cambiar DB_HOST="localhost" a DB_HOST="127.0.0.1"
/*
require_once __DIR__ . "/../../config/config.php";
$conectorPDO = ConectorPDO::obtenerInstancia($_ENV['DB_HOST'], (int) $_ENV['DB_PUERTO'], $_ENV['DB_USUARIO'], $_ENV['DB_CLAVE'], $_ENV['DB_NOMBRE']);
$conexion = $conectorPDO->establecerConexion();
if (isset($conexion)) echo "Éxito";
*/
