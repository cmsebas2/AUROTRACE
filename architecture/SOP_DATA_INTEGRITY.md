# SOP-INT-002: INTEGRIDAD DE DATOS Y AUDITORÍA

## 1. OBJETIVO
Establecer los protocolos técnicos que aseguren la inmutabilidad y transparencia de la información procesada en el ERP AUROFARMA.

## 2. EL "QA WATCHDOG" (MIDDLEWARE)
El sistema cuenta con un centinela automático (`AuditLogMiddleware`) que intercepta todas las peticiones a rutas críticas (`store`, `sign`, `verify`, `cerrar`). Su función es:
*   **Captura Automática**: Registra al usuario, la acción realizada, los valores ingresados y la dirección IP.
*   **Registro Selectivo**: Excluye datos sensibles como passwords, pero guarda el JSON completo de la transacción para auditoría forense.

## 3. TRAZABILIDAD INMUTABLE (AUDIT LOGS)
Todos los registros de la tabla `audit_logs` son protegidos por la ley de "Escritura Única":
1.  **Observador de Bloqueo**: El `AuditLogObserver` lanza una excepción inmediata si detecta un intento de `UPDATE` o `DELETE` sobre cualquier log.
2.  **Integridad de la Genealogía**: No existe la eliminación lógica (Soft Delete) ni física para datos de auditoría.

## 4. RESPONSABILIDAD TÉCNICA
Cada registro de auditoría vincula:
*   **Actor**: Quién realizó la acción.
*   **Objeto**: Qué modelo fue afectado (ej. ProductionOrder #15).
*   **Dato**: Qué cambió (Antes -> Después).
*   **IP**: Desde dónde se realizó la acción.

## 5. AUDITORÍA INVIMA / EXTERNA
El sistema debe permitir la exportación de este "Audit Trail" en formatos no modificables (PDF) para soportar inspecciones regulatorias, garantizando que el software no ha permitido la manipulación de resultados de producción.
