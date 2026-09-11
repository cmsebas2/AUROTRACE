<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación Aseguramiento de Calidad AUROTRACE</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }
        .header {
            background: linear-gradient(135deg, #003B5C 0%, #005889 50%, #06B6D4 100%);
            padding: 30px 25px;
            color: #ffffff;
            text-align: left;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 900;
            letter-spacing: -0.5px;
            text-transform: uppercase;
        }
        .header p {
            margin: 6px 0 0 0;
            font-size: 12px;
            color: #cff4fc;
            font-weight: 500;
        }
        .badge-alert {
            display: inline-block;
            background-color: #06b6d4;
            color: #ffffff;
            font-size: 10px;
            font-weight: 900;
            padding: 4px 10px;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }
        .body-content {
            padding: 25px;
        }
        .info-card {
            background-color: #f1f5f9;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            border-left: 4px solid #005889;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .info-table td {
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-table tr:last-child td {
            border-bottom: none;
        }
        .info-label {
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
        }
        .info-value {
            font-weight: 800;
            color: #0f172a;
            text-align: right;
            font-family: monospace;
        }
        .cta-button {
            display: block;
            width: 100%;
            text-align: center;
            background: linear-gradient(90deg, #005889 0%, #06B6D4 100%);
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 900;
            font-size: 12px;
            padding: 14px 0;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 25px;
            box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3);
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px 25px;
            text-align: center;
            border-top: 1px solid #f1f5f9;
            font-size: 11px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="badge-alert">Aseguramiento de Calidad (QA)</span>
            <h1>Revisión de Batch Record Requerida</h1>
            <p>AUROTRACE — Trazabilidad Forense & Control de Maquilas Externas</p>
        </div>

        <div class="body-content">
            <p style="font-size: 14px; font-weight: 600; color: #334155; margin-top: 0;">
                Estimado Equipo de Aseguramiento de Calidad,
            </p>
            <p style="font-size: 13px; color: #475569; line-height: 1.5;">
                El expediente físico del <strong>Batch Record</strong> ha completado la fase de revisión técnica por Dirección Técnica y ha ingresado al estado <strong style="color: #005889;">BR REVISION CALIDAD</strong>. Se requiere su dictamen analítico para la liberación del lote.
            </p>

            <div class="info-card">
                <table class="info-table">
                    <tr>
                        <td class="info-label">Número de OP</td>
                        <td class="info-value">{{ $order->op }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Número de Lote</td>
                        <td class="info-value" style="color: #005889; font-size: 14px;">{{ $order->lote }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Producto Farmacéutico</td>
                        <td class="info-value" style="font-family: inherit;">{{ $order->producto_nombre }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Maquilador Asignado</td>
                        <td class="info-value" style="font-family: inherit;">{{ $order->maquilador->nombre ?? 'Maquila Externa' }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Ubicación en Archivo Físico</td>
                        <td class="info-value" style="color: #0891b2;">{{ $order->posicion_archivo_fisico ?? 'R 1 N 1 A 123 S 1' }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Estado Actual</td>
                        <td class="info-value" style="color: #0284c7;">BR REVISION CALIDAD</td>
                    </tr>
                    <tr>
                        <td class="info-label">Revisado Por (DT)</td>
                        <td class="info-value" style="font-family: inherit;">{{ $usuarioNombre }}</td>
                    </tr>
                </table>
            </div>

            <p style="font-size: 12px; color: #64748b; font-style: italic;">
                Por favor revise los certificados analíticos (Físico-Químico, Microbiológico y Endotoxinas) en el portal oficial para proceder con la liberación o dictamen de custodia.
            </p>

            <a href="https://aurotrace.vercel.app/calidad" class="cta-button" target="_blank">
                Ir al Portal de Calidad (QA) ➔
            </a>
        </div>

        <div class="footer">
            <p style="margin: 0;">Mensaje automático generado por el Sistema AUROTRACE — Cumplimiento Res. ICA 062542 / 21 CFR Part 11</p>
            <p style="margin: 4px 0 0 0; font-size: 10px;">Aurofarma S.A.S. — Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
