<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aviso de Calidad QA - AUROTRACE</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }
        .header {
            background: linear-gradient(135deg, #003B5C 0%, #005889 50%, #06B6D4 100%);
            padding: 32px 24px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 900;
            letter-spacing: -0.5px;
            text-transform: uppercase;
        }
        .header p {
            margin: 6px 0 0 0;
            font-size: 13px;
            opacity: 0.9;
        }
        .badge-alert {
            display: inline-block;
            background-color: #06b6d4;
            color: #ffffff;
            font-size: 10px;
            font-weight: 900;
            padding: 4px 12px;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 12px;
        }
        .content {
            padding: 32px 24px;
        }
        .greeting {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 16px;
            color: #1e293b;
        }
        .info-card {
            background-color: #f8fafc;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            padding: 20px;
            margin: 20px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px border #e2e8f0;
            font-size: 13px;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 11px;
        }
        .info-value {
            color: #0f172a;
            font-weight: 800;
            text-align: right;
        }
        .highlight-lote {
            font-family: monospace;
            background-color: #ecfeff;
            color: #0891b2;
            padding: 2px 8px;
            border-radius: 6px;
            border: 1px solid #a5f3fc;
        }
        .btn-container {
            text-align: center;
            margin: 32px 0 16px 0;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #005889 0%, #003B5C 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 900;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(0, 88, 137, 0.3);
        }
        .footer {
            background-color: #f1f5f9;
            padding: 20px;
            text-align: center;
            font-size: 11px;
            color: #64748b;
            border-t: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>AUROTRACE</h1>
            <p>Control de Maquilas & Trazabilidad de Batch Records</p>
            <div class="badge-alert">Aviso de Revisión QA</div>
        </div>

        <div class="content">
            <div class="greeting">Atención Equipo de Aseguramiento de Calidad (QA),</div>
            <p style="font-size: 13px; line-height: 1.6; color: #334155;">
                Se ha registrado una actualización en el ciclo de vida del Batch Record. La siguiente orden requiere su dictamen analítico y verificación de liberación:
            </p>

            <div class="info-card">
                <table width="100%" cellpadding="0" cellspacing="0" style="font-size: 13px;">
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; font-weight: 700; font-size: 11px; text-transform: uppercase;">Número de Lote:</td>
                        <td style="padding: 6px 0; text-align: right;"><span class="highlight-lote">{{ $order->lote ?? 'N/A' }}</span></td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; font-weight: 700; font-size: 11px; text-transform: uppercase;">Número de OP:</td>
                        <td style="padding: 6px 0; text-align: right; font-weight: 800; color: #0f172a;">{{ $order->op ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; font-weight: 700; font-size: 11px; text-transform: uppercase;">Producto:</td>
                        <td style="padding: 6px 0; text-align: right; font-weight: 800; color: #0f172a;">{{ $order->producto_nombre ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; font-weight: 700; font-size: 11px; text-transform: uppercase;">Laboratorio Maquilador:</td>
                        <td style="padding: 6px 0; text-align: right; font-weight: 800; color: #0f172a;">{{ $order->maquilador->nombre ?? 'Sin Maquilador' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; font-weight: 700; font-size: 11px; text-transform: uppercase;">Ubicación Física:</td>
                        <td style="padding: 6px 0; text-align: right; font-weight: 800; color: #0891b2;">{{ $order->posicion_archivo_fisico ?? 'Sin ubicar' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; font-weight: 700; font-size: 11px; text-transform: uppercase;">Estado del Ciclo:</td>
                        <td style="padding: 6px 0; text-align: right; font-weight: 900; color: #0284c7;">{{ $order->estado ?? 'BR REVISION CALIDAD' }}</td>
                    </tr>
                </table>
            </div>

            <div class="btn-container">
                <a href="{{ url('/calidad') }}" class="btn">Ingresar al Portal de Calidad (QA)</a>
            </div>

            <p style="font-size: 11px; color: #94a3b8; text-align: center; margin-top: 24px;">
                Notificación generada automáticamente por {{ $usuarioNombre ?? 'Sistema AUROTRACE' }} el {{ date('d/m/Y H:i') }}.
            </p>
        </div>

        <div class="footer">
            <strong>AUROTRACE System</strong> · Cumplimiento Normativo 21 CFR Part 11 & Res. ICA 062542<br>
            Este mensaje es confidencial y para uso exclusivo del personal autorizado de Calidad.
        </div>
    </div>
</body>
</html>
