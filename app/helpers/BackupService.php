<?php
/**
 * BackupService - Motor de respaldo de base de datos
 *
 * Genera volcados SQL mediante PDO puro (sin mysqldump).
 * Compatible con Linux, WAMP Windows y hosting compartido.
 */
class BackupService
{
    // Cuántos archivos máximo conservar por tipo
    const RETENTION = [
        'daily'    => 30,
        'monthly'  => 12,
        'critical' => 120,  // 30 días × 4 backups/día
    ];

    private $backupBasePath;
    private $db;
    private $dbName;

    public function __construct()
    {
        $this->backupBasePath = ROOT . '/storage/backups';

        // Database ya está cargada por index.php; solo obtenemos la instancia
        $ref = new ReflectionClass('Database');

        // Leer dbName desde la propiedad privada
        $prop = $ref->getProperty('dbName');
        $prop->setAccessible(true);
        $inst = Database::getInstance();
        $this->dbName = $prop->getValue($inst);
        $this->db     = $inst->getConnection();
    }

    // ---------------------------------------------------------------
    //  API PÚBLICA
    // ---------------------------------------------------------------

    /**
     * Genera un backup del tipo especificado y aplica retención.
     *
     * @param  string $tipo  'daily' | 'monthly' | 'critical'
     * @return array  ['success'=>bool, 'filename'=>string, 'size'=>int, 'error'=>string]
     */
    public function generar(string $tipo): array
    {
        if (!isset(self::RETENTION[$tipo])) {
            return ['success' => false, 'error' => "Tipo de backup inválido: {$tipo}"];
        }

        $dir = $this->backupBasePath . '/' . $tipo;
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        $filename = sprintf(
            'backup_%s_%s_%s.sql.gz',
            $this->dbName,
            $tipo,
            date('Ymd_His')
        );
        $filepath = $dir . '/' . $filename;

        try {
            $sql = $this->dumpDatabase();

            // Comprimir con gzip
            $gz = gzopen($filepath, 'wb9');
            if (!$gz) {
                throw new RuntimeException("No se pudo crear el archivo: {$filepath}");
            }
            gzwrite($gz, $sql);
            gzclose($gz);

            $size = filesize($filepath);

            // Aplicar retención: borrar los más antiguos si se excede el límite
            $this->aplicarRetencion($dir, self::RETENTION[$tipo]);

            return ['success' => true, 'filename' => $filename, 'size' => $size, 'error' => ''];

        } catch (Throwable $e) {
            // Limpiar archivo parcial si existe
            if (file_exists($filepath)) {
                @unlink($filepath);
            }
            return ['success' => false, 'filename' => '', 'size' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Devuelve la lista de backups de un tipo ordenados de más reciente a más antiguo.
     *
     * @param  string $tipo
     * @return array  [['filename', 'size', 'created_at', 'tipo'], ...]
     */
    public function listar(string $tipo): array
    {
        $dir = $this->backupBasePath . '/' . $tipo;
        if (!is_dir($dir)) {
            return [];
        }

        $files = glob($dir . '/*.sql.gz') ?: [];

        $result = array_map(function ($path) use ($tipo) {
            return [
                'filename'   => basename($path),
                'size'       => filesize($path),
                'created_at' => filemtime($path),
                'tipo'       => $tipo,
            ];
        }, $files);

        // Ordenar: más reciente primero
        usort($result, fn($a, $b) => $b['created_at'] - $a['created_at']);

        return $result;
    }

    /**
     * Elimina un archivo de backup validando que esté dentro de storage/backups/.
     *
     * @param  string $tipo
     * @param  string $filename  Solo nombre de archivo (sin path)
     * @return bool
     */
    public function eliminar(string $tipo, string $filename): bool
    {
        if (!$this->esNombreSeguro($filename)) {
            return false;
        }
        $path = $this->backupBasePath . '/' . $tipo . '/' . $filename;
        if (!file_exists($path)) {
            return false;
        }
        return unlink($path);
    }

    /**
     * Devuelve la ruta absoluta segura de un backup para descarga.
     * Retorna null si no existe o el nombre es inválido.
     *
     * @param  string $tipo
     * @param  string $filename
     * @return string|null
     */
    public function rutaDescarga(string $tipo, string $filename): ?string
    {
        if (!$this->esNombreSeguro($filename)) {
            return null;
        }
        $path = $this->backupBasePath . '/' . $tipo . '/' . $filename;
        return file_exists($path) ? $path : null;
    }

    // ---------------------------------------------------------------
    //  LÓGICA INTERNA
    // ---------------------------------------------------------------

    /**
     * Genera el volcado SQL completo de la BD.
     */
    private function dumpDatabase(): string
    {
        $output  = "-- Backup generado por Lavoro-ERP\n";
        $output .= "-- Base de datos: {$this->dbName}\n";
        $output .= "-- Fecha: " . date('Y-m-d H:i:s') . " (Hora Perú UTC-5)\n";
        $output .= "-- ============================================================\n\n";
        $output .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $output .= "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n";
        $output .= "SET NAMES utf8mb4;\n\n";

        $tables = $this->db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            // Estructura
            $createStmt = $this->db->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
            $createSQL  = array_values($createStmt)[1]; // segunda columna = CREATE TABLE ...

            $output .= "-- ----------------------------------------------------------\n";
            $output .= "-- Tabla: `{$table}`\n";
            $output .= "-- ----------------------------------------------------------\n";
            $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $output .= $createSQL . ";\n\n";

            // Datos en lotes de 500 filas para no agotar memoria
            $count = $this->db->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
            if ($count > 0) {
                $batchSize = 500;
                for ($offset = 0; $offset < $count; $offset += $batchSize) {
                    $rows = $this->db->query(
                        "SELECT * FROM `{$table}` LIMIT {$batchSize} OFFSET {$offset}"
                    )->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($rows)) {
                        break;
                    }

                    $columns = '`' . implode('`, `', array_keys($rows[0])) . '`';
                    $output .= "INSERT INTO `{$table}` ({$columns}) VALUES\n";

                    $valueLines = [];
                    foreach ($rows as $row) {
                        $vals = array_map(function ($v) {
                            if ($v === null) {
                                return 'NULL';
                            }
                            return "'" . addslashes((string)$v) . "'";
                        }, $row);
                        $valueLines[] = '(' . implode(', ', $vals) . ')';
                    }
                    $output .= implode(",\n", $valueLines) . ";\n\n";
                }
            }
        }

        $output .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return $output;
    }

    /**
     * Mantiene solo los $maxFiles más recientes en el directorio.
     */
    private function aplicarRetencion(string $dir, int $maxFiles): void
    {
        $files = glob($dir . '/*.sql.gz') ?: [];
        if (count($files) <= $maxFiles) {
            return;
        }

        // Ordenar por fecha de modificación: más viejo primero
        usort($files, fn($a, $b) => filemtime($a) - filemtime($b));

        $toDelete = array_slice($files, 0, count($files) - $maxFiles);
        foreach ($toDelete as $f) {
            @unlink($f);
        }
    }

    /**
     * Valida que el nombre de archivo sea seguro (evita path traversal).
     */
    private function esNombreSeguro(string $filename): bool
    {
        // Solo permite nombre de archivo sin separadores de directorio
        return $filename !== ''
            && strpos($filename, '/') === false
            && strpos($filename, '\\') === false
            && strpos($filename, '..') === false
            && preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename) === 1;
    }

    /**
     * Formatea bytes a formato legible.
     */
    public static function formatSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
