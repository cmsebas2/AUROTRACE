# SOP-ERR-004: GESTIÓN DE DESVIACIONES Y ERRORES

## 1. OBJETIVO
Protocolizar la respuesta técnica del sistema ante desviaciones de proceso o fallos de validación, garantizando la seguridad del paciente.

## 2. REGLA DE DESVIACIÓN DE PROCESO (+/- 5%)
Durante la Manufactura, el sistema compara cada valor ingresado (RPM, Temperatura, Tiempo) contra el valor maestro:
*   **Acción**: Si la diferencia es mayor al 5%, el campo de `observaciones` se vuelve **obligatorio** (`Rule::requiredIf`).
*   **Alerta**: Se dispara un evento `ALERTA DE DESVIACIÓN` en el Audit Log con el valor fuera de especificación.

## 3. REGLA DE RENDIMIENTO CRÍTICO (90% - 110%)
Al finalizar el Envase, el sistema calcula el rendimiento final:
*   **Bloqueo**: Si el rendimiento es < 90% o > 110%, el botón de finalizar lote se desactiva por JavaScript.
*   **Quarantena**: Si se fuerza el cierre, el sistema asigna el estado `CUARENTENA` automáticamente.
*   **Investigación**: El sistema requiere un flujo de justificación técnica fuera de los procesos estándar del EBR.

## 4. VALIDACIÓN DE CREDENCIALES
Ante tres intentos fallidos de firma electrónica (Doble validación password), el sistema lanzará un error de autenticación 401 y registrará el intento fallido en el log de seguridad.

## 5. INTEGRIDAD TRANSACCIONAL
Todas las operaciones de base de datos críticas deben estar envueltas en `DB::beginTransaction()`. Si ocurre una excepción de PHP, el sistema realiza un `rollback` total para evitar datos inconsistentes o firmas parciales.
