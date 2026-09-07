<?php

class Database
{
    private static $instance = null;
    private $connection;

    // Conexión local (descomentar para desarrollo local)
    // private $host = 'localhost';
    // private $dbName = 'lavorope_dblavoro';
    // private $username = 'root';
    // private $password = '';

    // Conexión remota (comentar para desarrollo local, descomentar para hosting)
    private $host = '204.93.224.230';
    private $dbName = 'lavorope_dblavoro';
    private $username = 'lavorope_adm';
    private $password = '7Jb4TcRpX120';

    // Conexión hosting (descomentar para hosting)
    // private $host = 'localhost';
    // private $dbName = 'lavorope_dblavoro';
    // private $username = 'lavorope_adm';
    // private $password = '7Jb4TcRpX120';

    private function __construct()
    {
        try {
            $this->connection = new PDO(
                "mysql:host={$this->host};dbname={$this->dbName};charset=utf8",
                $this->username,
                $this->password
            );
            // Opciones de seguridad y manejo de errores
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die('Error de conexión: ' . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->connection;
    }
}