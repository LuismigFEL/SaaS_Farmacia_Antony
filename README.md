# FarmaControl — Sistema de Información Farmacéutico (MVP)
**Proyecto Integrador: Del Análisis al Prototipo de un Sistema de Información (Fase 4)**

Prototipo funcional completo para una farmacia local / PyME, desarrollado en **PHP 8 + MySQL/SQLite + Tailwind CSS**.

---

## 🚀 Inicio Rápido (En 5 segundos)

### 1. Iniciar con el Servidor Embebido de PHP (Recomendado)
Abre tu terminal en esta carpeta y ejecuta:
```bash
php -S localhost:8000 -t public
```
Abre tu navegador en: [http://localhost:8000](http://localhost:8000)

*Nota: El sistema incluye autoconfiguración híbrida. Si no tienes MySQL corriendo, se iniciará automáticamente con SQLite en `data/farmacia.sqlite` con datos semilla de ejemplo.*

### 2. Iniciar con XAMPP / MariaDB
1. Inicia **Apache** y **MySQL** en el panel de XAMPP.
2. Importa el archivo `sql/farmacia_db.sql` en phpMyAdmin.
3. Coloca esta carpeta en `C:\xampp\htdocs\` y visita:
   [http://localhost/farmacia_farma_control/public/](http://localhost/farmacia_farma_control/public/)

---

## 🧪 Pruebas Unitarias Automatizadas

Puedes ejecutarlas de dos formas:
1. **Desde la Web:** Entra al menú superior y haz clic en **"Pruebas Unitarias"** (`http://localhost:8000/test_runner.php`).
2. **Desde la Consola:**
   ```bash
   php tests/TestFarmacia.php
   ```

---

## 📂 Documentos de Entrega y Exposición

* 📘 [Manual Técnico Resumido](MANUAL_TECNICO.md): Arquitectura MVC, tablas, reglas de negocio sanitarias y endpoints.
* 🎙️ [Guion de Defensa y Pitch de 10 Minutos](PITCH_Y_DEFENSA.md): Minuto a minuto, diapositivas y respuestas preparadas para el jurado evaluador.
* 🗄️ [Script SQL](sql/farmacia_db.sql): Base de datos relacional completa con normalización y datos de prueba.
