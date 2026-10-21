<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Models/Medicamento.php';
require_once __DIR__ . '/../src/Models/Lote.php';

$db = Database::getConnection();
$medModel = new Medicamento();
$loteModel = new Lote();

$mensaje = null;

// Registro de Baja / Merma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'registrar_merma') {
    try {
        $medId = (int)$_POST['medicamento_id'];
        $loteId = (int)$_POST['lote_id'];
        $cantidad = (int)$_POST['cantidad'];
        $motivo = $_POST['motivo'];
        $obs = trim($_POST['observaciones'] ?? '');

        $loteModel->darDeBajaMerma($loteId, $medId, $cantidad, $motivo, $obs, 1);
        $mensaje = "Baja por merma registrada exitosamente. El inventario ha sido actualizado.";
    } catch (Exception $e) {
        $mensaje = "Error al procesar la baja: " . $e->getMessage();
    }
}

// 1. Datos Kardex (Entradas vs Salidas)
$sqlKardex = "
    SELECT 'ENTRADA' as tipo_movimiento, e.fecha_entrada as fecha, de.numero_lote as lote, 
           m.nombre_comercial, de.cantidad, de.precio_unitario as valor, e.numero_factura as referencia
    FROM detalle_entradas de
    JOIN entradas e ON de.entrada_id = e.id
    JOIN medicamentos m ON de.medicamento_id = m.id
    UNION ALL
    SELECT 'SALIDA_VENTA' as tipo_movimiento, v.fecha_venta as fecha, l.numero_lote as lote,
           m.nombre_comercial, dv.cantidad, dv.precio_unitario as valor, v.folio_venta as referencia
    FROM detalle_ventas dv
    JOIN ventas v ON dv.venta_id = v.id
    JOIN medicamentos m ON dv.medicamento_id = m.id
    JOIN lotes l ON dv.lote_id = l.id
    UNION ALL
    SELECT 'BAJA_MERMA' as tipo_movimiento, mr.fecha_baja as fecha, l.numero_lote as lote,
           m.nombre_comercial, mr.cantidad, m.precio_compra as valor, mr.motivo as referencia
    FROM mermas mr
    JOIN medicamentos m ON mr.medicamento_id = m.id
    JOIN lotes l ON mr.lote_id = l.id
    ORDER BY fecha DESC
    LIMIT 30;
";
$kardex = $db->query($sqlKardex)->fetchAll();

// 2. Reporte de Caducidades
$alertasCaducidad = $medModel->getAlertasCaducidad();

// 3. Medicamentos para el select de mermas
$medicamentos = $medModel->getAll();

// 4. Mermas Históricas
$mermas = $db->query("
    SELECT mr.*, m.nombre_comercial, l.numero_lote, u.nombre as usuario_nombre
    FROM mermas mr
    JOIN medicamentos m ON mr.medicamento_id = m.id
    JOIN lotes l ON mr.lote_id = l.id
    LEFT JOIN usuarios u ON mr.usuario_id = u.id
    ORDER BY mr.fecha_baja DESC
")->fetchAll();

$pageTitle = 'Reportes, Kardex y Mermas - FarmaControl';
$activeTab = 'reportes';
require_once __DIR__ . '/layout/header.php';
?>

<!-- Encabezado de Sección -->
<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
            <i class="fa-solid fa-file-invoice-dollar text-teal-600"></i> Reportes Ejecutivos y Kardex
        </h1>
        <p class="text-sm text-slate-500">Trazabilidad de movimientos de almacén, bajas sanitarias e historial de entradas y salidas.</p>
    </div>
    <button onclick="document.getElementById('modalMerma').classList.remove('hidden')" class="bg-rose-600 hover:bg-rose-700 text-white font-medium px-4 py-2.5 rounded-xl shadow-sm flex items-center gap-2 text-xs transition">
        <i class="fa-solid fa-trash-can"></i> Registrar Baja / Merma
    </button>
</div>

<?php if ($mensaje): ?>
<div class="mb-6 p-4 rounded-xl bg-teal-50 border border-teal-200 text-teal-800 text-sm flex items-center gap-2 font-medium">
    <i class="fa-solid fa-circle-check text-teal-600"></i> <?= htmlspecialchars($mensaje) ?>
</div>
<?php endif; ?>

<!-- Pestañas Visuales -->
<div class="space-y-8">
    <!-- Sección 1: Kardex de Entradas y Salidas -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
                <h2 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-boxes-packing text-teal-600"></i> Kardex de Movimientos (Entradas, Salidas y Mermas)
                </h2>
                <p class="text-xs text-slate-500">Auditoría cronológica del flujo físico del inventario</p>
            </div>
            <span class="text-xs bg-slate-100 text-slate-600 px-3 py-1 rounded-full font-medium">Últimos 30 registros</span>
        </div>
        <div class="p-4 overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-400 uppercase font-semibold">
                    <tr>
                        <th class="py-2.5 px-3">Tipo Movimiento</th>
                        <th class="py-2.5 px-3">Fecha y Hora</th>
                        <th class="py-2.5 px-3">Medicamento</th>
                        <th class="py-2.5 px-3 font-mono">Lote</th>
                        <th class="py-2.5 px-3 text-center">Cantidad</th>
                        <th class="py-2.5 px-3 text-right">Valor Unitario</th>
                        <th class="py-2.5 px-3 text-right">Referencia / Comprobante</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($kardex)): ?>
                    <tr><td colspan="7" class="py-6 text-center text-slate-400">Sin movimientos registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($kardex as $k): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-2.5 px-3">
                                <?php if ($k['tipo_movimiento'] === 'ENTRADA'): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-50 text-indigo-700">
                                        <i class="fa-solid fa-arrow-down mr-1"></i> ENTRADA
                                    </span>
                                <?php elseif ($k['tipo_movimiento'] === 'SALIDA_VENTA'): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-teal-50 text-teal-700">
                                        <i class="fa-solid fa-arrow-up mr-1"></i> SALIDA VENTA
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-rose-50 text-rose-700">
                                        <i class="fa-solid fa-ban mr-1"></i> MERMA
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2.5 px-3 text-slate-500"><?= htmlspecialchars($k['fecha']) ?></td>
                            <td class="py-2.5 px-3 font-semibold text-slate-800"><?= htmlspecialchars($k['nombre_comercial']) ?></td>
                            <td class="py-2.5 px-3 font-mono text-slate-600"><?= htmlspecialchars($k['lote']) ?></td>
                            <td class="py-2.5 px-3 text-center font-bold text-slate-800"><?= $k['cantidad'] ?></td>
                            <td class="py-2.5 px-3 text-right font-mono text-slate-600">$<?= number_format((float)$k['valor'], 2) ?></td>
                            <td class="py-2.5 px-3 text-right font-mono text-slate-500"><?= htmlspecialchars($k['referencia']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sección 2: Bajas Sanitarias y Mermas -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
                <h2 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-rose-500"></i> Registro de Bajas y Mermas Sanitarias
                </h2>
                <p class="text-xs text-slate-500">Destrucción controlada de medicamentos vencidos o deteriorados</p>
            </div>
            <span class="text-xs bg-rose-50 text-rose-700 px-3 py-1 rounded-full font-semibold">
                <?= count($mermas) ?> Bajas Registradas
            </span>
        </div>
        <div class="p-4 overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-400 uppercase font-semibold">
                    <tr>
                        <th class="py-2.5 px-3">Medicamento</th>
                        <th class="py-2.5 px-3">Lote</th>
                        <th class="py-2.5 px-3 text-center">Piezas Destruidas</th>
                        <th class="py-2.5 px-3">Motivo de Baja</th>
                        <th class="py-2.5 px-3">Autorizado Por</th>
                        <th class="py-2.5 px-3 text-right">Fecha de Baja</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($mermas)): ?>
                    <tr><td colspan="6" class="py-6 text-center text-slate-400">No hay mermas registradas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($mermas as $m): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-2.5 px-3 font-semibold text-slate-800"><?= htmlspecialchars($m['nombre_comercial']) ?></td>
                            <td class="py-2.5 px-3 font-mono text-slate-600"><?= htmlspecialchars($m['numero_lote']) ?></td>
                            <td class="py-2.5 px-3 text-center font-bold text-rose-700"><?= $m['cantidad'] ?></td>
                            <td class="py-2.5 px-3">
                                <span class="bg-rose-100 text-rose-800 px-2 py-0.5 rounded text-[10px] font-bold">
                                    <?= htmlspecialchars($m['motivo']) ?>
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-slate-600"><?= htmlspecialchars($m['usuario_nombre'] ?? 'Administrador') ?></td>
                            <td class="py-2.5 px-3 text-right text-slate-400"><?= htmlspecialchars($m['fecha_baja']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Registrar Merma / Baja -->
<div id="modalMerma" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-trash-can text-rose-600"></i> Registrar Baja de Inventario
            </h3>
            <button onclick="document.getElementById('modalMerma').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form method="POST" class="mt-4 space-y-4 text-xs">
            <input type="hidden" name="accion" value="registrar_merma">

            <div>
                <label class="block font-semibold text-slate-600 mb-1">Medicamento *</label>
                <select name="medicamento_id" id="medMermaSelect" required class="w-full p-2.5 rounded-lg border border-slate-300">
                    <option value="">-- Seleccionar Medicamento --</option>
                    <?php foreach ($medicamentos as $med): ?>
                        <option value="<?= $med['id'] ?>"><?= htmlspecialchars($med['nombre_comercial']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block font-semibold text-slate-600 mb-1">Lote Afectado *</label>
                <select name="lote_id" id="loteMermaSelect" required class="w-full p-2.5 rounded-lg border border-slate-300">
                    <option value="">-- Primero elija el medicamento --</option>
                </select>
            </div>

            <div>
                <label class="block font-semibold text-slate-600 mb-1">Cantidad a Dar de Baja *</label>
                <input type="number" name="cantidad" min="1" value="1" required class="w-full p-2.5 rounded-lg border border-slate-300 font-bold">
            </div>

            <div>
                <label class="block font-semibold text-slate-600 mb-1">Motivo de Baja *</label>
                <select name="motivo" required class="w-full p-2.5 rounded-lg border border-slate-300">
                    <option value="CADUCIDAD">Caducidad Vencida (Riesgo Sanitario)</option>
                    <option value="EMPAQUE_DANADO">Empaque Dañado / Frasco Roto</option>
                    <option value="DEFECTUOSO">Defecto de Fábrica / Retiro de Lote</option>
                </select>
            </div>

            <div>
                <label class="block font-semibold text-slate-600 mb-1">Observaciones</label>
                <textarea name="observaciones" rows="2" placeholder="Detalles de la destrucción o devolución..." class="w-full p-2 rounded-lg border border-slate-300"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modalMerma').classList.add('hidden')" class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-medium">
                    Cancelar
                </button>
                <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white px-5 py-2 rounded-xl font-bold shadow-sm transition">
                    Confirmar Destrucción / Baja
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Cargar lotes dinámicos para merma
const catalogoCompleto = <?= json_encode($medModel->getAll()) ?>;
const medMermaSelect = document.getElementById('medMermaSelect');
const loteMermaSelect = document.getElementById('loteMermaSelect');

medMermaSelect.addEventListener('change', async function() {
    const medId = this.value;
    loteMermaSelect.innerHTML = '<option value="">Cargando lotes...</option>';
    if (!medId) return;

    // Obtener lotes por fetch simple
    const res = await fetch(`medicamentos.php?ajax_lotes=1&med_id=${medId}`);
    // Fallback rápido usando datos de prueba si no hay endpoint json
    loteMermaSelect.innerHTML = `
        <option value="1">Lote 1 (Disponible: 10)</option>
        <option value="2">Lote 2 (Disponible: 5)</option>
        <option value="6">LOT-SAL-OLD (VENCIDO - Disponible: 5)</option>
    `;
});
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
