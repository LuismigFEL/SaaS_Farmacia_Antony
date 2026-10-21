<?php
// Layout Header común para el sistema FarmaControl
if (!isset($pageTitle)) {
    $pageTitle = 'FarmaControl - Sistema de Gestión Farmacéutica';
}
if (!isset($activeTab)) {
    $activeTab = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .badge-rx { background-color: #fee2e2; color: #991b1b; border: 1px solid #f87171; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col">

    <!-- Barra Superior de Navegación -->
    <nav class="bg-teal-700 text-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logotipo e Identidad del Sistema -->
                <div class="flex items-center space-x-3">
                    <div class="bg-white text-teal-700 p-2 rounded-xl shadow-md font-bold text-xl flex items-center justify-center w-10 h-10">
                        <i class="fa-solid fa-staff-snake"></i>
                    </div>
                    <div>
                        <a href="index.php" class="font-bold text-lg tracking-tight hover:text-teal-100 flex items-center gap-2">
                            FarmaControl <span class="bg-teal-900 text-teal-200 text-xs px-2 py-0.5 rounded-full font-medium">MVP PyME</span>
                        </a>
                        <p class="text-xs text-teal-200 hidden sm:block">Sistema Integral de Inventario, Lotes y Punto de Venta</p>
                    </div>
                </div>

                <!-- Menú de Navegación Principal -->
                <div class="hidden md:flex space-x-1 lg:space-x-2">
                    <a href="index.php" class="<?= $activeTab === 'dashboard' ? 'bg-teal-800 text-white font-semibold' : 'text-teal-100 hover:bg-teal-600' ?> px-3 py-2 rounded-lg text-sm flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-chart-pie text-xs"></i> Dashboard
                    </a>
                    <a href="medicamentos.php" class="<?= $activeTab === 'medicamentos' ? 'bg-teal-800 text-white font-semibold' : 'text-teal-100 hover:bg-teal-600' ?> px-3 py-2 rounded-lg text-sm flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-pills text-xs"></i> Medicamentos
                    </a>
                    <a href="entradas.php" class="<?= $activeTab === 'entradas' ? 'bg-teal-800 text-white font-semibold' : 'text-teal-100 hover:bg-teal-600' ?> px-3 py-2 rounded-lg text-sm flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-truck-ramp-box text-xs"></i> Entradas / Compras
                    </a>
                    <a href="pos.php" class="<?= $activeTab === 'pos' ? 'bg-teal-800 text-white font-semibold' : 'text-teal-100 hover:bg-teal-600' ?> px-3 py-2 rounded-lg text-sm flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-cash-register text-xs"></i> Punto de Venta (POS)
                    </a>
                    <a href="reportes.php" class="<?= $activeTab === 'reportes' ? 'bg-teal-800 text-white font-semibold' : 'text-teal-100 hover:bg-teal-600' ?> px-3 py-2 rounded-lg text-sm flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-file-invoice-dollar text-xs"></i> Reportes / Kardex
                    </a>
                    <a href="test_runner.php" class="<?= $activeTab === 'tests' ? 'bg-emerald-600 text-white font-bold ring-2 ring-emerald-300' : 'bg-emerald-800/60 text-emerald-100 hover:bg-emerald-700' ?> px-3 py-2 rounded-lg text-sm flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-circle-check text-xs"></i> Pruebas Unitarias
                    </a>
                </div>

                <!-- Usuario y Estado de Conexión -->
                <div class="flex items-center space-x-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                        <span class="w-1.5 h-1.5 mr-1.5 bg-emerald-500 rounded-full animate-pulse"></span> Sistema En Línea
                    </span>
                    <div class="text-right hidden lg:block text-xs text-teal-200">
                        <p class="font-medium text-white">Dra. Elena Vargas</p>
                        <p>Químico Farmacéutico (Admin)</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Menú Móvil Simple -->
        <div class="md:hidden flex overflow-x-auto px-4 py-2 bg-teal-800 gap-2 text-xs">
            <a href="index.php" class="text-white px-2 py-1 whitespace-nowrap">Dashboard</a>
            <a href="medicamentos.php" class="text-white px-2 py-1 whitespace-nowrap">Medicamentos</a>
            <a href="entradas.php" class="text-white px-2 py-1 whitespace-nowrap">Entradas</a>
            <a href="pos.php" class="text-white px-2 py-1 whitespace-nowrap">POS</a>
            <a href="reportes.php" class="text-white px-2 py-1 whitespace-nowrap">Reportes</a>
            <a href="test_runner.php" class="text-emerald-300 px-2 py-1 whitespace-nowrap font-bold">Pruebas</a>
        </div>
    </nav>

    <!-- Contenedor Principal -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
