<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Models/Medicamento.php';
require_once __DIR__ . '/../src/Models/Lote.php';
require_once __DIR__ . '/../src/Models/Venta.php';

$medModel = new Medicamento();
$loteModel = new Lote();
$ventaModel = new Venta();

$resultadoVenta = null;

// Procesar Venta vía POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'completar_venta') {
    $clienteNombre = trim($_POST['cliente_nombre'] ?? 'Público General');
    $tipoPago = $_POST['tipo_pago'] ?? 'EFECTIVO';
    $medicoReceta = trim($_POST['medico_receta'] ?? '');
    $cedulaProfesional = trim($_POST['cedula_profesional'] ?? '');
    $itemsJson = $_POST['items_json'] ?? '[]';

    $items = json_decode($itemsJson, true);

    if (empty($items)) {
        $resultadoVenta = [
            'exito' => false,
            'mensaje' => 'El carrito de venta está vacío.'
        ];
    } else {
        $datosVenta = [
            'cliente_nombre' => $clienteNombre,
            'tipo_pago' => $tipoPago,
            'medico_receta' => $medicoReceta,
            'cedula_profesional' => $cedulaProfesional,
            'usuario_id' => 2 // Cajero
        ];
        $resultadoVenta = $ventaModel->registrarVenta($datosVenta, $items);
    }
}

// Catálogo para mostrador con sus lotes disponibles
$catalogo = $medModel->getAll();
$catalogoConLotes = [];
foreach ($catalogo as $med) {
    $lotesDisponibles = $loteModel->getByMedicamento((int)$med['id'], false);
    $med['lotes'] = $lotesDisponibles;
    $catalogoConLotes[] = $med;
}

$pageTitle = 'Punto de Venta Mostrador (POS) - FarmaControl';
$activeTab = 'pos';
require_once __DIR__ . '/layout/header.php';
?>

<!-- Encabezado de Sección -->
<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
            <i class="fa-solid fa-cash-register text-teal-600"></i> Punto de Venta (Salidas de Mostrador)
        </h1>
        <p class="text-sm text-slate-500">Despacho con selección inteligente por caducidad (FEFO) y control de recetas médicas.</p>
    </div>
    <div class="flex items-center gap-2 bg-slate-100 p-1.5 rounded-xl border border-slate-200 text-xs">
        <span class="text-slate-500"><i class="fa-solid fa-circle-info text-teal-600 mr-1"></i> Modo FEFO Activo:</span>
        <span class="font-semibold text-slate-700">Se prioriza el lote más cercano a caducar</span>
    </div>
</div>

<!-- Alertas de Transacción -->
<?php if ($resultadoVenta): ?>
<div class="mb-6 p-4 rounded-xl flex items-center justify-between gap-3 <?= $resultadoVenta['exito'] ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' ?>">
    <div class="flex items-center gap-3">
        <i class="fa-solid <?= $resultadoVenta['exito'] ? 'fa-circle-check text-emerald-600' : 'fa-circle-xmark text-rose-600' ?> text-xl"></i>
        <div>
            <p class="font-bold text-sm"><?= $resultadoVenta['exito'] ? '¡Venta Realizada con Éxito!' : 'Venta No Procesada' ?></p>
            <p class="text-xs"><?= htmlspecialchars($resultadoVenta['mensaje']) ?></p>
            <?php if ($resultadoVenta['exito']): ?>
                <p class="text-xs font-mono mt-1 font-bold">Folio: <?= htmlspecialchars($resultadoVenta['folio']) ?> | Total Cobrado: $<?= number_format((float)$resultadoVenta['total'], 2) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($resultadoVenta['exito']): ?>
    <button onclick="window.print()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium flex items-center gap-1.5 transition">
        <i class="fa-solid fa-print"></i> Imprimir Ticket
    </button>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <!-- Columna Izquierda: Catálogo y Búsqueda para Mostrador -->
    <div class="lg:col-span-7 space-y-4">
        <!-- Buscador Rápido -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex gap-3">
            <div class="relative flex-grow">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <input type="text" id="buscadorPos" placeholder="Escribe medicamento, sustancia genérica o código..." 
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500 text-xs">
            </div>
        </div>

        <!-- Rejilla de Productos Disponibles -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[620px] overflow-y-auto pr-1" id="gridProductos">
            <?php foreach ($catalogoConLotes as $item): 
                $lotesDisponibles = array_filter($item['lotes'], fn($l) => $l['stock_disponible'] > 0);
                $loteFefo = !empty($lotesDisponibles) ? reset($lotesDisponibles) : null;
            ?>
            <div class="producto-card bg-white p-4 rounded-xl border border-slate-200 hover:border-teal-400 shadow-sm hover:shadow transition flex flex-col justify-between"
                 data-nombre="<?= strtolower(htmlspecialchars($item['nombre_comercial'])) ?>"
                 data-sustancia="<?= strtolower(htmlspecialchars($item['principio_activo'])) ?>"
                 data-codigo="<?= strtolower(htmlspecialchars($item['codigo_barras'])) ?>">
                <div>
                    <div class="flex justify-between items-start gap-2">
                        <h4 class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($item['nombre_comercial']) ?></h4>
                        <span class="font-bold font-mono text-teal-700 text-sm">$<?= number_format((float)$item['precio_venta'], 2) ?></span>
                    </div>
                    <p class="text-xs text-teal-800 font-medium mt-0.5"><?= htmlspecialchars($item['principio_activo']) ?> (<?= htmlspecialchars($item['concentracion']) ?>)</p>
                    <p class="text-[11px] text-slate-400 mt-0.5"><?= htmlspecialchars($item['presentacion']) ?></p>

                    <div class="mt-2.5 flex items-center gap-1.5 flex-wrap">
                        <?php if ($item['requiere_receta']): ?>
                            <span class="badge-rx px-2 py-0.5 rounded text-[10px] font-bold">
                                <i class="fa-solid fa-file-prescription mr-0.5"></i> Rx Obligatoria
                            </span>
                        <?php endif; ?>

                        <?php if ($loteFefo): ?>
                            <span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded text-[10px] font-mono">
                                Lote: <?= htmlspecialchars($loteFefo['numero_lote']) ?> (Cad: <?= htmlspecialchars($loteFefo['fecha_caducidad']) ?>)
                            </span>
                        <?php else: ?>
                            <span class="bg-rose-100 text-rose-700 px-2 py-0.5 rounded text-[10px] font-bold">
                                Sin Stock Disponible
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500">Stock: <strong class="text-slate-800"><?= $item['stock_actual'] ?></strong></span>
                    <?php if ($loteFefo && $item['stock_actual'] > 0): ?>
                    <button onclick="agregarAlCarrito(<?= htmlspecialchars(json_encode([
                        'id' => (int)$item['id'],
                        'nombre' => $item['nombre_comercial'],
                        'principio_activo' => $item['principio_activo'],
                        'precio' => (float)$item['precio_venta'],
                        'requiere_receta' => (bool)$item['requiere_receta'],
                        'lote_id' => (int)$loteFefo['id'],
                        'lote_num' => $loteFefo['numero_lote'],
                        'fecha_caducidad' => $loteFefo['fecha_caducidad'],
                        'stock_max' => (int)$loteFefo['stock_disponible']
                    ])) ?>)" class="bg-teal-600 hover:bg-teal-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1 transition">
                        <i class="fa-solid fa-plus"></i> Despachar
                    </button>
                    <?php else: ?>
                    <button disabled class="bg-slate-200 text-slate-400 px-3 py-1.5 rounded-lg text-xs font-semibold cursor-not-allowed">
                        Agotado
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Columna Derecha: Carrito de Venta y Cobro en Mostrador -->
    <div class="lg:col-span-5 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between h-fit">
        <div>
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-bold text-slate-800 text-base flex items-center gap-2">
                    <i class="fa-solid fa-cart-shopping text-teal-600"></i> Ticket de Venta
                </h3>
                <button onclick="limpiarCarrito()" class="text-xs text-rose-500 hover:text-rose-700 font-medium">
                    Vaciar Ticket
                </button>
            </div>

            <!-- Lista de Medicamentos en Carrito -->
            <div id="carritoItems" class="divide-y divide-slate-100 my-4 max-h-64 overflow-y-auto">
                <p id="carritoVacio" class="text-center py-8 text-slate-400 text-xs">
                    El carrito está vacío. Agrega medicamentos del catálogo a la izquierda.
                </p>
            </div>
        </div>

        <!-- Formulario de Finalización de Venta -->
        <form method="POST" id="formVenta" class="space-y-4 pt-4 border-t border-slate-100 text-xs">
            <input type="hidden" name="accion" value="completar_venta">
            <input type="hidden" name="items_json" id="itemsJsonInput" value="[]">

            <div>
                <label class="block font-semibold text-slate-600 mb-1">Nombre del Paciente / Cliente</label>
                <input type="text" name="cliente_nombre" value="Público General" class="w-full p-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-600 mb-1">Forma de Pago</label>
                    <select name="tipo_pago" class="w-full p-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-teal-500">
                        <option value="EFECTIVO">Efectivo</option>
                        <option value="TARJETA">Tarjeta Débito/Crédito</option>
                        <option value="TRANSFERENCIA">Transferencia SPEI</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-600 mb-1">Cajero / Operador</label>
                    <input type="text" readonly value="Carlos Ruiz (Caja 1)" class="w-full p-2 rounded-lg bg-slate-100 text-slate-500 border border-slate-200">
                </div>
            </div>

            <!-- Sección de Validación de Receta Médica (Se activa si hay Rx en el carrito) -->
            <div id="seccionReceta" class="hidden p-3.5 bg-rose-50 border border-rose-200 rounded-xl space-y-2">
                <p class="font-bold text-rose-800 flex items-center gap-1.5 text-xs">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600"></i> Control Sanitario Obligatorio (Rx)
                </p>
                <p class="text-[11px] text-rose-700">Se han incluido medicamentos que exigen receta médica. Completa los datos:</p>
                <div>
                    <label class="block font-semibold text-rose-900 mb-0.5">Nombre del Médico Tratante *</label>
                    <input type="text" name="medico_receta" id="medicoInput" placeholder="Dr. Juan Manuel Santos" class="w-full p-2 rounded-lg border border-rose-300 text-xs">
                </div>
                <div>
                    <label class="block font-semibold text-rose-900 mb-0.5">Cédula Profesional *</label>
                    <input type="text" name="cedula_profesional" id="cedulaInput" placeholder="CED-99887766" class="w-full p-2 rounded-lg border border-rose-300 text-xs font-mono">
                </div>
            </div>

            <!-- Desglose de Totales -->
            <div class="bg-slate-50 p-3.5 rounded-xl space-y-1.5 font-mono text-xs">
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal:</span>
                    <span id="txtSubtotal">$0.00</span>
                </div>
                <div class="flex justify-between text-slate-500">
                    <span>IVA (16% medicamentos gravados):</span>
                    <span id="txtIva">$0.00</span>
                </div>
                <div class="flex justify-between text-base font-bold text-slate-900 pt-1.5 border-t border-slate-200">
                    <span>TOTAL A COBRAR:</span>
                    <span id="txtTotal" class="text-teal-700">$0.00</span>
                </div>
            </div>

            <button type="submit" id="btnCobrar" disabled class="w-full bg-teal-600 hover:bg-teal-700 disabled:bg-slate-300 text-white font-bold py-3 rounded-xl shadow-md transition text-sm flex items-center justify-center gap-2">
                <i class="fa-solid fa-circle-check"></i> Finalizar Venta y Descontar Stock
            </button>
        </form>
    </div>
</div>

<script>
let carrito = [];

function agregarAlCarrito(item) {
    const index = carrito.findIndex(i => i.id === item.id && i.lote_id === item.lote_id);
    if (index > -1) {
        if (carrito[index].cantidad < item.stock_max) {
            carrito[index].cantidad++;
        } else {
            alert('Stock máximo alcanzado para este lote.');
            return;
        }
    } else {
        carrito.push({
            id: item.id,
            nombre: item.nombre,
            principio_activo: item.principio_activo,
            precio: item.precio,
            requiere_receta: item.requiere_receta,
            lote_id: item.lote_id,
            lote_num: item.lote_num,
            fecha_caducidad: item.fecha_caducidad,
            stock_max: item.stock_max,
            cantidad: 1
        });
    }
    renderCarrito();
}

function cambiarCantidad(index, delta) {
    if (carrito[index]) {
        carrito[index].cantidad += delta;
        if (carrito[index].cantidad <= 0) {
            carrito.splice(index, 1);
        } else if (carrito[index].cantidad > carrito[index].stock_max) {
            carrito[index].cantidad = carrito[index].stock_max;
            alert('Has alcanzado la cantidad máxima en existencia para el lote.');
        }
    }
    renderCarrito();
}

function eliminarDelCarrito(index) {
    carrito.splice(index, 1);
    renderCarrito();
}

function limpiarCarrito() {
    carrito = [];
    renderCarrito();
}

function renderCarrito() {
    const container = document.getElementById('carritoItems');
    const seccionReceta = document.getElementById('seccionReceta');
    const medicoInput = document.getElementById('medicoInput');
    const cedulaInput = document.getElementById('cedulaInput');
    const btnCobrar = document.getElementById('btnCobrar');

    if (carrito.length === 0) {
        container.innerHTML = '<p class="text-center py-8 text-slate-400 text-xs">El carrito está vacío. Agrega medicamentos del catálogo a la izquierda.</p>';
        document.getElementById('txtSubtotal').innerText = '$0.00';
        document.getElementById('txtIva').innerText = '$0.00';
        document.getElementById('txtTotal').innerText = '$0.00';
        document.getElementById('itemsJsonInput').value = '[]';
        seccionReceta.classList.add('hidden');
        medicoInput.required = false;
        cedulaInput.required = false;
        btnCobrar.disabled = true;
        return;
    }

    let html = '';
    let subtotal = 0;
    let requiereRecetaTotal = false;

    carrito.forEach((item, idx) => {
        const itemSubtotal = item.precio * item.cantidad;
        subtotal += itemSubtotal;
        if (item.requiere_receta) {
            requiereRecetaTotal = true;
        }

        html += `
        <div class="py-2.5 flex items-center justify-between text-xs">
            <div class="flex-grow pr-2">
                <span class="font-bold text-slate-800 block">${item.nombre}</span>
                <span class="text-[10px] text-teal-700 font-mono">Lote: ${item.lote_num} (Vence: ${item.fecha_caducidad})</span>
                <span class="text-[11px] text-slate-500 block font-mono">$${item.precio.toFixed(2)} c/u</span>
            </div>
            <div class="flex items-center gap-1.5">
                <button type="button" onclick="cambiarCantidad(${idx}, -1)" class="w-6 h-6 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center font-bold">-</button>
                <span class="w-6 text-center font-bold text-slate-800">${item.cantidad}</span>
                <button type="button" onclick="cambiarCantidad(${idx}, 1)" class="w-6 h-6 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center font-bold">+</button>
                <span class="w-16 text-right font-mono font-bold text-slate-800 ml-2">$${itemSubtotal.toFixed(2)}</span>
                <button type="button" onclick="eliminarDelCarrito(${idx})" class="text-rose-500 hover:text-rose-700 ml-2">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </div>
        </div>`;
    });

    container.innerHTML = html;

    const iva = subtotal * 0.16;
    const total = subtotal + iva;

    document.getElementById('txtSubtotal').innerText = '$' + subtotal.toFixed(2);
    document.getElementById('txtIva').innerText = '$' + iva.toFixed(2);
    document.getElementById('txtTotal').innerText = '$' + total.toFixed(2);

    // Formatear items para el backend
    const itemsFormatted = carrito.map(i => ({
        medicamento_id: i.id,
        lote_id: i.lote_id,
        cantidad: i.cantidad,
        precio_unitario: i.precio
    }));
    document.getElementById('itemsJsonInput').value = JSON.stringify(itemsFormatted);

    // Activar o desactivar campos de receta médica
    if (requiereRecetaTotal) {
        seccionReceta.classList.remove('hidden');
        medicoInput.required = true;
        cedulaInput.required = true;
    } else {
        seccionReceta.classList.add('hidden');
        medicoInput.required = false;
        cedulaInput.required = false;
    }

    btnCobrar.disabled = false;
}

// Filtro en tiempo real del catálogo de mostrador
document.getElementById('buscadorPos').addEventListener('input', function(e) {
    const query = e.target.value.toLowerCase().trim();
    const cards = document.querySelectorAll('.producto-card');
    cards.forEach(card => {
        const nombre = card.getAttribute('data-nombre');
        const sustancia = card.getAttribute('data-sustancia');
        const codigo = card.getAttribute('data-codigo');
        if (nombre.includes(query) || sustancia.includes(query) || codigo.includes(query)) {
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    });
});
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
