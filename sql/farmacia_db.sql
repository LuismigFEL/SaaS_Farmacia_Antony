-- ========================================================
-- SISTEMA DE GESTIÓN FARMACÉUTICA: FARMA-CONTROL
-- Base de Datos Relacional: MySQL / MariaDB (Compatible con SQLite)
-- Fase 4: Prototipo Funcional y MVP
-- ========================================================

CREATE DATABASE IF NOT EXISTS farmacia_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE farmacia_db;

-- 1. Tabla de Usuarios y Roles
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('ADMIN', 'FARMACEUTICO', 'CAJERO') DEFAULT 'FARMACEUTICO',
    activo TINYINT(1) DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabla de Proveedores y Laboratorios
CREATE TABLE IF NOT EXISTS proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rfc_o_cif VARCHAR(20) NOT NULL,
    razon_social VARCHAR(150) NOT NULL,
    contacto VARCHAR(100),
    telefono VARCHAR(20),
    email VARCHAR(100),
    direccion TEXT,
    activo TINYINT(1) DEFAULT 1
);

-- 3. Tabla de Medicamentos / Catálogo
CREATE TABLE IF NOT EXISTS medicamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_barras VARCHAR(50) UNIQUE NOT NULL,
    nombre_comercial VARCHAR(150) NOT NULL,
    principio_activo VARCHAR(150) NOT NULL, -- Sustancia genérica
    concentracion VARCHAR(50),               -- Ej. 500mg, 10mg/ml
    presentacion VARCHAR(100) NOT NULL,      -- Ej. Caja 20 tabletas, Jarabe 120ml
    laboratorio VARCHAR(100) NOT NULL,       -- Ej. Bayer, Genomma Lab, Pfizer
    requiere_receta TINYINT(1) DEFAULT 0,    -- 1 = Requiere receta (antibióticos/controlados)
    precio_compra DECIMAL(10, 2) NOT NULL,
    precio_venta DECIMAL(10, 2) NOT NULL,
    stock_actual INT DEFAULT 0,
    stock_minimo INT DEFAULT 10,             -- Umbral para alerta de reorden
    activo TINYINT(1) DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Tabla de Lotes y Fechas de Caducidad (Regla Crítica Farmacéutica)
CREATE TABLE IF NOT EXISTS lotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicamento_id INT NOT NULL,
    numero_lote VARCHAR(50) NOT NULL,
    fecha_fabricacion DATE,
    fecha_caducidad DATE NOT NULL,
    stock_inicial INT NOT NULL,
    stock_disponible INT NOT NULL,
    estado ENUM('DISPONIBLE', 'POR_VENCER', 'VENCIDO', 'AGOTADO') DEFAULT 'DISPONIBLE',
    FOREIGN KEY (medicamento_id) REFERENCES medicamentos(id) ON DELETE CASCADE
);

-- 5. Tabla de Entradas de Inventario (Compras / Abastecimiento)
CREATE TABLE IF NOT EXISTS entradas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_factura VARCHAR(50),
    proveedor_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha_entrada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_compra DECIMAL(10, 2) NOT NULL,
    observaciones TEXT,
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- 6. Detalle de Entradas
CREATE TABLE IF NOT EXISTS detalle_entradas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entrada_id INT NOT NULL,
    medicamento_id INT NOT NULL,
    numero_lote VARCHAR(50) NOT NULL,
    fecha_caducidad DATE NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (entrada_id) REFERENCES entradas(id) ON DELETE CASCADE,
    FOREIGN KEY (medicamento_id) REFERENCES medicamentos(id)
);

-- 7. Tabla de Salidas / Ventas (POS)
CREATE TABLE IF NOT EXISTS ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folio_venta VARCHAR(30) UNIQUE NOT NULL,
    usuario_id INT NOT NULL,
    cliente_nombre VARCHAR(120) DEFAULT 'Público General',
    tipo_pago ENUM('EFECTIVO', 'TARJETA', 'TRANSFERENCIA') DEFAULT 'EFECTIVO',
    requirio_receta TINYINT(1) DEFAULT 0,
    medico_receta VARCHAR(120) NULL,
    cedula_profesional VARCHAR(50) NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    iva DECIMAL(10, 2) NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    fecha_venta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- 8. Detalle de Salidas / Ventas
CREATE TABLE IF NOT EXISTS detalle_ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    medicamento_id INT NOT NULL,
    lote_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
    FOREIGN KEY (medicamento_id) REFERENCES medicamentos(id),
    FOREIGN KEY (lote_id) REFERENCES lotes(id)
);

-- 9. Tabla de Bajas y Mermas (Medicamentos caducados o dañados)
CREATE TABLE IF NOT EXISTS mermas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicamento_id INT NOT NULL,
    lote_id INT NOT NULL,
    cantidad INT NOT NULL,
    motivo ENUM('CADUCIDAD', 'EMPAQUE_DANADO', 'ROTO', 'DEFECTUOSO') NOT NULL,
    usuario_id INT NOT NULL,
    fecha_baja TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    observaciones TEXT,
    FOREIGN KEY (medicamento_id) REFERENCES medicamentos(id),
    FOREIGN KEY (lote_id) REFERENCES lotes(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- ========================================================
-- DATOS SEMILLA PARA PRUEBAS Y DEMOSTRACIÓN
-- ========================================================

-- Usuarios Iniciales
INSERT INTO usuarios (id, nombre, email, password_hash, rol) VALUES
(1, 'Dra. Elena Vargas (QF)', 'elena.farmacia@local.com', '$2y$10$abcdefghijklmnopqrstuv', 'ADMIN'),
(2, 'Carlos Ruiz (Cajero)', 'carlos.caja@local.com', '$2y$10$abcdefghijklmnopqrstuv', 'CAJERO');

-- Proveedores
INSERT INTO proveedores (id, rfc_o_cif, razon_social, contacto, telefono, email, direccion) VALUES
(1, 'DIS940822AA1', 'Distribuidora Farmacéutica del Centro', 'Lic. Roberto Gómez', '55-5829-1000', 'ventas@farmacentro.com', 'Parque Industrial Norte Bodega 14'),
(2, 'LAB890115BB2', 'Laboratorios Sanofi & Genéricos', 'Dra. Martha Silva', '55-4123-9988', 'pedidos@sanofigen.com', 'Av. Universidad 1200, CDMX');

-- Medicamentos con Principio Activo, Laboratorio y Alertas
INSERT INTO medicamentos (id, codigo_barras, nombre_comercial, principio_activo, concentracion, presentacion, laboratorio, requiere_receta, precio_compra, precio_venta, stock_actual, stock_minimo) VALUES
(1, '7501008493012', 'Tempra', 'Paracetamol', '500 mg', 'Caja con 20 tabletas', 'Reckitt', 0, 32.50, 58.00, 45, 15),
(2, '7501234567890', 'Amoxil', 'Amoxicilina', '500 mg', 'Caja con 12 cápsulas', 'GSK', 1, 65.00, 110.00, 24, 10),
(3, '7509876543211', 'Advil Max', 'Ibuprofeno', '400 mg', 'Caja con 10 cápsulas líquidas', 'Pfizer', 0, 48.00, 85.00, 8, 12), -- Stock bajo
(4, '7505551234567', 'Inhibitron', 'Omeprazol', '20 mg', 'Frasco con 14 cápsulas', 'Senosiain', 0, 55.00, 95.00, 35, 10),
(5, '7504449876543', 'Cozaar', 'Losartán', '50 mg', 'Caja con 30 grageas', 'Merck', 1, 140.00, 230.00, 18, 8),
(6, '7503332221110', 'Ventolin', 'Salbutamol', '100 mcg', 'Aerosol inhalador 200 dosis', 'GSK', 1, 120.00, 195.00, 5, 10); -- Stock bajo

-- Lotes y Caducidades (Con casos: Vencido, Por Vencer y Vigente)
INSERT INTO lotes (id, medicamento_id, numero_lote, fecha_fabricacion, fecha_caducidad, stock_inicial, stock_disponible, estado) VALUES
-- Tempra (Vigente)
(1, 1, 'LOT-TP24-A', '2024-01-10', '2026-11-30', 50, 45, 'DISPONIBLE'),
-- Amoxicilina (Próximo a Vencer en menos de 30 días para alertas)
(2, 2, 'LOT-AMX-99', '2023-06-15', '2026-11-15', 30, 24, 'POR_VENCER'),
-- Advil (Vigente con stock crítico)
(3, 3, 'LOT-ADV-01', '2024-02-01', '2027-05-20', 20, 8, 'DISPONIBLE'),
-- Omeprazol (Vigente)
(4, 4, 'LOT-OME-55', '2024-03-12', '2026-12-31', 40, 35, 'DISPONIBLE'),
-- Losartán (Vigente)
(5, 5, 'LOT-LOS-12', '2024-01-05', '2027-02-15', 20, 18, 'DISPONIBLE'),
-- Salbutamol (Lote caducado para probar bloqueo de venta y mermas)
(6, 6, 'LOT-SAL-OLD', '2022-01-10', '2024-05-10', 10, 5, 'VENCIDO');

-- Entradas registradas (Historial)
INSERT INTO entradas (id, numero_factura, proveedor_id, usuario_id, fecha_entrada, total_compra, observaciones) VALUES
(1, 'FAC-8921', 1, 1, '2026-09-15 10:30:00', 3185.00, 'Recepción mensual regular'),
(2, 'FAC-9014', 2, 1, '2026-10-02 14:00:00', 3620.00, 'Reposición urgente de antibióticos y analgésicos');

-- Ventas registradas
INSERT INTO ventas (id, folio_venta, usuario_id, cliente_nombre, tipo_pago, requirio_receta, medico_receta, cedula_profesional, subtotal, iva, total, fecha_venta) VALUES
('1', 'VTA-202610-001', 2, 'María González', 'EFECTIVO', 0, NULL, NULL, 123.28, 19.72, 143.00, '2026-10-18 09:15:00'),
('2', 'VTA-202610-002', 2, 'Juan Pérez', 'TARJETA', 1, 'Dr. Armando Morales', 'CED-782190', 293.10, 46.90, 340.00, '2026-10-19 11:40:00');
