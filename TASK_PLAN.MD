# TASK PLAN - AUROFARMA ERP (E.T.A.P.A. ENTERPRISE)
## Hoja de Ruta de Migración Sistémica

Este plan define la transformación del software desde un controlador de planta a un sistema ERP integral con cumplimiento regulatorio global.

---

### ETAPA 1: Infraestructura de Transaccionalidad Global
**Objetivo:** Migrar todas las firmas actuales al nuevo `SignatureService` y motor de auditoría Enterprise.

**Criterios de Aceptación:**
- [x] Migración de Base de Datos para auditoría extendida.
- [x] Creación del Core Service de Firmas Electrónicas.
- [ ] Refactorización de `BatchController`: Sustituir validaciones manuales por el servicio inyectable.
- [ ] Implementación de la "Firma en nombre de" con selector de operario responsable en todas las vistas EBR.

---

### ETAPA 2: Dominio de Inventarios y Almacén
**Objetivo:** Control total de stocks con segregación de lotes y estados de calidad.

**Criterios de Aceptación:**
- [ ] Módulo de Lotes: Seguimiento desde el ingreso (Materia Prima) hasta el egreso (Producto Terminado).
- [ ] Cuarentena Automática: Todo ingreso marcado como no disponible hasta aprobación técnica.
- [ ] Alertas FEFO: Bloqueo de uso de insumos próximos a vencer.
- [ ] Ajustes con Firma Reforzada: Todo cambio manual de stock requiere justificación y doble firma.

---

### ETAPA 3: Dominio de Compras (Procurement)
**Objetivo:** Digitalización del flujo de abastecimiento bajo homologación de proveedores.

**Criterios de Aceptación:**
- [ ] Maestro de Proveedores: Estados "Homologado", "En Proceso", "Bloqueado".
- [ ] Flujo de OC: Ciclo de Solicitud -> Aprobación (Gerencia/Calidad) -> Emisión.
- [ ] Recepción Técnica: Integración con Inventario para dar entrada a lotes inspeccionados.

---

### ETAPA 4: Dominio de Mantenimiento de Activos
**Objetivo:** Garantizar que ningún proceso se ejecute con equipos fuera de especificación.

**Criterios de Aceptación:**
- [ ] Dashboard de Calibración: Semaforización de equipos próximos a vencer.
- [ ] Bloqueo Transaccional: El sistema impide seleccionar equipos con calibración vencida en el módulo de producción.
- [ ] Historial de Mantenimiento (E-Logbook): Registro de intervenciones técnicas por activo.

---

### ETAPA 5: Engine de Reportes y Auditoría Final
**Objetivo:** Generación de expedientes legales unificados (Batch Record + Audit Trail).

**Criterios de Aceptación:**
- [ ] Generador PDF Enterprise: Inclusión de firmas de todos los departamentos en el reporte final.
- [ ] Módulo de Auditoría Forense: Filtros avanzados por "Firma en nombre de" y desvíos técnicos.

---
*Fin del Mapa de Migración Enterprise*
