<?php
require_once __DIR__ . '/../tests/TestFarmacia.php';

$suite = new TestFarmacia();
$resultados = $suite->ejecutarTodas();

$total = count($resultados);
$exitosas = count(array_filter($resultados, fn($r) => $r['exito']));
$porcentaje = $total > 0 ? round(($exitosas / $total) * 100) : 0;

$pageTitle = 'Pruebas Unitarias - FarmaControl';
$activeTab = 'tests';
require_once __DIR__ . '/layout/header.php';
?>

<!-- Encabezado de Sección -->
<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
            <i class="fa-solid fa-vial-circle-check text-emerald-600"></i> Verificación y Pruebas Unitarias
        </h1>
        <p class="text-sm text-slate-500">Validación automatizada de reglas sanitarias, caducidad de lotes, recetas y consistencia de inventario.</p>
    </div>
    <a href="test_runner.php" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-4 py-2.5 rounded-xl shadow-sm flex items-center gap-2 text-xs transition">
        <i class="fa-solid fa-rotate-right"></i> Re-ejecutar Pruebas
    </a>
</div>

<!-- Resumen General de Aprobación -->
<div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
    <div class="flex items-center gap-5">
        <div class="w-16 h-16 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-3xl font-bold">
            <?= $porcentaje ?>%
        </div>
        <div>
            <h3 class="text-lg font-bold text-slate-800">
                <?= $exitosas === $total ? 'Todas las pruebas pasaron satisfactoriamente' : 'Algunas pruebas requieren atención' ?>
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
                <?= $exitosas ?> de <?= $total ?> casos de prueba ejecutados y validados contra la base de datos y la lógica del modelo.
            </p>
        </div>
    </div>
    <div class="flex gap-4 text-xs font-mono">
        <div class="bg-emerald-50 border border-emerald-200 px-4 py-2 rounded-xl text-emerald-800">
            <span class="block text-[10px] uppercase font-bold text-emerald-600">Aprobadas</span>
            <span class="text-lg font-bold"><?= $exitosas ?></span>
        </div>
        <div class="bg-slate-50 border border-slate-200 px-4 py-2 rounded-xl text-slate-700">
            <span class="block text-[10px] uppercase font-bold text-slate-400">Total Casos</span>
            <span class="text-lg font-bold"><?= $total ?></span>
        </div>
        <div class="bg-slate-50 border border-slate-200 px-4 py-2 rounded-xl text-slate-700">
            <span class="block text-[10px] uppercase font-bold text-slate-400">Entorno</span>
            <span class="text-sm font-bold mt-1 block">PHP 8.x / PDO</span>
        </div>
    </div>
</div>

<!-- Listado Detallado de Pruebas Unitarias -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
        <h2 class="font-bold text-slate-800 text-sm flex items-center gap-2">
            <i class="fa-solid fa-list-check text-teal-600"></i> Detalle de Aserciones y Tiempos de Respuesta
        </h2>
        <span class="text-xs text-slate-400 font-mono">CLI Command: php tests/TestFarmacia.php</span>
    </div>

    <div class="divide-y divide-slate-100">
        <?php foreach ($resultados as $idx => $r): ?>
        <div class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-slate-50/60 transition">
            <div class="flex items-start gap-4">
                <div class="mt-0.5">
                    <?php if ($r['exito']): ?>
                        <span class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm">
                            <i class="fa-solid fa-check"></i>
                        </span>
                    <?php else: ?>
                        <span class="w-8 h-8 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center font-bold text-sm">
                            <i class="fa-solid fa-xmark"></i>
                        </span>
                    <?php endif; ?>
                </div>
                <div>
                    <h4 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                        <?= htmlspecialchars($r['nombre']) ?>
                        <span class="text-[10px] font-mono px-2 py-0.5 rounded <?= $r['exito'] ? 'bg-emerald-100 text-emerald-800 font-bold' : 'bg-rose-100 text-rose-800 font-bold' ?>">
                            <?= $r['exito'] ? 'PASÓ' : 'FALLÓ' ?>
                        </span>
                    </h4>
                    <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($r['mensaje']) ?></p>
                </div>
            </div>
            <div class="text-right flex items-center md:flex-col justify-end">
                <span class="text-[11px] font-mono text-slate-400 flex items-center gap-1">
                    <i class="fa-regular fa-clock"></i> <?= $r['tiempo_ms'] ?> ms
                </span>
                <span class="text-[10px] text-teal-700 font-semibold bg-teal-50 px-2 py-0.5 rounded-full mt-1">
                    Aserción Validada
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="mt-8 bg-slate-900 text-slate-300 p-5 rounded-2xl shadow-sm text-xs space-y-2">
    <p class="font-bold text-slate-100 flex items-center gap-2">
        <i class="fa-solid fa-terminal text-teal-400"></i> Ejecución desde Consola / Terminal para el Panel:
    </p>
    <div class="bg-slate-950 p-3 rounded-xl font-mono text-teal-300 border border-slate-800 overflow-x-auto">
        cd C:\Users\Joeymir\.gemini\antigravity\scratch\farmacia_farma_control<br>
        php tests/TestFarmacia.php
    </div>
    <p class="text-slate-400 text-[11px]">
        Las pruebas se pueden correr tanto desde el navegador con un clic como desde cualquier consola de comandos de Windows (PowerShell / CMD) para la demostración en vivo ante el jurado evaluador.
    </p>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
