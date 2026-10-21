<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Models/Medicamento.php';
require_once __DIR__ . '/../src/Models/Venta.php';

$medModel = new Medicamento();
$ventaModel = new Venta();
$db = Database::getConnection();

// Datos del Dashboard
$totalMedicamentos = count($medModel->getAll());
$alertasStock = $medModel->getAlertasStock();
$alertasCaducidad = $medModel->getAlertasCaducidad();

// Ventas totales y del día
$stmtVentas = $db->query("SELECT COALESCE(SUM(total), 0) as total_ventas, COUNT(*) as conteo FROM ventas");
$ventasInfo = $stmtVentas->fetch();

$stmtEntradas = $db->query("SELECT COALESCE(SUM(total_compra), 0) as total_compras, COUNT(*) as conteo FROM entradas");
$entradasInfo = $stmtEntradas->fetch();

// Últimas 5 ventas
$ultimasVentas = $ventaModel->getAll();
$ultimasVentas = array_slice($ultimasVentas, 0, 5);

$pageTitle = 'Dashboard General - FarmaControl';
$activeTab = 'dashboard';
require_once __DIR__ . '/layout/header.php';
?>

<!-- Banner de Bienvenida y Estado del Negocio -->
<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
            Panel de Control Farmacéutico
            <span class="text-xs font-semibold px-2.5 py-1 bg-teal-100 text-teal-800 rounded-lg">Sucursal Centro</span>
        </h1>
        <p class="text-sm text-slate-500 mt-1">Supervisión en tiempo real de inventarios por lote, caducidades y flujo de caja.</p>
    </div>
    <div class="flex flex-wrap gap-2.5">
        <a href="pos.php" class="bg-teal-600 hover:bg-teal-700 text-white font-medium px-4 py-2.5 rounded-xl shadow-sm flex items-center gap-2 text-sm transition">
            <i class="fa-solid fa-cash-register"></i> Nueva Venta
        </a>
        <a href="entradas.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2.5 rounded-xl shadow-sm flex items-center gap-2 text-sm transition">
            <i class="fa-solid fa-truck-ramp-box"></i> Registrar Compra
        </a>
        <a href="test_runner.php" class="bg-slate-800 hover:bg-slate-900 text-emerald-400 font-medium px-3.5 py-2.5 rounded-xl text-sm flex items-center gap-2 transition">
            <i class="fa-solid fa-vial-circle-check"></i> Pruebas MVP
        </a>
    </div>
</div>

<!-- Tarjetas KPI Principales -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <!-- Total Productos -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Catálogo Activo</p>
            <h3 class="text-2xl font-bold text-slate-800 mt-1"><?= $totalMedicamentos ?></h3>
            <span class="text-xs text-teal-600 font-medium flex items-center gap-1 mt-1">
                <i class="fa-solid fa-box-archive"></i> Fórmulas y genéricos
            </span>
        </div>
        <div class="w-12 h-12 bg-teal-50 text-teal-600 rounded-xl flex items-center justify-center text-xl">
            <i class="fa-solid fa-pills"></i>
        </div>
    </div>

    <!-- Alertas de Caducidad -->
    <div class="bg-white p-5 rounded-2xl border <?= count($alertasCaducidad) > 0 ? 'border-amber-300 bg-amber-50/20' : 'border-slate-200' ?> shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Riesgo Caducidad</p>
            <h3 class="text-2xl font-bold text-amber-700 mt-1"><?= count($alertasCaducidad) ?></h3>
            <span class="text-xs text-amber-600 font-medium flex items-center gap-1 mt-1">
                <i class="fa-solid fa-triangle-exclamation"></i> Lotes &le; 60 días o vencidos
            </span>
        </div>
        <div class="w-12 h-12 bg-amber-100 text-amber-700 rounded-xl flex items-center justify-center text-xl">
            <i class="fa-solid fa-calendar-xmark"></i>
        </div>
    </div>

    <!-- Alertas Stock Mínimo -->
    <div class="bg-white p-5 rounded-2xl border <?= count($alertasStock) > 0 ? 'border-rose-300 bg-rose-50/20' : 'border-slate-200' ?> shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold text-rose-600 uppercase tracking-wider">Stock Crítico</p>
            <h3 class="text-2xl font-bold text-rose-700 mt-1"><?= count($alertasStock) ?></h3>
            <span class="text-xs text-rose-600 font-medium flex items-center gap-1 mt-1">
                <i class="fa-solid fa-circle-down"></i> Bajo nivel de reorden
            </span>
        </div>
        <div class="w-12 h-12 bg-rose-100 text-rose-700 rounded-xl flex items-center justify-center text-xl">
            <i class="fa-solid fa-arrow-trend-down"></i>
        </div>
    </div>

    <!-- Ingresos por Ventas -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Ventas Acumuladas</p>
            <h3 class="text-2xl font-bold text-slate-800 mt-1">$<?= number_format((float)$ventasInfo['total_ventas'], 2) ?></h3>
            <span class="text-xs text-emerald-600 font-medium flex items-center gap-1 mt-1">
                <i class="fa-solid fa-receipt"></i> <?= $ventasInfo['conteo'] ?> tickets emitidos
            </span>
        </div>
        <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-xl">
            <i class="fa-solid fa-hand-holding-dollar"></i>
        </div>
    </div>
</div>

<!-- Tablas de Control Crítico Farmacéutico -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Tabla 1: Semáforo de Caducidad y Lotes -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
                <h2 class="font-bold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-amber-500"></i> Semáforo Sanitario de Caducidad
                </h2>
                <p class="text-xs text-slate-500">Lotes que requieren remoción o rotación FEFO inmediata</p>
            </div>
            <a href="reportes.php" class="text-xs text-teal-600 hover:text-teal-800 font-semibold">Ver Todo &rarr;</a>
        </div>
        <div class="p-4 flex-grow overflow-x-auto">
            <?php if (empty($alertasCaducidad)): ?>
                <div class="text-center py-8 text-slate-400 text-sm">
                    <i class="fa-solid fa-shield-heart text-3xl text-emerald-400 mb-2"></i>
                    <p>Excelente: No hay medicamentos vencidos ni próximos a caducar.</p>
                </div>
            <?php else: ?>
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="text-slate-400 uppercase border-b border-slate-100">
                        <tr>
                            <th class="py-2.5">Medicamento</th>
                            <th class="py-2.5">Lote</th>
                            <th class="py-2.5">Caducidad</th>
                            <th class="py-2.5 text-center">Stock</th>
                            <th class="py-2.5 text-right">Estatus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($alertasCaducidad as $alerta): 
                            $dias = (int)$alerta['dias_restantes'];
                            $esVencido = $dias <= 0;
                            $esUrgente = $dias > 0 && $dias <= 30;
                        ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 font-medium text-slate-800">
                                <?= htmlspecialchars($alerta['nombre_comercial']) ?>
                                <span class="block text-[11px] text-slate-400"><?= htmlspecialchars($alerta['principio_activo']) ?></span>
                            </td>
                            <td class="py-3 font-mono text-slate-700"><?= htmlspecialchars($alerta['numero_lote']) ?></td>
                            <td class="py-3 font-medium"><?= htmlspecialchars($alerta['fecha_caducidad']) ?></td>
                            <td class="py-3 text-center font-bold text-slate-700"><?= $alerta['stock_disponible'] ?></td>
                            <td class="py-3 text-right">
                                <?php if ($esVencido): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800">
                                        <i class="fa-solid fa-ban mr-1"></i> VENCIDO
                                    </span>
                                <?php elseif ($esUrgente): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">
                                        <i class="fa-solid fa-hourglass-half mr-1"></i> <?= $dias ?> días
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-yellow-100 text-yellow-800">
                                        <?= $dias ?> días
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabla 2: Medicamentos con Stock Crítico -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
                <h2 class="font-bold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-box-open text-rose-500"></i> Alertas de Stock Crítico
                </h2>
                <p class="text-xs text-slate-500">Productos por debajo del nivel de seguridad para reposición</p>
            </div>
            <a href="entradas.php" class="text-xs text-teal-600 hover:text-teal-800 font-semibold">Abastecer &rarr;</a>
        </div>
        <div class="p-4 flex-grow overflow-x-auto">
            <?php if (empty($alertasStock)): ?>
                <div class="text-center py-8 text-slate-400 text-sm">
                    <i class="fa-solid fa-circle-check text-3xl text-emerald-400 mb-2"></i>
                    <p>Todos los medicamentos cuentan con niveles de stock óptimos.</p>
                </div>
            <?php else: ?>
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="text-slate-400 uppercase border-b border-slate-100">
                        <tr>
                            <th class="py-2.5">Medicamento</th>
                            <th class="py-2.5">Laboratorio</th>
                            <th class="py-2.5 text-center">Actual</th>
                            <th class="py-2.5 text-center">Mínimo</th>
                            <th class="py-2.5 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($alertasStock as $med): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 font-medium text-slate-800">
                                <?= htmlspecialchars($med['nombre_comercial']) ?>
                                <span class="block text-[11px] text-slate-400"><?= htmlspecialchars($med['principio_activo']) ?> (<?= htmlspecialchars($med['concentracion']) ?>)</span>
                            </td>
                            <td class="py-3 text-slate-600"><?= htmlspecialchars($med['laboratorio']) ?></td>
                            <td class="py-3 text-center font-extrabold text-rose-600"><?= $med['stock_actual'] ?></td>
                            <td class="py-3 text-center text-slate-400 font-medium"><?= $med['stock_minimo'] ?></td>
                            <td class="py-3 text-right">
                                <a href="entradas.php?med_id=<?= $med['id'] ?>" class="inline-flex items-center px-2.5 py-1 bg-teal-50 text-teal-700 hover:bg-teal-100 rounded-lg text-xs font-semibold transition">
                                    <i class="fa-solid fa-plus mr-1"></i> Comprar
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Sección de Flujo de Operaciones Recientes -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-8">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="font-bold text-slate-800 text-base">Últimas Salidas / Ventas Registradas</h2>
            <p class="text-xs text-slate-500">Historial transaccional en mostrador</p>
        </div>
        <a href="pos.php" class="text-xs font-semibold text-teal-600 hover:text-teal-800">Ir al Punto de Venta &rarr;</a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-slate-50 text-slate-400 uppercase">
                <tr>
                    <th class="py-3 px-4 rounded-l-lg">Folio</th>
                    <th class="py-3 px-4">Cliente</th>
                    <th class="py-3 px-4">Método</th>
                    <th class="py-3 px-4 text-center">Receta</th>
                    <th class="py-3 px-4 text-right">Subtotal</th>
                    <th class="py-3 px-4 text-right">IVA (16%)</th>
                    <th class="py-3 px-4 text-right">Total</th>
                    <th class="py-3 px-4 text-right rounded-r-lg">Fecha</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($ultimasVentas as $vta): ?>
                <tr class="hover:bg-slate-50 transition">
                    <td class="py-3 px-4 font-mono font-bold text-teal-700"><?= htmlspecialchars($vta['folio_venta']) ?></td>
                    <td class="py-3 px-4 font-medium text-slate-800"><?= htmlspecialchars($vta['cliente_nombre']) ?></td>
                    <td class="py-3 px-4">
                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700">
                            <?= htmlspecialchars($vta['tipo_pago']) ?>
                        </span>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <?php if ($vta['requirio_receta']): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                <i class="fa-solid fa-file-prescription mr-1"></i> SI (<?= htmlspecialchars($vta['medico_receta'] ?? 'Validada') ?>)
                            </span>
                        <?php else: ?>
                            <span class="text-slate-400">No</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-4 text-right font-mono">$<?= number_format((float)$vta['subtotal'], 2) ?></td>
                    <td class="py-3 px-4 text-right font-mono text-slate-400">$<?= number_format((float)$vta['iva'], 2) ?></td>
                    <td class="py-3 px-4 text-right font-mono font-bold text-slate-800">$<?= number_format((float)$vta['total'], 2) ?></td>
                    <td class="py-3 px-4 text-right text-slate-400"><?= htmlspecialchars($vta['fecha_venta']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
