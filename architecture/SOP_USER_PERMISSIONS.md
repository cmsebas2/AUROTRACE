# SOP-PER-007: MATRIZ DE PERMISOS Y ACCESOS

## 1. OBJETIVO
Definir la arquitectura de acceso basado en roles para prevenir el uso no autorizado de funciones críticas.

## 2. MATRIZ DE ROLES (RBAC)
Los accesos se segmentan por dominios de negocio:
*   **OPERARIO**: Acceso solo a ejecución de producción e inventario básico.
*   **CALIDAD**: Acceso a liberaciones, aprobaciones de insumos y firmas de verificación.
*   **ADMIN**: Acceso a configuración técnica, logs y gestión de usuarios. No autorizado para firmas de producción (segregación de funciones).
*   **SUPERVISOR / GERENCIA**: Acceso a reportes y autoritaciones de alto nivel (compras, auditoría).

## 3. SEGREGACIÓN DE FUNCIONES (SoD)
Nadie puede ser "Juez y Parte" en un proceso. 
*   Ejemplo: El usuario que realiza el pesaje no puede autorverificar el paso de calidad.
*   Ejemplo: El que crea una orden de compra no puede ser el mismo que aprueba el pago.

## 4. GESTIÓN DE CUENTAS
*   **Cuentas Personales**: Está estrictamente prohibido el uso de cuentas genéricas o compartidas.
*   **Bloqueo de Sesión**: La sesión caduca automáticamente tras 60 minutos de inactividad para prevenir acceso físico no autorizado.

## 5. AUDITORÍA DE ACCESOS
Mensualmente se generará un reporte de usuarios y sus niveles de acceso actuales. Cualquier cambio en el rol de un usuario debe estar acompañado de una firma de la Dirección Técnica y quedar registrado en el Audit Log con el significado "Cambio de Privilegios de Usuario".
