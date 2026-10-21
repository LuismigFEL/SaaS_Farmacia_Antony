<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Models/Medicamento.php';
require_once __DIR__ . '/../src/Models/Lote.php';

$medModel = new Medicamento();
$loteModel = new Lote();
$mensaje = null;
$tipoMensaje = 'success';

// Manejo de Creación de Medicamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    try {
        $nuevoId = $medModel->create([
            'codigo_barras' => trim($_POST['codigo_barras']),
            'nombre_comercial' => trim($_POST['nombre_comercial']),
            'principio_activo' => trim($_POST['principio_activo']),
            'concentracion' => trim($_POST['concentracion']),
            'presentacion' => trim($_POST['presentacion']),
            'laboratorio' => trim($_POST['laboratorio']),
            'requiere_receta' => isset($_POST['requiere_receta']) ? 1 : 0,
            'precio_compra' => (float)$_POST['precio_compra'],
            'precio_venta' => (float)$_POST['precio_venta'],
            'stock_actual' => (int)($_POST['stock_actual'] ?? 0),
            'stock_minimo' => (int)($_POST['stock_minimo'] ?? 10)
        ]);
        $mensaje = "Medicamento '{$_POST['nombre_comercial']}' registrado con éxito en el catálogo.";
        $tipoMensaje = 'success';
    } catch (Exception $e) {
        $mensaje = "Error al registrar medicamento: " . $e->getMessage();
        $tipoMensaje = 'error';
    }
}

// Búsqueda
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : null;
$medicamentos = $medModel->getAll($busqueda);

$pageTitle = 'Catálogo de Medicamentos - FarmaControl';
$activeTab = 'medicamentos';
require_once __DIR__ . '/layout/header.php';
?>

<!-- Encabezado de Sección -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
            <i class="fa-solid fa-pills text-teal-600"></i> Catálogo Farmacéutico
        </h1>
        <p class="text-sm text-slate-500">Gestión de sustancias activas, nombres comerciales, laboratorios y requisitos de receta.</p>
    </div>
    <button onclick="document.getElementById('modalNuevo').classList.remove('hidden')" class="bg-teal-600 hover:bg-teal-700 text-white font-medium px-4 py-2.5 rounded-xl shadow-sm flex items-center justify-center gap-2 text-sm transition">
        <i class="fa-solid fa-plus"></i> Registrar Medicamento
    </button>
</div>

<!-- Alertas / Mensajes -->
<?php if ($mensaje): ?>
<div class="mb-6 p-4 rounded-xl flex items-center gap-3 <?= $tipoMensaje === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' ?>">
    <i class="fa-solid <?= $tipoMensaje === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-rose-600' ?> text-lg"></i>
    <span class="text-sm font-medium"><?= htmlspecialchars($mensaje) ?></span>
</div>
<?php endif; ?>

<!-- Barra de Búsqueda y Filtros -->
<div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm mb-6">
    <form method="GET" class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-grow">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>
            <input type="text" name="q" value="<?= htmlspecialchars($busqueda ?? '') ?>" 
                   placeholder="Buscar por Nombre Comercial (ej. Tempra) o Principio Activo (ej. Paracetamol)..." 
                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500 text-sm">
        </div>
        <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-5 py-2.5 rounded-xl text-sm font-medium transition flex items-center justify-center gap-2">
            Filtrar
        </button>
        <?php if (!empty($busqueda)): ?>
        <a href="medicamentos.php" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2.5 rounded-xl text-sm font-medium transition flex items-center justify-center">
            Limpiar
        </a>
        <?php endif; ?>
    </form>
</div>

<!-- Listado de Medicamentos -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-slate-50 text-slate-500 uppercase border-b border-slate-100 font-semibold">
                <tr>
                    <th class="py-3.5 px-4">Código</th>
                    <th class="py-3.5 px-4">Medicamento / Sustancia</th>
                    <th class="py-3.5 px-4">Presentación</th>
                    <th class="py-3.5 px-4">Laboratorio</th>
                    <th class="py-3.5 px-4 text-center">Tipo Regulación</th>
                    <th class="py-3.5 px-4 text-right">P. Compra</th>
                    <th class="py-3.5 px-4 text-right">P. Venta</th>
                    <th class="py-3.5 px-4 text-center">Stock Total</th>
                    <th class="py-3.5 px-4 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($medicamentos)): ?>
                <tr>
                    <td colspan="9" class="py-8 text-center text-slate-400">
                        <i class="fa-solid fa-box-open text-3xl mb-2"></i>
                        <p>No se encontraron medicamentos en el catálogo.</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($medicamentos as $med): 
                        $lotes = $loteModel->getByMedicamento((int)$med['id'], false);
                    ?>
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3.5 px-4 font-mono font-medium text-slate-500"><?= htmlspecialchars($med['codigo_barras']) ?></td>
                        <td class="py-3.5 px-4">
                            <span class="font-bold text-slate-800 text-sm block"><?= htmlspecialchars($med['nombre_comercial']) ?></span>
                            <span class="text-teal-700 font-medium text-xs flex items-center gap-1">
                                <i class="fa-solid fa-flask text-[10px]"></i> <?= htmlspecialchars($med['principio_activo']) ?> (<?= htmlspecialchars($med['concentracion']) ?>)
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-slate-600"><?= htmlspecialchars($med['presentacion']) ?></td>
                        <td class="py-3.5 px-4 text-slate-700 font-medium"><?= htmlspecialchars($med['laboratorio']) ?></td>
                        <td class="py-3.5 px-4 text-center">
                            <?php if ($med['requiere_receta']): ?>
                                <span class="badge-rx px-2.5 py-1 rounded-full text-[11px] font-bold inline-flex items-center gap-1">
                                    <i class="fa-solid fa-prescription"></i> Requiere Receta (Rx)
                                </span>
                            <?php else: ?>
                                <span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full text-[11px] font-medium">
                                    Venta Libre (OTC)
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono text-slate-500">$<?= number_format((float)$med['precio_compra'], 2) ?></td>
                        <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-800">$<?= number_format((float)$med['precio_venta'], 2) ?></td>
                        <td class="py-3.5 px-4 text-center">
                            <span class="font-bold text-sm <?= $med['stock_actual'] <= $med['stock_minimo'] ? 'text-rose-600 bg-rose-50 px-2 py-0.5 rounded-lg border border-rose-200' : 'text-slate-700' ?>">
                                <?= $med['stock_actual'] ?>
                            </span>
                            <span class="block text-[10px] text-slate-400">Mín: <?= $med['stock_minimo'] ?></span>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <button onclick="verLotes(<?= htmlspecialchars(json_encode([
                                'nombre' => $med['nombre_comercial'],
                                'lotes' => $lotes
                            ])) ?>)" class="text-teal-600 hover:text-teal-800 p-1.5 rounded-lg hover:bg-teal-50 transition" title="Ver Lotes y Caducidades">
                                <i class="fa-solid fa-boxes-stacked text-base"></i>
                            </button>
                            <a href="entradas.php?med_id=<?= $med['id'] ?>" class="text-indigo-600 hover:text-indigo-800 p-1.5 rounded-lg hover:bg-indigo-50 transition ml-1" title="Registrar Entrada/Compra">
                                <i class="fa-solid fa-cart-plus text-base"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para Registrar Nuevo Medicamento -->
<div id="modalNuevo" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-capsules text-teal-600"></i> Nuevo Medicamento
            </h3>
            <button onclick="document.getElementById('modalNuevo').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form method="POST" class="mt-4 space-y-4">
            <input type="hidden" name="accion" value="crear">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Código de Barras *</label>
                    <input type="text" name="codigo_barras" required placeholder="750123456789" class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Nombre Comercial *</label>
                    <input type="text" name="nombre_comercial" required placeholder="Ej. Tafil, Paracetamol" class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Principio Activo (Genérico) *</label>
                    <input type="text" name="principio_activo" required placeholder="Ej. Alprazolam, Paracetamol" class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Concentración *</label>
                    <input type="text" name="concentracion" required placeholder="Ej. 500 mg, 10 mg/ml" class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Presentación Farmacéutica *</label>
                    <input type="text" name="presentacion" required placeholder="Ej. Caja con 20 tabletas, Jarabe 120ml" class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Laboratorio Fabricante *</label>
                    <input type="text" name="laboratorio" required placeholder="Ej. Bayer, Pfizer, Liomont" class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Precio Compra ($) *</label>
                    <input type="number" step="0.01" name="precio_compra" required placeholder="35.00" class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Precio Venta Público ($) *</label>
                    <input type="number" step="0.01" name="precio_venta" required placeholder="65.00" class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Stock Mínimo (Alerta)</label>
                    <input type="number" name="stock_minimo" value="10" class="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500">
                </div>
                <div class="flex items-center pt-5">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="requiere_receta" value="1" class="w-4 h-4 text-teal-600 rounded border-slate-300 focus:ring-teal-500">
                        <span class="text-xs font-bold text-rose-700">¿Requiere Receta Médica (Rx)?</span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modalNuevo').classList.add('hidden')" class="px-4 py-2 rounded-xl text-slate-600 text-xs font-medium hover:bg-slate-100">
                    Cancelar
                </button>
                <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white px-5 py-2 rounded-xl text-xs font-semibold shadow-sm transition">
                    Guardar Medicamento
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para Ver Lotes del Medicamento -->
<div id="modalLotes" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 id="lotesTitulo" class="text-base font-bold text-slate-800">Lotes Registrados</h3>
            <button onclick="document.getElementById('modalLotes').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <div id="lotesContenido" class="mt-4">
            <!-- Inyectado vía JavaScript -->
        </div>
    </div>
</div>

<script>
function verLotes(data) {
    document.getElementById('lotesTitulo').innerText = 'Lotes de: ' + data.nombre;
    const cont = document.getElementById('lotesContenido');
    if (!data.lotes || data.lotes.length === 0) {
        cont.innerHTML = '<p class="text-slate-400 text-xs py-4 text-center">No hay lotes registrados para este medicamento. Abastece mediante una Entrada.</p>';
    } else {
        let html = '<table class="w-full text-left text-xs text-slate-600"><thead class="bg-slate-50 uppercase text-slate-400"><tr><th class="py-2 px-2">Lote</th><th class="py-2 px-2">Caducidad</th><th class="py-2 px-2 text-center">Disponible</th><th class="py-2 px-2 text-right">Estado</th></tr></thead><tbody class="divide-y divide-slate-100">';
        data.lotes.forEach(l => {
            let color = l.estado === 'VENCIDO' ? 'text-rose-600 font-bold' : (l.estado === 'POR_VENCER' ? 'text-amber-600 font-semibold' : 'text-emerald-600');
            html += `<tr><td class="py-2 px-2 font-mono">${l.numero_lote}</td><td class="py-2 px-2 font-medium">${l.fecha_caducidad}</td><td class="py-2 px-2 text-center font-bold">${l.stock_disponible}</td><td class="py-2 px-2 text-right ${color}">${l.estado}</td></tr>`;
        });
        html += '</tbody></table>';
        cont.innerHTML = html;
    }
    document.getElementById('modalLotes').classList.remove('hidden');
}
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
