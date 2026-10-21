<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Models/Medicamento.php';
require_once __DIR__ . '/../src/Models/Entrada.php';

$medModel = new Medicamento();
$entradaModel = new Entrada();
$db = Database::getConnection();

$mensaje = null;
$tipoMensaje = 'success';

// Procesar Registro de Entrada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'registrar_entrada') {
    try {
        $medicamentoId = (int)$_POST['medicamento_id'];
        $cantidad = (int)$_POST['cantidad'];
        $precioUnitario = (float)$_POST['precio_unitario'];
        $subtotal = $cantidad * $precioUnitario;

        $datosEntrada = [
            'numero_factura' => trim($_POST['numero_factura']),
            'proveedor_id' => (int)$_POST['proveedor_id'],
            'usuario_id' => 1,
            'total_compra' => $subtotal,
            'observaciones' => trim($_POST['observaciones'] ?? '')
        ];

        $items = [
            [
                'medicamento_id' => $medicamentoId,
                'numero_lote' => trim($_POST['numero_lote']),
                'fecha_caducidad' => $_POST['fecha_caducidad'],
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'subtotal' => $subtotal
            ]
        ];

        $entradaId = $entradaModel->registrarEntrada($datosEntrada, $items);
        $mensaje = "Entrada #$entradaId registrada correctamente. Stock y lote actualizados en inventario.";
        $tipoMensaje = 'success';
    } catch (Exception $e) {
        $mensaje = "Error al registrar la entrada: " . $e->getMessage();
        $tipoMensaje = 'error';
    }
}

$proveedores = $entradaModel->getProveedores();
$medicamentos = $medModel->getAll();
$entradasHistorial = $entradaModel->getAll();

$preselectMedId = isset($_GET['med_id']) ? (int)$_GET['med_id'] : null;

$pageTitle = 'Entradas y Abastecimiento de Lotes - FarmaControl';
$activeTab = 'entradas';
require_once __DIR__ . '/layout/header.php';
?>

<!-- Encabezado de Sección -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
        <i class="fa-solid fa-truck-ramp-box text-indigo-600"></i> Entradas de Almacén y Compras
    </h1>
    <p class="text-sm text-slate-500">Recepción de mercancía de laboratorios y alta de lotes con fecha de caducidad.</p>
</div>

<!-- Notificaciones -->
<?php if ($mensaje): ?>
<div class="mb-6 p-4 rounded-xl flex items-center gap-3 <?= $tipoMensaje === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' ?>">
    <i class="fa-solid <?= $tipoMensaje === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-rose-600' ?> text-lg"></i>
    <span class="text-sm font-medium"><?= htmlspecialchars($mensaje) ?></span>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    <!-- Formulario de Entrada -->
    <div class="lg:col-span-1 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm h-fit">
        <h2 class="text-base font-bold text-slate-800 pb-3 border-b border-slate-100 flex items-center gap-2">
            <i class="fa-solid fa-file-invoice text-indigo-600"></i> Registrar Nueva Entrada
        </h2>

        <form method="POST" class="mt-4 space-y-4 text-xs">
            <input type="hidden" name="accion" value="registrar_entrada">

            <div>
                <label class="block font-semibold text-slate-600 mb-1">Distribuidor / Proveedor *</label>
                <select name="proveedor_id" required class="w-full p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Seleccionar Laboratorio/Proveedor --</option>
                    <?php foreach ($proveedores as $prov): ?>
                        <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['razon_social']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block font-semibold text-slate-600 mb-1">No. Factura / Remisión *</label>
                <input type="text" name="numero_factura" required placeholder="FAC-2026-99" class="w-full p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 font-mono">
            </div>

            <div>
                <label class="block font-semibold text-slate-600 mb-1">Medicamento a Abastecer *</label>
                <select name="medicamento_id" id="medSelect" required class="w-full p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Seleccionar Medicamento --</option>
                    <?php foreach ($medicamentos as $med): ?>
                        <option value="<?= $med['id'] ?>" <?= $preselectMedId === (int)$med['id'] ? 'selected' : '' ?> data-compra="<?= $med['precio_compra'] ?>">
                            <?= htmlspecialchars($med['nombre_comercial']) ?> (<?= htmlspecialchars($med['principio_activo']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-600 mb-1">No. de Lote *</label>
                    <input type="text" name="numero_lote" required placeholder="LOT-2026-X" class="w-full p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 font-mono">
                </div>
                <div>
                    <label class="block font-semibold text-slate-600 mb-1">Fecha Caducidad *</label>
                    <input type="date" name="fecha_caducidad" required class="w-full p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-600 mb-1">Cantidad Recibida *</label>
                    <input type="number" name="cantidad" min="1" value="20" required class="w-full p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 font-bold">
                </div>
                <div>
                    <label class="block font-semibold text-slate-600 mb-1">Costo Unitario ($) *</label>
                    <input type="number" step="0.01" name="precio_unitario" id="costoUnitario" required placeholder="0.00" class="w-full p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 font-bold">
                </div>
            </div>

            <div>
                <label class="block font-semibold text-slate-600 mb-1">Observaciones</label>
                <textarea name="observaciones" rows="2" placeholder="Recepción sin daños en embalaje..." class="w-full p-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>

            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-xl shadow-sm transition text-xs flex items-center justify-center gap-2">
                <i class="fa-solid fa-cloud-arrow-up"></i> Registrar Entrada a Inventario
            </button>
        </form>
    </div>

    <!-- Historial de Entradas -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
                <h2 class="font-bold text-slate-800 text-sm">Historial de Entradas / Compras Realizadas</h2>
                <p class="text-xs text-slate-500">Comprobantes y lotes ingresados al sistema</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-lg">
                <?= count($entradasHistorial) ?> Entradas
            </span>
        </div>

        <div class="p-4 flex-grow overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-400 uppercase font-semibold">
                    <tr>
                        <th class="py-3 px-3">No. Factura</th>
                        <th class="py-3 px-3">Proveedor / Laboratorio</th>
                        <th class="py-3 px-3">Registrado Por</th>
                        <th class="py-3 px-3 text-right">Monto Total</th>
                        <th class="py-3 px-3 text-right">Fecha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($entradasHistorial)): ?>
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slate-400">No se han registrado compras aún.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($entradasHistorial as $ent): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 px-3 font-mono font-bold text-indigo-700"><?= htmlspecialchars($ent['numero_factura'] ?: 'S/N') ?></td>
                            <td class="py-3 px-3 font-medium text-slate-800"><?= htmlspecialchars($ent['proveedor_nombre'] ?? 'Distribuidora General') ?></td>
                            <td class="py-3 px-3 text-slate-500"><?= htmlspecialchars($ent['usuario_nombre'] ?? 'Administrador') ?></td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-800">$<?= number_format((float)$ent['total_compra'], 2) ?></td>
                            <td class="py-3 px-3 text-right text-slate-400"><?= htmlspecialchars($ent['fecha_entrada']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Auto-completar costo según el medicamento seleccionado
const medSelect = document.getElementById('medSelect');
const costoInput = document.getElementById('costoUnitario');

function updateCosto() {
    const selectedOption = medSelect.options[medSelect.selectedIndex];
    const compra = selectedOption.getAttribute('data-compra');
    if (compra) {
        costoInput.value = parseFloat(compra).toFixed(2);
    }
}

medSelect.addEventListener('change', updateCosto);
if (medSelect.selectedIndex > 0) {
    updateCosto();
}
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
