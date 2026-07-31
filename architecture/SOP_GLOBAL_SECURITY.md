# SOP-SEC-001: SEGURIDAD GLOBAL Y FIRMA ELECTRÓNICA

## 1. OBJETIVO
Definir los mecanismos de control de acceso, niveles de autoridad y el protocolo de firma electrónica para garantizar el cumplimiento de la norma **21 CFR Part 11** en el ERP AUROFARMA.

## 2. SISTEMA DE ROLES
El sistema gestiona la autoridad mediante tres perfiles lógicos:
*   **ADMIN (Súper Usuario)**: Acceso total a configuración de maestros y gestión de usuarios.
*   **OPERARIO**: Ejecución de procesos de pesaje, fabricación y envase. Autorizado para firmas de "Realizó".
*   **QA (Calidad)**: Verificación de procesos críticos. Autorizado para firmas de "Verificó". Requiere validación de credenciales en cada firma.

## 3. MIDDLEWARE DE AUTENTICACIÓN
Todas las rutas internas están protegidas por el middleware `auth` de Laravel. El acceso requiere una sesión activa mediante email y password. No se permite el acceso de invitados a módulos de producción.

## 4. PROTOCOLO DE FIRMA ELECTRÓNICA (21 CFR Part 11)
Para acciones críticas (Cierres de etapa, Verificaciones de Calidad, Desvíos), se aplica el siguiente flujo:
1.  **Doble Validación**: El usuario debe re-ingresar su email y password en un modal específico de firma.
2.  **Verificación de Sesión**: El sistema cruza `Auth::id()` con las credenciales ingresadas para evitar suplantación de terminales desatendidas.
3.  **Significado de Firma**: Toda firma debe guardar un "Meaning" (ej. "Firma de Verificación QA - Despeje de Línea") en los registros de auditoría.

## 5. RE-AUTENTICACIÓN OBLIGATORIA
Las firmas de Calidad (QA) caducan por cada evento. No se permite la persistencia de credenciales de firma entre diferentes pasos del EBR, obligando al usuario verificado a autenticarse físicamente en cada punto de control.
