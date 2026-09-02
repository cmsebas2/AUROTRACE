# Documentación Técnica de Auditoría: Módulo Control de Producción en Maquilas Externas

**Sistema ERP AuroTrace - Laboratorios Aurofarma**  
**Normativa de Cumplimiento:** 21 CFR Parte 11 (FDA) | EudraLex Anexo 11 | Resolución ICA 062542 de 2020 (Colombia)

---

## 1. Contexto Regulatorio y Arquitectura de Datos

El módulo **Control de Producción en Maquilas Externas** ha sido diseñado para garantizar la trazabilidad 360° en la manufactura por terceros de medicamentos veterinarios, diferenciando operativamente entre **Premezcla** (insumo intermedio medicado con controles estrictos de homogeneidad y potencia) y **Producto Terminado** (liberación comercial).

### 1.1 Modelo Relacional Primario
- `maquiladores`: Catálogo corporativo no harcodeado con control de fecha de vencimiento de certificado BPM-ICA (`certificado_bpm_ica_vigente_hasta`).
- `maquila_production_orders`: Orden central de fabricación (ODM) y su solicitud correlativa (SDM).
- `maquila_items`: Detalle por ítem, lote físico, presentación y cantidad programada.
- `maquila_deliveries`: Entregas parciales recibidas con cálculo de porcentaje de aporte al lote.
- `electronic_signatures`: Registro inmutable polimórfico de firmas digitales bajo 21 CFR Parte 11.

---

## 2. Protocolo de Firma Electrónica (21 CFR Parte 11)

### 2.1 Requisitos de Identificación
Conforme a 21 CFR § 11.200:
1. **Doble Identificación:** Cada firma de recepción parcial o cierre técnico exige la re-autenticación activa del usuario mediante su Identificador (Email/Nombre) y su Contraseña, independientemente de la sesión activa en el navegador.
2. **Sello de Tiempo Servidor:** La estampa de tiempo (`signed_at`) se genera exclusivamente desde el servidor de aplicaciones en UTC/Servidor, impidiendo la manipulación desde el lado del cliente.
3. **Hash de Integridad SHA-256:** Cada firma genera un hash inmutable derivado del payload (Remisión, Cantidad, Fecha, ID Verificador, Timestamp). Cualquier intento de modificación en base de datos alterará la firma y disparará una excepción de integridad.

### 2.2 Protocolo de Doble Firma Electrónica (Liquidación Final)
El cierre técnico y la congelación de los rendimientos (Yield %) requieren **Doble Firma Electrónica Obligatoria**:
- **Firma 1:** Operador de Producción / Logística.
- **Firma 2:** Supervisor de Aseguramiento de Calidad.

Ambas firmas quedan vinculadas al registro en `electronic_signatures` con el `meaning` explicativo registrado.

---

## 3. Motor de Cálculo e Invariantes de Calidad

### 3.1 Saldo Pendiente
$$\text{Saldo Pendiente} = \text{Cantidad Programada} - \sum \text{Cantidad Recibida}$$

### 3.2 Rendimiento Operativo (Yield %)
$$\text{Yield \%} = \left( \frac{\sum \text{Cantidad Recibida}}{\text{Cantidad Programada}} \right) \times 100$$

### 3.3 Desviación y Tolerancias BPM ICA
- **Premezclas:** Tolerancia máxima del $\pm 3.0\%$ respecto al $100.0\%$.
- **Producto Terminado:** Tolerancia máxima del $\pm 5.0\%$.
- **Clasificación Automática:** Si la desviación sobrepasa los límites configurados, el sistema clasifica el resultado como **Merma** o **Exceso**, obligando a ingresar una justificación técnica antes del cierre.

### 3.4 Lead Time (Tiempo en Maquila)
$$\text{Lead Time (días)} = \text{Fecha Última Entrega} - \text{Fecha Envió a Maquila}$$

---

## 4. Auditoría y Rastro de Auditoría (Audit Trail)

Todas las transacciones sobre el módulo ejecutan registros automáticos en la tabla `audit_logs` del ERP AuroTrace.
- Registros de **Sólo Escritura Inicial** (inmutables por `AuditLogObserver`).
- Mapeo de significado para auditores externos (ICA / INVIMA).
