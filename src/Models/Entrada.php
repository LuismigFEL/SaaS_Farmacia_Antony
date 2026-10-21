<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/Lote.php';

class Entrada {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAll(): array {
        $sql = "SELECT e.*, p.razon_social as proveedor_nombre, u.nombre as usuario_nombre,
                       (SELECT COUNT(*) FROM detalle_entradas de WHERE de.entrada_id = e.id) as total_items
                FROM entradas e
                LEFT JOIN proveedores p ON e.proveedor_id = p.id
                LEFT JOIN usuarios u ON e.usuario_id = u.id
                ORDER BY e.fecha_entrada DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function getProveedores(): array {
        return $this->db->query("SELECT * FROM proveedores WHERE activo = 1 ORDER BY razon_social ASC")->fetchAll();
    }

    public function registrarEntrada(array $datos, array $items): int {
        $this->db->beginTransaction();
        try {
            $sql = "INSERT INTO entradas (numero_factura, proveedor_id, usuario_id, total_compra, observaciones) 
                    VALUES (:num_fac, :prov_id, :usr_id, :total, :obs)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':num_fac' => $datos['numero_factura'],
                ':prov_id' => $datos['proveedor_id'],
                ':usr_id' => $datos['usuario_id'] ?? 1,
                ':total' => $datos['total_compra'],
                ':obs' => $datos['observaciones'] ?? ''
            ]);
            $entradaId = (int)$this->db->lastInsertId();

            $loteModel = new Lote();

            foreach ($items as $item) {
                // Registrar detalle de entrada
                $sqlDet = "INSERT INTO detalle_entradas (entrada_id, medicamento_id, numero_lote, fecha_caducidad, cantidad, precio_unitario, subtotal)
                           VALUES (:eid, :mid, :nlote, :fcad, :cant, :punit, :subt)";
                $stmtDet = $this->db->prepare($sqlDet);
                $stmtDet->execute([
                    ':eid' => $entradaId,
                    ':mid' => $item['medicamento_id'],
                    ':nlote' => $item['numero_lote'],
                    ':fcad' => $item['fecha_caducidad'],
                    ':cant' => $item['cantidad'],
                    ':punit' => $item['precio_unitario'],
                    ':subt' => $item['subtotal']
                ]);

                // Crear o actualizar lote y stock
                $loteModel->create([
                    'medicamento_id' => $item['medicamento_id'],
                    'numero_lote' => $item['numero_lote'],
                    'fecha_caducidad' => $item['fecha_caducidad'],
                    'cantidad' => $item['cantidad']
                ]);
            }

            $this->db->commit();
            return $entradaId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
