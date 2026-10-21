<?php
require_once __DIR__ . '/../../config/database.php';

class Lote {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getByMedicamento(int $medicamentoId, bool $soloDisponibles = true): array {
        $sql = "SELECT * FROM lotes WHERE medicamento_id = :mid";
        if ($soloDisponibles) {
            $sql .= " AND stock_disponible > 0";
        }
        // Orden FEFO (First Expired, First Out)
        $sql .= " ORDER BY fecha_caducidad ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':mid' => $medicamentoId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $sql = "INSERT INTO lotes (medicamento_id, numero_lote, fecha_fabricacion, fecha_caducidad, stock_inicial, stock_disponible, estado) 
                VALUES (:medicamento_id, :numero_lote, :fecha_fabricacion, :fecha_caducidad, :stock_inicial, :stock_disponible, :estado)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':medicamento_id' => $data['medicamento_id'],
            ':numero_lote' => $data['numero_lote'],
            ':fecha_fabricacion' => $data['fecha_fabricacion'] ?? date('Y-m-d'),
            ':fecha_caducidad' => $data['fecha_caducidad'],
            ':stock_inicial' => $data['cantidad'],
            ':stock_disponible' => $data['cantidad'],
            ':estado' => 'DISPONIBLE'
        ]);

        // Actualizar stock total del medicamento
        $updateMed = $this->db->prepare("UPDATE medicamentos SET stock_actual = stock_actual + :cant WHERE id = :mid");
        $updateMed->execute([':cant' => $data['cantidad'], ':mid' => $data['medicamento_id']]);

        return (int)$this->db->lastInsertId();
    }

    public function registrarMerma(int $medicamentoId, int $loteId, int $cantidad, string $motivo, string $obs = '', int $usuarioId = 1): bool {
        $loteStmt = $this->db->prepare("SELECT stock_disponible FROM lotes WHERE id = :id");
        $loteStmt->execute([':id' => $loteId]);
        $lote = $loteStmt->fetch();

        if (!$lote || $lote['stock_disponible'] < $cantidad) {
            throw new Exception("Cantidad a dar de baja excede el stock disponible del lote.");
        }

        // Registrar merma
        $mermaStmt = $this->db->prepare("INSERT INTO mermas (medicamento_id, lote_id, cantidad, motivo, usuario_id, observaciones) 
                                         VALUES (:mid, :lid, :cant, :motivo, :uid, :obs)");
        $mermaStmt->execute([
            ':mid' => $medicamentoId,
            ':lid' => $loteId,
            ':cant' => $cantidad,
            ':motivo' => $motivo,
            ':uid' => $usuarioId,
            ':obs' => $obs
        ]);

        // Descontar del lote
        $updLote = $this->db->prepare("UPDATE lotes SET stock_disponible = stock_disponible - :cant WHERE id = :id");
        $updLote->execute([':cant' => $cantidad, ':id' => $loteId]);

        // Descontar del medicamento
        $updMed = $this->db->prepare("UPDATE medicamentos SET stock_actual = stock_actual - :cant WHERE id = :id");
        $updMed->execute([':cant' => $cantidad, ':id' => $medicamentoId]);

        return true;
    }
}
