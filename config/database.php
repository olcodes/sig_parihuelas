<?php
/**
 * CONFIGURACIÓN UNIVERSAL DE BASE DE DATOS
 * Compatible con cualquier entorno: Local, Hosting compartido, VPS, Cloud
 * 
 * @author Sistema Lavoro-ERP  
 * @version 3.0 - Configuración Ultra-robusta
 */

class Database
{
    private static $instance = null;
    private $connection;

    // ==========================================
    // CONFIGURACIONES DE ENTORNO
    // ==========================================
    // ⚠️  Estas propiedades se sobrescriben automáticamente según el entorno
    //     mediante detectEnvironmentSettings(). No modificar aquí.
    
    private $host = null;
    private $dbName = null;
    private $username = null;
    private $password = null;

    // ──────────────────────────────────────────
    // REFERENCIA RÁPIDA DE CONFIGURACIONES
    // (Copiar/pegar en detectEnvironmentSettings si es necesario)
    // ──────────────────────────────────────────
    
    // 🏠 DESARROLLO LOCAL (PHP built-in server)
    //   host='204.93.224.230', user='lavorope_adm', pass='7Jb4TcRpX120'
    
    // 🌐 HOSTING (BD local en el servidor)
    //   host='localhost', user='lavorope_adm', pass='7Jb4TcRpX120'

    // ==========================================
    // DETECCIÓN AUTOMÁTICA DE ENTORNO (OPCIONAL)
    // ==========================================
    
    /**
     * Constructor - Inicializa la conexión según el entorno
     */
    private function __construct()
    {
        // Detección automática de entorno (opcional)
        $this->detectEnvironmentSettings();
        
        try {
            $this->connection = new PDO(
                "mysql:host={$this->host};dbname={$this->dbName};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    // Opciones de seguridad y rendimiento
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                    PDO::ATTR_TIMEOUT => 30,
                    PDO::ATTR_PERSISTENT => false
                ]
            );
            
            // Configuraciones adicionales para MySQL
            $this->connection->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            $this->connection->exec("SET time_zone = '-05:00'"); // Zona horaria Perú
            
        } catch (PDOException $e) {
            // Log del error para debugging
            if (isset($_GET['debug_db'])) {
                echo "<div style='background:#ffe6e6;border:1px solid #ff9999;padding:15px;margin:10px;border-radius:5px;'>";
                echo "<h4 style='color:#cc0000;'>❌ Error de Conexión a Base de Datos</h4>";
                echo "<p><strong>Host:</strong> {$this->host}</p>";
                echo "<p><strong>Database:</strong> {$this->dbName}</p>";
                echo "<p><strong>Usuario:</strong> {$this->username}</p>";
                echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
                echo "</div>";
            }
            
            die('Error de conexión a la base de datos. Verifique la configuración.');
        }
    }

    /**
     * Detección automática de configuración según el entorno
     *
     * LOCAL   (localhost / 127.0.0.1 / 192.168.x / .local):
     *   Conecta a la BD remota via IP publica (para desarrollo)
     *
     * HOSTING (cualquier otro dominio):
     *   Conecta a MySQL local del servidor
     */
    private function detectEnvironmentSettings()
    {
        $isLocal = (
            isset($_SERVER['HTTP_HOST']) && (
                strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
                strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
                strpos($_SERVER['HTTP_HOST'], '192.168.') !== false ||
                strpos($_SERVER['HTTP_HOST'], '.local') !== false
            )
        );
        
        if ($isLocal) {
            // 🏠 Entorno LOCAL → conectar a BD remota vía IP
            $this->host = 'localhost';
            $this->dbName = 'lavorope_dblavoro';
            $this->username = 'lavorope_adm';
            $this->password = '7Jb4TcRpX120';
        } else {
            // 🌐 Entorno HOSTING → conectar a MySQL local del servidor
            $this->host = 'localhost';
            $this->dbName = 'lavorope_dblavoro';
            $this->username = 'root';
            $this->password = '';
        }
    }

    /**
     * Singleton pattern - Obtiene la instancia única
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Obtiene la conexión PDO
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Reconecta forzando una NUEVA conexión PDO (útil cuando MySQL ha matado la conexión por timeout)
     * Reemplaza la conexión interna para que el singleton funcione correctamente.
     */
    public function reconnect(): PDO
    {
        $this->connection = new PDO(
            "mysql:host={$this->host};dbname={$this->dbName};charset=utf8mb4",
            $this->username,
            $this->password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                PDO::ATTR_TIMEOUT => 30,
                PDO::ATTR_PERSISTENT => false
            ]
        );
        return $this->connection;
    }

    /**
     * Métodos de conveniencia para compatibilidad
     */
    public function query($sql)
    {
        return $this->connection->query($sql);
    }

    public function prepare($sql)
    {
        return $this->connection->prepare($sql);
    }

    public function exec($sql)
    {
        return $this->connection->exec($sql);
    }

    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Test de conexión para debugging
     */
    public function testConnection()
    {
        try {
            $stmt = $this->connection->query("SELECT 1 as test, NOW() as current_time");
            $result = $stmt->fetch();
            return [
                'success' => true,
                'test_value' => $result['test'],
                'server_time' => $result['current_time'],
                'host' => $this->host,
                'database' => $this->dbName,
                'user' => $this->username
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'host' => $this->host,
                'database' => $this->dbName,
                'user' => $this->username
            ];
        }
    }
}

// ==========================================
// DIAGNÓSTICO DE BASE DE DATOS
// ==========================================
if (isset($_GET['debug_db']) || isset($_GET['test_db'])) {
    echo "<div style='background:#f8f9fa;border:1px solid #dee2e6;padding:20px;margin:20px;border-radius:8px;font-family:monospace;font-size:13px;'>";
    echo "<h3 style='color:#495057;margin-top:0;'>🗄️ DIAGNÓSTICO DE BASE DE DATOS</h3>";
    
    try {
        $db = Database::getInstance();
        $test = $db->testConnection();
        
        if ($test['success']) {
            echo "<div style='background:#e8f5e8;padding:15px;border-radius:5px;margin:10px 0;'>";
            echo "<h4 style='margin:0;color:#2e7d32;'>✅ Conexión Exitosa</h4>";
            echo "<table style='width:100%;margin-top:10px;'>";
            echo "<tr><td><strong>Host:</strong></td><td style='color:#388e3c;'>" . $test['host'] . "</td></tr>";
            echo "<tr><td><strong>Base de Datos:</strong></td><td style='color:#388e3c;'>" . $test['database'] . "</td></tr>";
            echo "<tr><td><strong>Usuario:</strong></td><td style='color:#388e3c;'>" . $test['user'] . "</td></tr>";
            echo "<tr><td><strong>Hora del Servidor:</strong></td><td style='color:#388e3c;'>" . $test['server_time'] . "</td></tr>";
            echo "<tr><td><strong>Test Value:</strong></td><td style='color:#388e3c;'>" . $test['test_value'] . "</td></tr>";
            echo "</table>";
            echo "</div>";
            
            // Test adicional de tablas
            try {
                $stmt = $db->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo "<div style='background:#e3f2fd;padding:15px;border-radius:5px;margin:10px 0;'>";
                echo "<h4 style='margin:0;color:#1565c0;'>📊 Tablas Encontradas (" . count($tables) . ")</h4>";
                echo "<p style='margin:5px 0;color:#1976d2;'>" . implode(', ', array_slice($tables, 0, 10));
                if (count($tables) > 10) echo " <em>... y " . (count($tables) - 10) . " más</em>";
                echo "</p>";
                echo "</div>";
            } catch (Exception $e) {
                echo "<div style='background:#fff3e0;padding:15px;border-radius:5px;margin:10px 0;'>";
                echo "<h4 style='margin:0;color:#ef6c00;'>⚠️ No se pudieron listar las tablas</h4>";
                echo "<p style='margin:5px 0;color:#f57c00;'>" . $e->getMessage() . "</p>";
                echo "</div>";
            }
            
        } else {
            echo "<div style='background:#ffe6e6;padding:15px;border-radius:5px;margin:10px 0;'>";
            echo "<h4 style='margin:0;color:#c62828;'>❌ Error de Conexión</h4>";
            echo "<table style='width:100%;margin-top:10px;'>";
            echo "<tr><td><strong>Host:</strong></td><td style='color:#d32f2f;'>" . $test['host'] . "</td></tr>";
            echo "<tr><td><strong>Base de Datos:</strong></td><td style='color:#d32f2f;'>" . $test['database'] . "</td></tr>";
            echo "<tr><td><strong>Usuario:</strong></td><td style='color:#d32f2f;'>" . $test['user'] . "</td></tr>";
            echo "<tr><td><strong>Error:</strong></td><td style='color:#d32f2f;'>" . $test['error'] . "</td></tr>";
            echo "</table>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div style='background:#ffe6e6;padding:15px;border-radius:5px;margin:10px 0;'>";
        echo "<h4 style='margin:0;color:#c62828;'>❌ Error Crítico</h4>";
        echo "<p style='margin:5px 0;color:#d32f2f;'>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
    
    echo "<p style='margin-bottom:0;color:#6c757d;text-align:center;'>💡 <em>Accede con ?debug_db o ?test_db para ver esta información</em></p>";
    echo "</div>";
}
?>