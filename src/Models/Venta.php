<?php
require_once __DIR__ . '/../../config/database.php';

class Venta {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAll(): array {
        $sql = "SELECT v.*, u.nombre as cajero_nombre,
                       (SELECT COUNT(*) FROM detalle_ventas dv WHERE dv.venta_id = v.id) as total_items
                FROM ventas v
                LEFT JOIN usuarios u ON v.usuario_id = u.id
                ORDER BY v.fecha_venta DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function getDetalle(int $ventaId): array {
        $sql = "SELECT dv.*, m.nombre_comercial, m.principio_activo, m.presentacion, l.numero_lote, l.fecha_caducidad
                FROM detalle_ventas dv
                JOIN medicamentos m ON dv.medicamento_id = m.id
                JOIN lotes l ON dv.lote_id = l.id
                WHERE dv.venta_id = :vid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':vid' => $ventaId]);
        return $stmt->fetchAll();
    }

    public function registrarVenta(array $datosVenta, array $items): array {
        $this->db->beginTransaction();

        try {
            // 1. Validar reglas de negocio farmacéuticas
            $subtotalCalculado = 0.0;
            $hayControlado = false;

            foreach ($items as $item) {
                // Verificar lote y caducidad
                $stmtLote = $this->db->prepare("SELECT l.*, m.nombre_comercial, m.requiere_receta, m.precio_venta 
                                                FROM lotes l 
                                                JOIN medicamentos m ON l.medicamento_id = m.id 
                                                WHERE l.id = :lid");
                $stmtLote->execute([':lid' => $item['lote_id']]);
                $lote = $stmtLote->fetch();

                if (!$lote) {
                    throw new Exception("Lote ID {$item['lote_id']} no existe en el sistema.");
                }

                // Regla Crítica 1: Lote caducado NO se puede vender
                $hoy = date('Y-m-d');
                if ($lote['fecha_caducidad'] < $hoy) {
                    throw new Exception("REGLA SANITARIA: El lote '{$lote['numero_lote']}' de {$lote['nombre_comercial']} está VENCIDO (Caducó: {$lote['fecha_caducidad']}) y no puede venderse.");
                }

                // Regla Crítica 2: Stock suficiente en el lote
                if ($lote['stock_disponible'] < $item['cantidad']) {
                    throw new Exception("Stock insuficiente en el lote '{$lote['numero_lote']}' de {$lote['nombre_comercial']}. Disponible: {$lote['stock_disponible']}, Solicitado: {$item['cantidad']}.");
                }

                if ($lote['requiere_receta'] == 1) {
                    $hayControlado = true;
                }

                $subtotalCalculado += ($item['precio_unitario'] * $item['cantidad']);
            }

            // Regla Crítica 3: Si requiere receta, exigir cédula y médico
            if ($hayControlado && (empty($datosVenta['medico_receta']) || empty($datosVenta['cedula_profesional']))) {
                throw new Exception("REGLA SANITARIA: La venta incluye antibióticos o medicamentos controlados. Es obligatorio registrar el Médico y su Cédula Profesional.");
            }

            $ivaCalculado = round($subtotalCalculado * 0.16, 2);
            $totalCalculado = round($subtotalCalculado + $ivaCalculado, 2);

            $folio = 'VTA-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

            // 2. Insertar cabecera de venta
            $sqlVenta = "INSERT INTO ventas (folio_venta, usuario_id, cliente_nombre, tipo_pago, requirio_receta, medico_receta, cedula_profesional, subtotal, iva, total)
                         VALUES (:folio, :uid, :cliente, :tpago, :receta, :medico, :cedula, :subt, :iva, :total)";
            $stmtV = $this->db->prepare($sqlVenta);
            $stmtV->execute([
                ':folio' => $folio,
                ':uid' => $datosVenta['usuario_id'] ?? 2,
                ':cliente' => $datosVenta['cliente_nombre'] ?: 'Público General',
                ':tpago' => $datosVenta['tipo_pago'] ?: 'EFECTIVO',
                ':receta' => $hayControlado ? 1 : 0,
                ':medico' => $hayControlado ? $datosVenta['medico_receta'] : null,
                ':cedula' => $hayControlado ? $datosVenta['cedula_profesional'] : null,
                ':subt' => $subtotalCalculado,
                ':iva' => $ivaCalculado,
                ':total' => $totalCalculado
            ]);
            $ventaId = (int)$this->db->lastInsertId();

            // 3. Insertar detalle y descontar inventarios
            foreach ($items as $item) {
                $subtItem = $item['precio_unitario'] * $item['cantidad'];
                $sqlDet = "INSERT INTO detalle_ventas (venta_id, medicamento_id, lote_id, cantidad, precio_unitario, subtotal)
                           VALUES (:vid, :mid, :lid, :cant, :punit, :subt)";
                $stmtDet = $this->db->prepare($sqlDet);
                $stmtDet->execute([
                    ':vid' => $ventaId,
                    ':mid' => $item['medicamento_id'],
                    ':lid' => $item['lote_id'],
                    ':cant' => $item['cantidad'],
                    ':punit' => $item['precio_unitario'],
                    ':subt' => $subtItem
                ]);

                // Descontar del lote
                $updLote = $this->db->prepare("UPDATE lotes SET stock_disponible = stock_disponible - :cant WHERE id = :lid");
                $updLote->execute([':cant' => $item['cantidad'], ':lid' => $item['lote_id']]);

                // Descontar del stock global del medicamento
                $updMed = $this->db->prepare("UPDATE medicamentos SET stock_actual = stock_actual - :cant WHERE id = :mid");
                $updMed->execute([':cant' => $item['cantidad'], ':mid' => $item['medicamento_id']]);
            }

            $this->db->commit();
            return [
                'exito' => true,
                'venta_id' => $ventaId,
                'folio' => $folio,
                'total' => $totalCalculado,
                'mensaje' => 'Venta procesada y stock actualizado correctamente.'
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'exito' => false,
                'mensaje' => $e->getMessage()
            ];
        }
    }
}
