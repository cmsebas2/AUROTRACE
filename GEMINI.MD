# AUROFARMA ERP - PROTOCOLO E.T.A.P.A.
## Fase E: Estrategia y Leyes del Proyecto

Este documento constituye la "Constitución Técnica" del ERP AUROFARMA. Cualquier cambio en la lógica del sistema debe respetar estas leyes fundamentales.

---

### Artículo 1: Esquema de Datos Primario (Flujo EBR)

El sistema opera bajo una arquitectura de trazabilidad total basada en el lote físico.

*   **ProductionOrder (OP)**: El nodo central. Contiene el `status` del lote (`PESAJE`, `MANUFACTURA`, `ACONDICIONAMIENTO`, `CUARENTENA`) y el `final_yield_percentage`.
*   **BatchPackagingResult**: Almacena los resultados del módulo de envase, incluyendo el `units_obtained` y el `average_weight`. Está vinculado 1:1 con la OP.
*   **AuditLog**: El guardián de la integridad. Registra cada cambio en los modelos auditables de forma inmutable.

---

### Artículo 2: Invariantes de Calidad (Reglas de Oro)

1.  **Rendimiento Operativo (Yield)**:
    *   **Rango de Aceptación**: 90.00% - 110.00%.
    *   **Acción de Desviación**: Si el rendimiento está fuera de este rango, el lote se mueve automáticamente a `status = 'CUARENTENA'` y se bloquea el botón de finalización en la interfaz.
2.  **Desviaciones de Proceso (BPM)**:
    *   **Regla del 5%**: Cualquier parámetro dinámico (RPM, Temperatura, Peso) que se desvíe más de un 5% del valor maestro definido en el Plan Maestro disparará una observación obligatoria y un registro de alerta en el Audit Log.

---

### Artículo 3: Protocolo de Firma Electrónica (21 CFR Part 11)

La integridad de las firmas se basa en tres pilares:
1.  **Doble Identificación**: Para cada firma de Calidad (QA), se requiere el email y password del usuario verificador.
2.  **Validación de Sesión**: Además de las credenciales, el sistema verifica `Auth::check()` para asegurar que la sesión del operario/usuario sigue activa, previniendo suplantaciones por terminales desatendidas.
3.  **Mapeo de Significado**: Cada firma técnica se traduce a un "Significado de Firma" (Meaning) legible para humanos en los registros de auditoría.

---

### Artículo 4: Inmutabilidad de Registros

*   **Audit Trail**: La tabla `audit_logs` es de **SÓLO LECTURA/ESCRITURA INICIAL**.
*   **Restricción Estricta**: Existe un `AuditLogObserver` que lanza una excepción inmediata si se detecta un intento de `UPDATE` o `DELETE` sobre cualquier registro de auditoría.
*   **Traceability**: No se permiten eliminaciones lógicas ni físicas de registros que comprometan la genealogía del lote.

---

### Artículo 5: Control de Inventarios (Ley de FEFO)

El ERP garantiza que ningún insumo vencido o en cuarentena sea utilizado en producción.
*   **Trazabilidad de Lote**: Cada movimiento de stock debe estar vinculado al lote del proveedor y al certificado de análisis (COA).
*   **Ajustes de Inventario**: Todo ajuste manual de stock requiere firma reforzada y auditoría inmediata.

---

### Artículo 6: Gestión de Compras (Homologación)

La calidad del producto final comienza con la selección del proveedor.
*   **Proveedores Críticos**: Solo se pueden emitir órdenes de compra a proveedores previamente homologados por Garantía de Calidad.
*   **Recepción Técnica**: Todo ingreso al almacén debe pasar por una inspección técnica obligatoria antes de salir de CUARENTENA.

---

### Artículo 7: Mantenimiento y Calibración

Los equipos de planta son el soporte de la precisión.
*   **Estado de Calibración**: El sistema bloqueará el uso de cualquier equipo (balanza, reactor, envasadora) cuya fecha de calibración esté vencida.
*   **Hoja de Vida**: Cada activo debe tener una hoja de vida electrónica que registre su historia de mantenimientos correctivos y preventivos.

---
*Fin del Documento - Fase E Global Enterprise*
