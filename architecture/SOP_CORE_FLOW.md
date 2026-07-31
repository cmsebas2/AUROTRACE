# SOP-FLO-003: CICLO DE VIDA DEL LOTE (CORE FLOW)

## 1. OBJETIVO
Definir el flujo secuencial y obligatorio que debe seguir un producto desde su creación técnica hasta su liberación para venta.

## 2. ETAPAS DEL FLUJO EBR
El ERP AUROFARMA impone una transición de estados estrictamente lineal:

1.  **MAESTRO (Master Data)**: Definición de Fórmulas y Planes de Manufactura.
2.  **PLANEADO**: Creación de la OP (Orden de Producción).
3.  **PESAJE (Dispensación)**: Selección y pesaje de materias primas. Cambio automático a estado `PESAJE`.
4.  **MANUFACTURA (Fabricación)**: Procesamiento físico del granel. Cambio automático a estado `MANUFACTURA` tras cerrar dispensación.
5.  **ACONDICIONAMIENTO (Envase)**: Llenado y empaque final. Cambio automático a `ACONDICIONAMIENTO`.
6.  **LIBERACIÓN / CERRADO**: Firma final de Calidad y cálculo de rendimiento.

## 3. BLOQUEOS SECUENCIALES
A nivel de sistema, no se puede acceder a la etapa $N+1$ si la etapa $N$ no ha sido firmada por Operario y verificada por QA. Las rutas del Batch Record están protegidas por lógica de controlador que verifica el `status` de la OP antes de renderizar la vista.

## 4. ESTADOS ESPECIALES
*   **CUARENTENA**: Estado automático si el rendimiento final es desvía del rango 90-110%. El lote queda bloqueado para cualquier acción posterior hasta la liberación manual por Dirección Técnica.
*   **ANULADO**: Estado final para lotes cancelados.

## 5. RECONCILIACIÓN
Cada lote debe pasar por una reconciliación de materiales al inicio y al final del flujo, asegurando que el 100% de la materia prima e insumos de empaque estén debidamente justificados.
