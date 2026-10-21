# MANUAL TÉCNICO RESUMIDO — SISTEMA "FARMA-CONTROL"
**Proyecto Integrador: Del Análisis al Prototipo de un Sistema de Información**  
**Fase 4: Prototipo Funcional y MVP Farmacéutico**

---

## 1. Ficha Técnica y Especificaciones del Entorno

| Parámetro | Especificación |
| :--- | :--- |
| **Nombre del Sistema** | **FarmaControl** (Sistema de Información Farmacéutico) |
| **Tipo de Sistema** | Aplicación Web / Cliente-Servidor (Patrón MVC Adaptado) |
| **Lenguaje del Servidor** | **PHP 8.0+** (PHP Nativo orientado a objetos con PDO) |
| **Motor de Base de Datos** | **MySQL / MariaDB** (con soporte de respaldo transparente en **SQLite 3**) |
| **Interfaz y Estilos** | HTML5 semántico, **Tailwind CSS (CDN)**, FontAwesome 6 |
| **Compatibilidad** | Multiplataforma (Windows 10/11, Linux, macOS). Compatible con XAMPP, Laragon, WampServer o servidor embebido de PHP |

---

## 2. Arquitectura de Software y Estructura del Proyecto

El sistema adopta el patrón arquitectónico **Modelo - Vista - Controlador (MVC)** para separar responsabilidades entre la persistencia de datos, las reglas de negocio farmacéuticas y la experiencia de usuario.

```text
farmacia_farma_control/
├── config/
│   └── database.php          # Gestor de conexión PDO (MySQL con auto-fallback a SQLite)
├── data/
│   └── farmacia.sqlite       # Base de datos embebida (se autogenera si no hay MySQL)
├── sql/
│   └── farmacia_db.sql       # Script DDL/DML completo con datos semilla
├── src/
│   └── Models/
│       ├── Medicamento.php   # Catálogo, sustancias activas, alertas de stock y caducidad
│       ├── Lote.php          # Trazabilidad de lotes, algoritmo FEFO y bajas de merma
│       ├── Entrada.php       # Compras a proveedores y alta automática de inventario
│       └── Venta.php         # Salidas de mostrador, recetas médicas y facturación
├── public/
│   ├── layout/
│   │   ├── header.php        # Barra de navegación, alertas y branding
│   │   └── footer.php        # Metadatos del proyecto y estado
│   ├── index.php             # Dashboard ejecutivo y semáforo de caducidades
│   ├── medicamentos.php      # CRUD del catálogo de medicamentos y búsqueda de genéricos
│   ├── entradas.php          # Registro de compras y recepción de lotes
│   ├── pos.php               # Punto de venta (POS) y despacho en mostrador
│   ├── reportes.php          # Kardex cronológico, historial de bajas y mermas
│   └── test_runner.php       # Interfaz visual interactiva de pruebas unitarias
├── tests/
│   └── TestFarmacia.php      # Suite de pruebas unitarias por consola (CLI)
├── MANUAL_TECNICO.md         # Manual de especificación técnica y despliegue
├── PITCH_Y_DEFENSA.md        # Guion de exposición de 10 minutos para el jurado
└── README.md                 # Instrucciones rápidas de ejecución
```

---

## 3. Modelo de Datos y Reglas de Negocio Implementadas

### A. Diagrama Entidad-Relación y Tablas Críticas
1. **`usuarios`**: Control de acceso con roles (`ADMIN` para el Químico Farmacéutico responsable y `CAJERO` para atención en mostrador).
2. **`medicamentos`**: Registra tanto el **Nombre Comercial** como el **Principio Activo (Genérico)**, presentación, laboratorio, precios y la bandera regulatoria `requiere_receta` (0 = Venta Libre / OTC, 1 = Requiere Receta / Rx).
3. **`lotes`**: Elemento nuclear de una farmacia. Asocia cada medicamento con su `numero_lote` y su `fecha_caducidad`.
4. **`entradas` y `detalle_entradas`**: Registran las compras a laboratorios, incrementando sincrónicamente el stock global y creando/ampliando lotes.
5. **`ventas` y `detalle_ventas`**: Despacho de medicamentos en mostrador.
6. **`mermas`**: Trazabilidad de productos destruidos o dados de baja por caducidad o daños.

### B. Reglas Sanitarias y de Lógica de Negocio en Código
* **Regla 1 (Bloqueo de Caducados):** En el método `Venta::registrarVenta()`, el sistema compara la `fecha_caducidad` del lote seleccionado con la fecha del sistema (`date('Y-m-d')`). Si el lote ya expiró, la transacción se aborta con un mensaje de alerta sanitaria y no descuenta inventario.
* **Regla 2 (Despacho FEFO - First Expired, First Out):** En `Lote::getByMedicamento()`, los lotes disponibles se ordenan de forma ascendente por `fecha_caducidad`. Esto garantiza que el cajero siempre despache primero el medicamento más próximo a expirar.
* **Regla 3 (Antibióticos y Controlados - Rx):** Si el carrito contiene uno o más medicamentos con `requiere_receta = 1`, la interfaz y el backend exigen de forma obligatoria el **Nombre del Médico Tratante** y su **Cédula Profesional**.
* **Regla 4 (Precisión Fiscal):** Se calculan por separado Subtotal, IVA al 16% y Total con redondeo estricto a 2 decimales (`round($val, 2)`).

---

## 4. Instrucciones de Instalación y Ejecución

El proyecto fue diseñado con **Zero-Config**: puede ejecutarse de dos formas:

### Opción 1: Servidor Integrado de PHP (Forma más rápida)
1. Abrir una terminal (PowerShell o CMD) en la carpeta del proyecto:
   ```bash
   cd C:\Users\Joeymir\.gemini\antigravity\scratch\farmacia_farma_control
   ```
2. Levantar el servidor web apuntando a la carpeta `public`:
   ```bash
   php -S localhost:8000 -t public
   ```
3. Abrir el navegador en:
   ```text
   http://localhost:8000
   ```
   *(El sistema detecta automáticamente SQLite y crea las tablas con datos semilla sin necesidad de configurar MySQL).*

### Opción 2: Usando XAMPP / MariaDB
1. Abrir **XAMPP Control Panel** e iniciar los módulos **Apache** y **MySQL**.
2. Abrir **phpMyAdmin** (`http://localhost/phpmyadmin`).
3. Importar el archivo ubicado en:
   `sql/farmacia_db.sql`
4. Copiar la carpeta `farmacia_farma_control` dentro de `C:\xampp\htdocs\`.
5. Acceder desde el navegador a:
   ```text
   http://localhost/farmacia_farma_control/public/
   ```

---

## 5. Ejecución de la Suite de Pruebas Unitarias

Para demostrar ante el tribunal que el núcleo del software es robusto:

* **Modo Gráfico:** Ingresar desde la barra de navegación a **"Pruebas Unitarias"** (`public/test_runner.php`). Permite re-ejecutar en vivo las pruebas con tiempos de respuesta en milisegundos.
* **Modo Consola (Terminal CLI):**
  ```bash
  php tests/TestFarmacia.php
  ```
  Salida esperada:
  ```text
  ========================================================
   FARMA-CONTROL: SUITE DE PRUEBAS UNITARIAS (MVP FASE 4)
  ========================================================
  [ PASS ] Test 1: Persistencia y Validación de Medicamento
  [ PASS ] Test 2: Regla Sanitaria - Bloqueo de Venta de Lote Caducado
  [ PASS ] Test 3: Regla Sanitaria - Exigencia de Receta Médica en Antibióticos
  [ PASS ] Test 4: Integridad de Inventario - Descuento de Stock en Venta
  [ PASS ] Test 5: Precisión Contable - Cálculo de Subtotal, IVA (16%) y Total
  --------------------------------------------------------
  Resumen: 5 / 5 pruebas superadas con éxito.
  ========================================================
  ```
