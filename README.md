# 🧬 AUROTRACE — ERP Farmacéutico & Sistema EBR (Protocolo E.T.A.P.A.)

Bienvenido a la documentación oficial **"For Dummies" (Fácil de Entender)** de **AUROTRACE**, el sistema de Planificación de Recursos Empresariales (ERP) y Registro Electrónico de Lotes (**EBR - Electronic Batch Record**) de **Laboratorios Aurofarma S.A.S.**

Este documento explica **qué hace el sistema**, **dónde está cada archivo** y **cómo funciona cada módulo paso a paso** sin necesidad de ser un experto en Laravel.

---

## 📌 1. ¿Qué es AUROTRACE y qué problema resuelve?

En un laboratorio farmacéutico, cada lote de medicamento que se fabrica debe quedar registrado con precisión quirúrgica: qué materias primas se usaron, quién las pesó, a qué velocidad giró el reactor, cuántas unidades salieron y qué inspector de Calidad aprobó cada etapa.

**AUROTRACE** digitaliza y blinda todo ese proceso cumpliendo con la normativa internacional **FDA 21 CFR Parte 11** (Firmas Electrónicas e Inmutabilidad de Auditoría) y las **Buenas Prácticas de Manufactura (BPM / GMP)**.

---

## 🏛️ 2. Las 4 Reglas de Oro del Sistema (`GEMINI.md`)

1. **Rendimiento Operativo (Yield 90% – 110%)**:
   - Al terminar el envasado, el sistema calcula el porcentaje de rendimiento real frente al teórico.
   - Si el rendimiento está entre **90.00% y 110.00%**, el lote pasa a `COMPLETADO`.
   - Si está por debajo de 90% o por encima de 110%, el lote se bloquea automáticamente en estado **`CUARENTENA`**.
2. **Regla del 5% en Desviaciones de Proceso (BPM)**:
   - Si durante el pesaje o la manufactura un parámetro (Peso o RPM) se desvía más de un **±5%** respecto al valor teórico del Plan Maestro, el sistema exige una observación obligatoria y dispara una **`ALERTA DE DESVIACIÓN`** en el Audit Trail.
3. **Firma Electrónica 21 CFR Parte 11**:
   - Cada firma crítica valida usuario, contraseña y sesión activa, registrando quién firmó, en nombre de quién y el significado legal de la firma.
4. **Inmutabilidad del Audit Trail (`audit_logs`)**:
   - La tabla `audit_logs` es de **solo lectura e inserción inicial**. El guardián [`AuditLogObserver.php`](app/Observers/AuditLogObserver.php) bloquea cualquier intento de editar (`UPDATE`) o borrar (`DELETE`) un registro histórico.

---

## 🗺️ 3. Mapa del Proyecto ("Dónde está cada cosa")

```text
aurotrace_codigo/
├── GEMINI.md                          # Constitución Técnica del ERP (Reglas de Oro)
├── routes/
│   └── web.php                        # Mapa de todas las URLs del sistema (organizado en 10 secciones)
├── bootstrap/
│   └── app.php                        # Configuración de arranque, middlewares y manejo de errores
├── app/
│   ├── Http/
│   │   ├── Controllers/               # El "cerebro" que recibe clics del usuario y responde vistas/JSON
│   │   └── Middleware/                # Filtros de seguridad (IsAdmin, AuditLogMiddleware)
│   ├── Models/                        # Representación de las tablas de la base de datos
│   ├── Observers/                     # Guardianes automáticos (AuditLogObserver, ProductionOrderObserver)
│   ├── Rules/                         # Reglas de validación personalizadas (Cfr21Signature)
│   ├── Services/                      # Motores de Firma Electrónica (Cfr21SignatureService, SignatureService)
│   ├── Traits/                        # Comportamientos reutilizables (AuditableTrait, SignatureTrait)
│   ├── Data/                          # Catálogo maestro estático de 1.954 ítems corporativos
│   └── Helpers/                       # Utilidades como el lector nativo de archivos Excel (.xlsx)
└── resources/
    └── views/                         # Pantallas HTML/Blade e interfaces interactivas (Alpine.js + Tailwind)
```

---

## 🧠 4. Guía de Controladores (`app/Http/Controllers/`)

Si necesitas buscar o dar mantenimiento a una pantalla, aquí tienes qué hace cada controlador:

| Controlador | ¿Qué hace en palabras sencillas? |
| :--- | :--- |
| **`AuthController.php`** | Maneja el inicio de sesión (`login`), cierre de sesión (`logout`) y registra cada acceso o intento fallido en el Audit Trail. |
| **`DashboardController.php`** | Calcula los 4 indicadores principales (KPIs) de la pantalla de inicio y el gráfico de rendimiento de los últimos 10 lotes. |
| **`ProductionOrderController.php`** | Gestiona la **Fase Documental Pre-Operativa** de una Orden de Producción (OP): Creación y explosión de materiales (`A3PPR0007`), Ajuste de Principios Activos (`A4PPR0007`), Verificación de Ajuste, Solicitud/Aprobación de Codificado (`A6PPR0007`) y Carga/Unificación PDF de COAs. |
| **`BatchController.php`** | Gestiona la **Ejecución Física en Planta (EBR)** en 5 pasos: 1. Conciliación de Materiales, 2. Despeje de Línea, 3. Dispensación (Pesaje), 4. Manufactura en Tanque y 5. Envase/Rendimiento. |
| **`MaquilaProductionOrderController.php`** | Administra de principio a fin las órdenes fabricadas por laboratorios externos (**Maquilas**): Creación de ODM, Envío a Maquilador, Recepción Parcial/Total, Llegada del Batch Record con ubicación 3D en el Archivo Físico (`RACK 1`), Revisión DT y Liberación QA. |
| **`ConsultasBrController.php`** | Buscador histórico y carga masiva desde Excel (`.xlsx`) de órdenes de maquila y consulta visual del Archivo Físico 3D. |
| **`BatchRecordController.php`** | Permite crear y editar las **Recetas / Planes Maestros de Fabricación** de cada producto (pasos de carga, agitación, RPM y controles IPC). |
| **`ProductController.php`** | Catálogo maestro de Productos, sus Ingredientes (`FormulaIngredient`) y Presentaciones Comerciales (`ProductPresentation`). |
| **`QualityController.php`** | Bandeja de Aseguramiento de Calidad para **Liberar** o **Rechazar** lotes que cayeron en estado `CUARENTENA`. |
| **`GenealogyController.php`** | Expediente Forense 360° de un lote: muestra toda su genealogía, calcula su puntaje de cumplimiento (*Compliance Score*), permite la liberación final y exporta el certificado en PDF. |
| **`EbrReportController.php`** | Visor de documentos PDF del EBR almacenados en el servidor y generador de vista previa del formato maestro `A3PPR0007`. |
| **`GlobalSignatureController.php`** | Validador universal AJAX de firmas electrónicas 21 CFR Parte 11 para todos los formularios del sistema. |
| **`UserController.php`** | Administración de Usuarios, Roles (`ADMIN`, `PRODUCCION`, `CALIDAD`, `PLANEACION`), Permisos granulares y Visor global del Audit Trail. |

---

## 🔄 5. Los 3 Flujos Principales Paso a Paso

### Flujo A: Orden de Producción Interna — Fase Documental (`ProductionOrderController`)
1. **Crear OP (`/op/crear`)**: Planeación elige el producto, tamaño del lote y presentaciones. El sistema calcula automáticamente la explosión de materias primas y empaque (`OP_CREADA`).
2. **Ajuste de Activos (`/op/ajuste-activos/{lote}`)**: Se ajusta la cantidad de Principios Activos (APIs) según la potencia y humedad del certificado y se firma (`AJ_CREADO`).
3. **Verificar Ajuste (`/op/verificar-ajuste/{lote}`)**: Calidad revisa y firma el cálculo del ajuste (`AJ_VERIFICADO`).
4. **Verificar OP (`/op/verificar-final/{lote}`)**: Cierre documental de la OP (`OP_VERIFICADA`) y generación automática del PDF Maestro.
5. **Codificado (`/op/solicitud-codificado/{lote}` y `/op/aprobar-codificado/{lote}`)**: Elaboración y aprobación del codificado de envase/empaque (`COD_CREADO` -> `COD_VERIFICADO`).
6. **COAs (`/op/coas/{lote}` y `/op/aprobar-coas/{lote}`)**: Carga de números de análisis y PDFs de Certificados de Análisis, unificación en un solo PDF y aprobación de Calidad (`COA_CREADO` -> `COA_VERIFICADO`).

### Flujo B: Ejecución en Planta EBR (`BatchController`)
1. **Conciliación (`/batch/{lote}/conciliacion`)**: Confirmación de lotes e insumos recibidos en planta.
2. **Despeje de Línea (`/batch/{lote}/despeje`)**: Checklist de 12 puntos de limpieza y presión diferencial con doble firma (Operario + Calidad) por área (`Dispensación`, `Fabricación`, `Envasado`, `Acondicionado`).
3. **Dispensación / Pesaje (`/batch/{lote}/dispensacion`)**: Pesaje ingrediente por ingrediente con control de tolerancia del ±5%.
4. **Manufactura (`/batch/{lote}/fabricacion`)**: Ejecución secuencial en tanque (RPM, tiempo, peso, IPC) bloqueada paso a paso con firma de operario y verificación QA.
5. **Envase y Rendimiento (`/batch/{lote}/envase`)**: Muestreo estadístico de pesos (`n=10` y gráfico SPC) y cálculo del rendimiento final (90%–110% -> `COMPLETADO`, fuera de rango -> `CUARENTENA`).

### Flujo C: Maquilas Externas 360° (`MaquilaProductionOrderController`)
1. **Crear ODM (`/maquila/crear`)** -> Estado `OP CREADA`.
2. **Enviar a Maquilador** -> Estado `OP EN PRODUCCION`.
3. **Recepción de Producto (`/maquila/{id}/recepcion`)** -> Entregas parciales o entrega `TOTAL` (`OP TERMINADA - BR PENDIENTE`).
4. **Llegada de Batch Record (`/maquila/{id}/llegada-br`)** -> Ubicación física 3D en `RACK 1` (`R 1 N X A Y S Z`) y cálculo de rendimiento (`BR REVISION DT`).
5. **Revisión Dirección Técnica (DT)** -> Estado `BR REVISION CALIDAD`.
6. **Dictamen Calidad (QA)** -> Si DT y QA cierran sin pendientes, pasa a `BR CERRADO`; de lo contrario queda en `BR ABIERTO`.

---

## 🛠️ 6. Comandos Útiles para Desarrolladores

```bash
# 1. Instalar dependencias de PHP y Frontend
composer install
npm install && npm run build

# 2. Ejecutar migraciones de base de datos
php artisan migrate

# 3. Levantar el servidor local de desarrollo
php artisan serve

# 4. Listar todas las rutas registradas en el sistema
php artisan route:list

# 5. Limpiar cachés de vistas, rutas y configuración
php artisan optimize:clear
```
