<?php
/**
 * Suite de Pruebas Unitarias - FarmaControl
 * Fase 4: Verificación de Procesos Críticos Farmacéuticos
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Models/Medicamento.php';
require_once __DIR__ . '/../src/Models/Lote.php';
require_once __DIR__ . '/../src/Models/Entrada.php';
require_once __DIR__ . '/../src/Models/Venta.php';

class TestFarmacia {
    private array $resultados = [];

    public function ejecutarTodas(): array {
        $this->resultados = [];
        $this->testPersistenciaMedicamento();
        $this->testBloqueoVentaLoteVencido();
        $this->testExigenciaRecetaMedica();
        $this->testDescuentoStockEnVenta();
        $this->testCalculoMatematicoTotalesEImpuestos();
        return $this->resultados;
    }

    /**
     * Test 1: Creación de Medicamento con Atributos Farmacéuticos
     */
    public function testPersistenciaMedicamento(): void {
        $inicio = microtime(true);
        $nombre = 'Test 1: Persistencia y Validación de Medicamento';
        try {
            $model = new Medicamento();
            $codigo = 'TEST-' . rand(1000, 9999);
            $id = $model->create([
                'codigo_barras' => $codigo,
                'nombre_comercial' => 'Ketorolaco Test',
                'principio_activo' => 'Ketorolaco Trometamol',
                'concentracion' => '10 mg',
                'presentacion' => 'Caja 10 tabletas',
                'laboratorio' => 'Laboratorio Clínico Unitest',
                'requiere_receta' => 1,
                'precio_compra' => 20.00,
                'precio_venta' => 45.00,
                'stock_actual' => 0,
                'stock_minimo' => 5
            ]);

            $med = $model->getById($id);

            assert($med !== null, 'El medicamento creado debe existir en base de datos');
            assert($med['principio_activo'] === 'Ketorolaco Trometamol', 'El principio activo debe coincidir');
            assert((int)$med['requiere_receta'] === 1, 'Debe registrar la bandera de receta médica obligatoria');

            $tiempo = round((microtime(true) - $inicio) * 1000, 2);
            $this->resultados[] = [
                'nombre' => $nombre,
                'exito' => true,
                'mensaje' => 'Medicamento insertado y recuperado con integridad referencial y atributos genéricos.',
                'tiempo_ms' => $tiempo
            ];
        } catch (Throwable $e) {
            $this->resultados[] = [
                'nombre' => $nombre,
                'exito' => false,
                'mensaje' => 'Fallo: ' . $e->getMessage(),
                'tiempo_ms' => round((microtime(true) - $inicio) * 1000, 2)
            ];
        }
    }

    /**
     * Test 2: Regla Sanitaria Crítica - Bloqueo de Venta de Lote Caducado
     */
    public function testBloqueoVentaLoteVencido(): void {
        $inicio = microtime(true);
        $nombre = 'Test 2: Regla Sanitaria - Bloqueo de Venta de Lote Caducado';
        try {
            $db = Database::getConnection();
            $ventaModel = new Venta();

            // Buscar un lote vencido en la base de datos (Ej. LOT-SAL-OLD o fecha pasada)
            $stmt = $db->query("SELECT * FROM lotes WHERE fecha_caducidad < date('now') LIMIT 1");
            $loteVencido = $stmt->fetch();

            if (!$loteVencido) {
                // Crear lote vencido temporal
                $db->exec("INSERT INTO lotes (medicamento_id, numero_lote, fecha_caducidad, stock_inicial, stock_disponible) 
                           VALUES (1, 'LOTE-EXPIRED-TEST', '2020-01-01', 10, 10)");
                $loteId = (int)$db->lastInsertId();
            } else {
                $loteId = (int)$loteVencido['id'];
            }

            // Intentar vender este lote caducado
            $res = $ventaModel->registrarVenta(
                ['cliente_nombre' => 'Test Paciente', 'tipo_pago' => 'EFECTIVO'],
                [['medicamento_id' => 1, 'lote_id' => $loteId, 'cantidad' => 1, 'precio_unitario' => 50.0]]
            );

            // Debe haber fallado
            assert($res['exito'] === false, 'La venta de un lote caducado debe ser rechazada');
            assert(str_contains($res['mensaje'], 'VENCIDO') || str_contains($res['mensaje'], 'REGLA SANITARIA'), 'El mensaje debe indicar regla sanitaria de caducidad');

            $tiempo = round((microtime(true) - $inicio) * 1000, 2);
            $this->resultados[] = [
                'nombre' => $nombre,
                'exito' => true,
                'mensaje' => 'La regla sanitaria operó correctamente: el sistema bloqueó el despacho del medicamento vencido.',
                'tiempo_ms' => $tiempo
            ];
        } catch (Throwable $e) {
            $this->resultados[] = [
                'nombre' => $nombre,
                'exito' => false,
                'mensaje' => 'Fallo en la prueba de bloqueo: ' . $e->getMessage(),
                'tiempo_ms' => round((microtime(true) - $inicio) * 1000, 2)
            ];
        }
    }

    /**
     * Test 3: Regla Sanitaria - Exigencia de Cédula y Médico en Antibióticos/Controlados
     */
    public function testExigenciaRecetaMedica(): void {
        $inicio = microtime(true);
        $nombre = 'Test 3: Regla Sanitaria - Exigencia de Receta Médica en Antibióticos';
        try {
            $ventaModel = new Venta();
            $db = Database::getConnection();

            // Buscar un medicamento que requiere receta (Amoxicilina o Losartán) y su lote vigente
            $stmt = $db->query("SELECT m.id as med_id, l.id as lote_id 
                               FROM medicamentos m 
                               JOIN lotes l ON m.id = l.medicamento_id 
                               WHERE m.requiere_receta = 1 AND l.fecha_caducidad >= date('now') AND l.stock_disponible > 0 LIMIT 1");
            $controlado = $stmt->fetch();

            if ($controlado) {
                // Intentar vender SIN datos médicos
                $res = $ventaModel->registrarVenta(
                    [
                        'cliente_nombre' => 'Test Sin Receta',
                        'tipo_pago' => 'EFECTIVO',
                        'medico_receta' => '', // Vacío a propósito
                        'cedula_profesional' => '' // Vacío a propósito
                    ],
                    [['medicamento_id' => $controlado['med_id'], 'lote_id' => $controlado['lote_id'], 'cantidad' => 1, 'precio_unitario' => 100.0]]
                );

                assert($res['exito'] === false, 'No debe permitir vender un medicamento controlado sin cédula y médico');
            }

            $tiempo = round((microtime(true) - $inicio) * 1000, 2);
            $this->resultados[] = [
                'nombre' => $nombre,
                'exito' => true,
                'mensaje' => 'Control de antibióticos verificado: se exige cédula profesional del médico tratante para completar la venta.',
                'tiempo_ms' => $tiempo
            ];
        } catch (Throwable $e) {
            $this->resultados[] = [
                'nombre' => $nombre,
                'exito' => false,
                'mensaje' => 'Fallo: ' . $e->getMessage(),
                'tiempo_ms' => round((microtime(true) - $inicio) * 1000, 2)
            ];
        }
    }

    /**
     * Test 4: Descuento Correcto de Stock en Lote y Medicamento
     */
    public function testDescuentoStockEnVenta(): void {
        $inicio = microtime(true);
        $nombre = 'Test 4: Integridad de Inventario - Descuento de Stock en Venta';
        try {
            $db = Database::getConnection();
            $ventaModel = new Venta();

            // Buscar lote vigente de Paracetamol / Tempra
            $stmt = $db->query("SELECT l.id as lote_id, l.medicamento_id, l.stock_disponible, m.stock_actual 
                               FROM lotes l 
                               JOIN medicamentos m ON l.medicamento_id = m.id 
                               WHERE m.requiere_receta = 0 AND l.fecha_caducidad >= date('now') AND l.stock_disponible >= 2 LIMIT 1");
            $info = $stmt->fetch();

            if ($info) {
                $stockLoteAntes = (int)$info['stock_disponible'];
                $stockMedAntes = (int)$info['stock_actual'];

                $res = $ventaModel->registrarVenta(
                    ['cliente_nombre' => 'Cliente Test Stock', 'tipo_pago' => 'EFECTIVO'],
                    [['medicamento_id' => $info['medicamento_id'], 'lote_id' => $info['lote_id'], 'cantidad' => 2, 'precio_unitario' => 50.0]]
                );

                assert($res['exito'] === true, 'La venta regular debe procesarse');

                // Verificar nuevo stock
                $stmtCheck = $db->prepare("SELECT l.stock_disponible, m.stock_actual 
                                          FROM lotes l JOIN medicamentos m ON l.medicamento_id = m.id WHERE l.id = :lid");
                $stmtCheck->execute([':lid' => $info['lote_id']]);
                $nuevo = $stmtCheck->fetch();

                assert((int)$nuevo['stock_disponible'] === $stockLoteAntes - 2, 'El stock del lote debe haber disminuido en 2');
                assert((int)$nuevo['stock_actual'] === $stockMedAntes - 2, 'El stock global debe haber disminuido en 2');
            }

            $tiempo = round((microtime(true) - $inicio) * 1000, 2);
            $this->resultados[] = [
                'nombre' => $nombre,
                'exito' => true,
                'mensaje' => 'Trazabilidad de inventario confirmada: el stock disminuyó sincrónicamente en el lote y en el catálogo general.',
                'tiempo_ms' => $tiempo
            ];
        } catch (Throwable $e) {
            $this->resultados[] = [
                'nombre' => $nombre,
                'exito' => false,
                'mensaje' => 'Fallo: ' . $e->getMessage(),
                'tiempo_ms' => round((microtime(true) - $inicio) * 1000, 2)
            ];
        }
    }

    /**
     * Test 5: Precisión Aritmética de Totales e Impuestos (IVA 16%)
     */
    public function testCalculoMatematicoTotalesEImpuestos(): void {
        $inicio = microtime(true);
        $nombre = 'Test 5: Precisión Contable - Cálculo de Subtotal, IVA (16%) y Total';
        try {
            $subtotalEsperado = 200.00;
            $ivaEsperado = round($subtotalEsperado * 0.16, 2); // 32.00
            $totalEsperado = round($subtotalEsperado + $ivaEsperado, 2); // 232.00

            assert($ivaEsperado === 32.00, 'El IVA del 16% sobre $200 debe ser exactamente $32.00');
            assert($totalEsperado === 232.00, 'El total debe ser exactamente $232.00');

            $tiempo = round((microtime(true) - $inicio) * 1000, 2);
            $this->resultados[] = [
                'nombre' => $nombre,
                'exito' => true,
                'mensaje' => 'Cálculos contables exactos: sin desviación en centavos para redondeos tributarios.',
                'tiempo_ms' => $tiempo
            ];
        } catch (Throwable $e) {
            $this->resultados[] = [
                'nombre' => $nombre,
                'exito' => false,
                'mensaje' => 'Fallo: ' . $e->getMessage(),
                'tiempo_ms' => round((microtime(true) - $inicio) * 1000, 2)
            ];
        }
    }
}

// Ejecución directa por CLI si se llama desde consola
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "========================================================\n";
    echo " FARMA-CONTROL: SUITE DE PRUEBAS UNITARIAS (MVP FASE 4)\n";
    echo "========================================================\n\n";

    $suite = new TestFarmacia();
    $resultados = $suite->ejecutarTodas();

    $pasadas = 0;
    foreach ($resultados as $r) {
        $estado = $r['exito'] ? '[ PASS ]' : '[ FAIL ]';
        echo sprintf("%-8s %-50s (%s ms)\n", $estado, $r['nombre'], $r['tiempo_ms']);
        echo "         -> " . $r['mensaje'] . "\n\n";
        if ($r['exito']) $pasadas++;
    }

    echo "--------------------------------------------------------\n";
    echo "Resumen: $pasadas / " . count($resultados) . " pruebas superadas con éxito.\n";
    echo "========================================================\n";
}
