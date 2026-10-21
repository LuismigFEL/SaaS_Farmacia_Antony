<?php
require_once __DIR__ . '/../../config/database.php';

class Medicamento {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAll(?string $busqueda = null): array {
        if (!empty($busqueda)) {
            $sql = "SELECT m.*, 
                           COALESCE(SUM(CASE WHEN l.fecha_caducidad >= DATE('now') THEN l.stock_disponible ELSE 0 END), 0) as stock_vigente
                    FROM medicamentos m
                    LEFT JOIN lotes l ON m.id = l.medicamento_id
                    WHERE m.nombre_comercial LIKE :b1 
                       OR m.principio_activo LIKE :b2 
                       OR m.codigo_barras LIKE :b3
                    GROUP BY m.id
                    ORDER BY m.nombre_comercial ASC";
            $stmt = $this->db->prepare($sql);
            $param = "%$busqueda%";
            $stmt->execute([':b1' => $param, ':b2' => $param, ':b3' => $param]);
            return $stmt->fetchAll();
        }

        $sql = "SELECT m.*, 
                       COALESCE(SUM(CASE WHEN l.fecha_caducidad >= DATE('now') THEN l.stock_disponible ELSE 0 END), 0) as stock_vigente
                FROM medicamentos m
                LEFT JOIN lotes l ON m.id = l.medicamento_id
                GROUP BY m.id
                ORDER BY m.nombre_comercial ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM medicamentos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function create(array $data): int {
        $sql = "INSERT INTO medicamentos (codigo_barras, nombre_comercial, principio_activo, concentracion, presentacion, laboratorio, requiere_receta, precio_compra, precio_venta, stock_actual, stock_minimo) 
                VALUES (:codigo_barras, :nombre_comercial, :principio_activo, :concentracion, :presentacion, :laboratorio, :requiere_receta, :precio_compra, :precio_venta, :stock_actual, :stock_minimo)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':codigo_barras' => $data['codigo_barras'],
            ':nombre_comercial' => $data['nombre_comercial'],
            ':principio_activo' => $data['principio_activo'],
            ':concentracion' => $data['concentracion'] ?? '',
            ':presentacion' => $data['presentacion'],
            ':laboratorio' => $data['laboratorio'],
            ':requiere_receta' => isset($data['requiere_receta']) ? 1 : 0,
            ':precio_compra' => $data['precio_compra'],
            ':precio_venta' => $data['precio_venta'],
            ':stock_actual' => $data['stock_actual'] ?? 0,
            ':stock_minimo' => $data['stock_minimo'] ?? 10
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getAlertasStock(): array {
        $sql = "SELECT * FROM medicamentos WHERE stock_actual <= stock_minimo ORDER BY stock_actual ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function getAlertasCaducidad(): array {
        // Alertas: Vencidos o por vencer en menos de 60 días
        $sql = "SELECT l.*, m.nombre_comercial, m.principio_activo, m.presentacion,
                       CAST(julianday(l.fecha_caducidad) - julianday('now') AS INTEGER) as dias_restantes
                FROM lotes l
                JOIN medicamentos m ON l.medicamento_id = m.id
                WHERE l.stock_disponible > 0 AND (l.fecha_caducidad <= date('now', '+60 days'))
                ORDER BY l.fecha_caducidad ASC";
        return $this->db->query($sql)->fetchAll();
    }
}
