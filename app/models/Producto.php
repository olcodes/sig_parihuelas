<?php
class Producto extends Model {
    public function createProducto($codigo, $producto, $unidad, $abreviatura = '') {
        // Forzar mayúsculas antes de guardar
        $codigo = mb_strtoupper($codigo, 'UTF-8');
        $producto = mb_strtoupper($producto, 'UTF-8');
        $unidad = mb_strtoupper($unidad, 'UTF-8');
        $abreviatura = mb_strtoupper($abreviatura ?? '', 'UTF-8');
        $stmt = $this->db->prepare("INSERT INTO productos (Codigo, Producto, UnidadMedida, Abreviatura) VALUES (:codigo, :producto, :unidad, :abreviatura)");
        return $stmt->execute(['codigo' => $codigo, 'producto' => $producto, 'unidad' => $unidad, 'abreviatura' => $abreviatura]);
    }

    public function updateProducto($id, $codigo, $producto, $unidad, $abreviatura = '') {
        // Forzar mayúsculas antes de actualizar
        $codigo = mb_strtoupper($codigo, 'UTF-8');
        $producto = mb_strtoupper($producto, 'UTF-8');
        $unidad = mb_strtoupper($unidad, 'UTF-8');
        $abreviatura = mb_strtoupper($abreviatura ?? '', 'UTF-8');
        $stmt = $this->db->prepare("UPDATE productos SET Codigo = :codigo, Producto = :producto, UnidadMedida = :unidad, Abreviatura = :abreviatura WHERE Id = :id");
        return $stmt->execute(['codigo' => $codigo, 'producto' => $producto, 'unidad' => $unidad, 'abreviatura' => $abreviatura, 'id' => $id]);
    }

    public function deleteProducto($id) {
        $stmt = $this->db->prepare("DELETE FROM productos WHERE Id = :id");
        return $stmt->execute(['id' => $id]);
    }
    protected $table = 'productos';
    protected $primaryKey = 'Id';


    public function getAll($search = '', $limit = 10, $offset = 0) {
        $sql = "SELECT * FROM productos ";
        if ($search) {
            // Usar dos placeholders diferentes para evitar problemas en drivers PDO
            $sql .= "WHERE Codigo LIKE :search1 OR Producto LIKE :search2 ";
        }
        $sql .= "ORDER BY Id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        if ($search) {
            $stmt->bindValue(':search1', "%$search%", PDO::PARAM_STR);
            $stmt->bindValue(':search2', "%$search%", PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll($search = '') {
        $sql = "SELECT COUNT(*) FROM productos ";
        if ($search) {
            $sql .= "WHERE Codigo LIKE :search1 OR Producto LIKE :search2 ";
        }
        $stmt = $this->db->prepare($sql);
        if ($search) {
            $stmt->bindValue(':search1', "%$search%", PDO::PARAM_STR);
            $stmt->bindValue(':search2', "%$search%", PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function findByCodigo($codigo) {
        $stmt = $this->db->prepare("SELECT * FROM productos WHERE Codigo = :codigo");
        $stmt->execute(['codigo' => $codigo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si existe un producto con la misma descripción (case-insensitive).
     * Si se pasa excludeId, se excluye ese registro (útil en actualizaciones).
     */
    public function existsByProducto($producto, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM productos WHERE UPPER(Producto) = UPPER(:producto)";
        if ($excludeId) $sql .= " AND Id != :excludeId";
        $stmt = $this->db->prepare($sql);
        $params = ['producto' => $producto];
        if ($excludeId) $params['excludeId'] = $excludeId;
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Verifica si existe un producto con la misma combinación Código + Descripción.
     * Si se pasa excludeId, se excluye ese registro.
     */
    public function existsByCodigoAndProducto($codigo, $producto, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM productos WHERE UPPER(Codigo) = UPPER(:codigo) AND UPPER(Producto) = UPPER(:producto)";
        if ($excludeId) $sql .= " AND Id != :excludeId";
        $stmt = $this->db->prepare($sql);
        $params = ['codigo' => $codigo, 'producto' => $producto];
        if ($excludeId) $params['excludeId'] = $excludeId;
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM productos WHERE Id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function __construct() {
        parent::__construct();
    }

    public function getAllForSelect() {
        // Incluir UnidadMedida para que los selectores puedan proveer la unidad directamente
        $stmt = $this->db->prepare("SELECT Id, Codigo, Producto, UnidadMedida FROM productos ORDER BY Producto");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verificar si el producto tiene movimientos registrados
     * @param int $id ID del producto
     * @return bool true si tiene movimientos, false si no
     */
    public function hasMovements($id)
    {
        // Obtener el código del producto para buscar en las tablas de movimientos
        $producto = $this->find($id);
        if (!$producto) {
            return false;
        }
        $codigo = $producto['Codigo'];
        
        $db = Database::getInstance()->getConnection();
        
        // Verificar en despachos internos productos
        $stmt = $db->prepare('SELECT COUNT(*) FROM despachos_internos_productos WHERE CodigoProducto = ?');
        $stmt->execute([$codigo]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        
        // Verificar en despachos externos productos
        $stmt = $db->prepare('SELECT COUNT(*) FROM despachos_externos_productos WHERE CodigoProducto = ?');
        $stmt->execute([$codigo]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        
        // Verificar en recepciones internas productos
        $stmt = $db->prepare('SELECT COUNT(*) FROM recepciones_internas_productos WHERE CodigoProducto = ?');
        $stmt->execute([$codigo]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        
        // Verificar en recepciones externas productos
        $stmt = $db->prepare('SELECT COUNT(*) FROM recepciones_externas_productos WHERE CodigoProducto = ?');
        $stmt->execute([$codigo]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        
        return false;
    }
}
