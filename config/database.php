<?php
/**
 * Conexión a Base de Datos - FarmaControl
 * Compatible con MySQL (XAMPP / Laragon) y SQLite (Fallback automático sin configuración)
 */

class Database {
    private static ?PDO $instance = null;

    // Configuración para MySQL
    private static string $host = '127.0.0.1';
    private static string $db_name = 'farmacia_db';
    private static string $username = 'root';
    private static string $password = '';
    private static string $port = '3306';

    public static function getConnection(): PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        // Intento 1: Conexión a MySQL
        try {
            $dsn = "mysql:host=" . self::$host . ";port=" . self::$port . ";dbname=" . self::$db_name . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 2
            ];
            self::$instance = new PDO($dsn, self::$username, self::$password, $options);
            return self::$instance;
        } catch (PDOException $e) {
            // Si MySQL no está activo, usar SQLite local transparente para que el prototipo funcione de inmediato
            return self::getSqliteConnection();
        }
    }

    private static function getSqliteConnection(): PDO {
        $dataDir = __DIR__ . '/../data';
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0777, true);
        }
        $sqliteFile = $dataDir . '/farmacia.sqlite';
        $isNew = !file_exists($sqliteFile);

        $pdo = new PDO("sqlite:" . $sqliteFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        if ($isNew) {
            self::initSqliteSchema($pdo);
        }

        self::$instance = $pdo;
        return self::$instance;
    }

    private static function initSqliteSchema(PDO $pdo): void {
        $schema = "
        CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            rol TEXT DEFAULT 'FARMACEUTICO',
            activo INTEGER DEFAULT 1,
            creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS proveedores (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            rfc_o_cif TEXT NOT NULL,
            razon_social TEXT NOT NULL,
            contacto TEXT,
            telefono TEXT,
            email TEXT,
            direccion TEXT,
            activo INTEGER DEFAULT 1
        );

        CREATE TABLE IF NOT EXISTS medicamentos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            codigo_barras TEXT UNIQUE NOT NULL,
            nombre_comercial TEXT NOT NULL,
            principio_activo TEXT NOT NULL,
            concentracion TEXT,
            presentacion TEXT NOT NULL,
            laboratorio TEXT NOT NULL,
            requiere_receta INTEGER DEFAULT 0,
            precio_compra REAL NOT NULL,
            precio_venta REAL NOT NULL,
            stock_actual INTEGER DEFAULT 0,
            stock_minimo INTEGER DEFAULT 10,
            activo INTEGER DEFAULT 1,
            creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS lotes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            medicamento_id INTEGER NOT NULL,
            numero_lote TEXT NOT NULL,
            fecha_fabricacion DATE,
            fecha_caducidad DATE NOT NULL,
            stock_inicial INTEGER NOT NULL,
            stock_disponible INTEGER NOT NULL,
            estado TEXT DEFAULT 'DISPONIBLE',
            FOREIGN KEY (medicamento_id) REFERENCES medicamentos(id)
        );

        CREATE TABLE IF NOT EXISTS entradas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            numero_factura TEXT,
            proveedor_id INTEGER NOT NULL,
            usuario_id INTEGER NOT NULL,
            fecha_entrada DATETIME DEFAULT CURRENT_TIMESTAMP,
            total_compra REAL NOT NULL,
            observaciones TEXT
        );

        CREATE TABLE IF NOT EXISTS detalle_entradas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            entrada_id INTEGER NOT NULL,
            medicamento_id INTEGER NOT NULL,
            numero_lote TEXT NOT NULL,
            fecha_caducidad DATE NOT NULL,
            cantidad INTEGER NOT NULL,
            precio_unitario REAL NOT NULL,
            subtotal REAL NOT NULL
        );

        CREATE TABLE IF NOT EXISTS ventas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            folio_venta TEXT UNIQUE NOT NULL,
            usuario_id INTEGER NOT NULL,
            cliente_nombre TEXT DEFAULT 'Público General',
            tipo_pago TEXT DEFAULT 'EFECTIVO',
            requirio_receta INTEGER DEFAULT 0,
            medico_receta TEXT NULL,
            cedula_profesional TEXT NULL,
            subtotal REAL NOT NULL,
            iva REAL NOT NULL,
            total REAL NOT NULL,
            fecha_venta DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS detalle_ventas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            venta_id INTEGER NOT NULL,
            medicamento_id INTEGER NOT NULL,
            lote_id INTEGER NOT NULL,
            cantidad INTEGER NOT NULL,
            precio_unitario REAL NOT NULL,
            subtotal REAL NOT NULL
        );

        CREATE TABLE IF NOT EXISTS mermas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            medicamento_id INTEGER NOT NULL,
            lote_id INTEGER NOT NULL,
            cantidad INTEGER NOT NULL,
            motivo TEXT NOT NULL,
            usuario_id INTEGER NOT NULL,
            fecha_baja DATETIME DEFAULT CURRENT_TIMESTAMP,
            observaciones TEXT
        );

        -- Semilla inicial
        INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES
        ('Dra. Elena Vargas (QF)', 'elena@farmacia.com', 'hash123', 'ADMIN'),
        ('Carlos Ruiz (Caja)', 'carlos@farmacia.com', 'hash123', 'CAJERO');

        INSERT INTO proveedores (rfc_o_cif, razon_social, contacto, telefono, email, direccion) VALUES
        ('DIS940822AA1', 'Distribuidora Farmacéutica del Centro', 'Lic. Roberto Gómez', '55-5829-1000', 'ventas@farmacentro.com', 'Parque Industrial Norte Bodega 14'),
        ('LAB890115BB2', 'Laboratorios Sanofi & Genéricos', 'Dra. Martha Silva', '55-4123-9988', 'pedidos@sanofigen.com', 'Av. Universidad 1200, CDMX');

        INSERT INTO medicamentos (codigo_barras, nombre_comercial, principio_activo, concentracion, presentacion, laboratorio, requiere_receta, precio_compra, precio_venta, stock_actual, stock_minimo) VALUES
        ('7501008493012', 'Tempra', 'Paracetamol', '500 mg', 'Caja con 20 tabletas', 'Reckitt', 0, 32.50, 58.00, 45, 15),
        ('7501234567890', 'Amoxil', 'Amoxicilina', '500 mg', 'Caja con 12 cápsulas', 'GSK', 1, 65.00, 110.00, 24, 10),
        ('7509876543211', 'Advil Max', 'Ibuprofeno', '400 mg', 'Caja con 10 cápsulas líquidas', 'Pfizer', 0, 48.00, 85.00, 8, 12),
        ('7505551234567', 'Inhibitron', 'Omeprazol', '20 mg', 'Frasco con 14 cápsulas', 'Senosiain', 0, 55.00, 95.00, 35, 10),
        ('7504449876543', 'Cozaar', 'Losartán', '50 mg', 'Caja con 30 grageas', 'Merck', 1, 140.00, 230.00, 18, 8),
        ('7503332221110', 'Ventolin', 'Salbutamol', '100 mcg', 'Aerosol 200 dosis', 'GSK', 1, 120.00, 195.00, 5, 10);

        INSERT INTO lotes (medicamento_id, numero_lote, fecha_fabricacion, fecha_caducidad, stock_inicial, stock_disponible, estado) VALUES
        (1, 'LOT-TP24-A', '2024-01-10', '2026-11-30', 50, 45, 'DISPONIBLE'),
        (2, 'LOT-AMX-99', '2023-06-15', '2026-11-15', 30, 24, 'POR_VENCER'),
        (3, 'LOT-ADV-01', '2024-02-01', '2027-05-20', 20, 8, 'DISPONIBLE'),
        (4, 'LOT-OME-55', '2024-03-12', '2026-12-31', 40, 35, 'DISPONIBLE'),
        (5, 'LOT-LOS-12', '2024-01-05', '2027-02-15', 20, 18, 'DISPONIBLE'),
        (6, 'LOT-SAL-OLD', '2022-01-10', '2024-05-10', 10, 5, 'VENCIDO');

        INSERT INTO ventas (folio_venta, usuario_id, cliente_nombre, tipo_pago, requirio_receta, subtotal, iva, total) VALUES
        ('VTA-001', 2, 'María González', 'EFECTIVO', 0, 123.28, 19.72, 143.00),
        ('VTA-002', 2, 'Juan Pérez', 'TARJETA', 1, 293.10, 46.90, 340.00);
        ";
        $pdo->exec($schema);
    }
}
