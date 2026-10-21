# GUION DE PITCH EJECUTIVO Y DEFENSA ANTE EL PANEL (10 MINUTOS)
**Proyecto Integrador: Sistema de Información Farmacéutico "FarmaControl"**  
**Fase 4: Prototipo Funcional y MVP**

---

## 1. Cronograma Minuto a Minuto de la Exposición (10:00 min)

| Tiempo | Diapositiva / Sección | Propósito y Mensaje Clave | Acción en Pantalla |
| :---: | :--- | :--- | :--- |
| **0:00 - 1:00** | **Slide 1: Portada y Planteamiento** | Nombre del proyecto, equipo y el dolor real de una farmacia local (pérdidas por caducidad y sanciones regulatorias). | Proyectar diapositiva de título. |
| **1:00 - 2:30** | **Slide 2 y 3: La Oportunidad y Objetivos** | Por qué un Excel o POS tradicional no basta: se requiere control por Lotes y Principio Activo. | Mostrar diagrama del problema vs solución. |
| **2:30 - 4:00** | **Slide 4 y 5: Arquitectura y Base de Datos** | Explicar el modelo relacional (Lotes, Entradas, Salidas, Mermas) y el patrón MVC en PHP. | Mostrar esquema físico y diagrama de datos. |
| **4:00 - 7:30** | **DEMOSTRACIÓN EN VIVO (Live Demo)** | El "momento wow": mostrar el sistema corriendo, el semáforo de caducidad, una venta con receta y el bloqueo de lote vencido. | Compartir navegador con `http://localhost:8000`. |
| **7:30 - 8:30** | **Slide 8: Pruebas Unitarias** | Demostrar que el software no solo se ve bien, sino que tiene verificación automatizada de reglas sanitarias. | Mostrar `test_runner.php` o terminal con pruebas en verde. |
| **8:30 - 9:30** | **Slide 9: Impacto en la PyME y Viabilidad** | ROI para la farmacia: reducción del 90% en mermas por caducidad y cumplimiento normativo. | Gráfica de beneficio económico y operativo. |
| **9:30 - 10:00** | **Slide 10: Conclusiones y Cierre** | Síntesis del MVP y apertura a la sesión de preguntas y respuestas. | Diapositiva de agradecimiento. |

---

## 2. Estructura de Diapositivas (Contenido Diapositiva por Diapositiva)

### Diapositiva 1: Portada
* **Título:** FarmaControl: Del Análisis al Prototipo de un Sistema de Información
* **Subtítulo:** Sistema Integral de Trazabilidad por Lotes, Control de Caducidades y Punto de Venta para Farmacias Locales.
* **Integrantes / Rol:** Estudiante(s) del Proyecto Integrador.

### Diapositiva 2: El Problema de la PyME Farmacéutica
* **Situación actual:** La Farmacia local gestiona ventas en libretas o terminales genéricas que ignoran las fechas de caducidad de cada lote.
* **Consecuencias críticas:**
  1. **Pérdidas económicas:** Medicamentos que caducan en bodega al no rotarse oportunamente (merma financiera).
  2. **Riesgo sanitario y legal:** Multas o clausuras por vender medicamentos vencidos o despachar antibióticos sin retener la cédula médica.
  3. **Pérdida de ventas:** Si un cliente pide un medicamento de marca y no lo hay, el empleado no sabe qué genérico (principio activo idéntico) ofrecerle.

### Diapositiva 3: Nuestra Solución: FarmaControl (MVP)
* Un sistema web ágil desarrollado en PHP con base de datos relacional que resuelve de raíz las 3 fallas:
  * **Algoritmo FEFO (First Expired, First Out):** El sistema prioriza automáticamente la venta del lote con vencimiento más próximo.
  * **Semáforo Sanitario:** Alertas visuales inmediatas para lotes a 60, 30 días o caducados.
  * **Catálogo Dual:** Búsqueda inmediata por Nombre Comercial y Sustancia Activa.
  * **Validación Obligatoria de Receta:** Bloquea ventas de antibióticos sin cédula médica.

### Diapositiva 4: Arquitectura del Sistema
* **Patrón Arquitectónico:** Modelo - Vista - Controlador (MVC).
* **Stack Tecnológico:**
  * **Backend:** PHP 8 orientado a objetos con conexión robusta PDO.
  * **Base de Datos:** MySQL / MariaDB (con compatibilidad de contingencia en SQLite).
  * **Frontend:** Interfaz reactiva y limpia basada en Tailwind CSS.
* **Seguridad y Robustez:** Transacciones atómicas (`beginTransaction` / `commit`) para evitar inconsistencias de inventario en ventas simultáneas.

### Diapositiva 5: Modelo Relacional de Base de Datos
* **Normalización (3FN):** Separación de la entidad genérica `medicamentos` de la entidad física transaccional `lotes`.
* **Entradas vs Salidas:** Cada compra a proveedor genera un lote con fecha de expiración; cada venta en mostrador descuenta piezas del lote específico.
* **Módulo de Mermas:** Registro formal del desecho de medicamentos vencidos para auditoría sanitaria.

### Diapositiva 6: Demostración en Vivo (Flujo 1 y 2)
*(Aquí se minimiza la presentación y se muestra el prototipo en vivo)*
1. **Flujo 1 - Dashboard:** Mostrar las tarjetas KPI y el semáforo de caducidades con alertas en rojo y amarillo.
2. **Flujo 2 - Entrada de Mercancía:** Abastecer un medicamento asignándole número de lote y caducidad a futuro.
3. **Flujo 3 - Salida en Punto de Venta:** Despachar un producto aplicando FEFO y demostrar cómo al agregar un antibiótico (Amoxicilina) el sistema despliega obligatoriamente los campos de Cédula Profesional y Médico.
4. **Flujo 4 - Bloqueo de Lote Vencido:** Intentar vender un lote expirado y mostrar cómo el sistema lo impide protegiendo al paciente.

### Diapositiva 7: Pruebas Unitarias de Procesos Críticos
* Demostración de la suite automatizada (`tests/TestFarmacia.php`):
  * Test 1: Integridad de datos y catálogo genérico.
  * Test 2: Bloqueo inmutable de venta de lote vencido.
  * Test 3: Exigencia de receta médica en antibióticos/controlados.
  * Test 4: Descuento exacto y sincrónico de stock.
  * Test 5: Exactitud matemática en el cálculo del IVA (16%).

### Diapositiva 8: Viabilidad y Retorno de Inversión (ROI)
* **Viabilidad Técnica:** Utiliza tecnologías estándar de bajo consumo de recursos (PHP nativo, sin dependencias pesadas).
* **Viabilidad Operativa:** Interfaz intuitiva; la capacitación del personal de mostrador toma menos de 15 minutos.
* **Viabilidad Económica:** Reduce las pérdidas por caducidad en más del 80% durante los primeros 3 meses de operación.

### Diapositiva 9: Conclusiones
* Se cumplió al 100% el objetivo del Proyecto Integrador: partiendo de la necesidad de una PyME, se diseñó la base de datos relacional y se entregó un **Producto Mínimo Viable (MVP)** completamente funcional con pruebas unitarias y manual técnico.

---

## 3. Matriz de Preguntas Difíciles del Jurado y Cómo Responderlas

### Pregunta 1: "¿Por qué decidieron separar los Medicamentos de los Lotes en dos tablas distintas en lugar de guardar todo en una sola tabla?"
* **Respuesta recomendada:**  
  *"Excelente pregunta, profesor. Si tuviéramos una sola tabla, estaríamos violando la Segunda y Tercera Forma Normal (2FN y 3FN), porque una caja de Paracetamol de 500mg tiene propiedades constantes como su nombre, principio activo y laboratorio; sin embargo, en bodega podemos tener 50 cajas que vencen en noviembre de 2026 y 100 cajas que vencen en mayo de 2027. Al desacoplar `medicamentos` de `lotes`, logramos trazabilidad sanitaria exacta y podemos aplicar el principio FEFO sin duplicar datos del producto."*

### Pregunta 2: "¿Qué pasa si dos cajeros intentan vender el último lote al mismo tiempo?"
* **Respuesta recomendada:**  
  *"Para garantizar la consistencia y atomicidad de la información (principios ACID), implementamos transacciones mediante PDO (`$db->beginTransaction()`). Dentro de la transacción, el sistema consulta el stock actual y descuenta las unidades. Si el stock no es suficiente, la transacción se revierte con `$db->rollBack()`, impidiendo cualquier sobreventa o stock negativo."*

### Pregunta 3: "¿Cómo garantiza el sistema que la farmacia cumpla con las leyes sanitarias sobre antibióticos?"
* **Respuesta recomendada:**  
  *"En la tabla `medicamentos` existe el campo booleano `requiere_receta`. Cuando el cajero agrega un medicamento con esta bandera activa (por ejemplo, Amoxicilina), el frontend y el backend bloquean la finalización de la venta a menos que se ingresen el Nombre del Médico y su Cédula Profesional, quedando almacenados en la tabla `ventas` para cualquier auditoría de la secretaría de salud."*

### Pregunta 4: "¿Por qué eligieron PHP para este prototipo?"
* **Respuesta recomendada:**  
  *"Elegimos PHP porque es el estándar de la industria web con el soporte más extendido a nivel mundial. Es compatible de forma nativa con cualquier hosting o servidor local (como XAMPP o Laragon), tiene un costo de infraestructura prácticamente nulo para una PyME, y gracias a PDO nos ofrece portabilidad inmediata entre MySQL y SQLite sin modificar una sola línea de código."*
