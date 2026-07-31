<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta de Reintegración - Aurofarma S.A.S.</title>
    <style>
        @page { margin: 40px; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #333; line-height: 1.4; margin: 0; padding: 0; }
        
        .header { width: 100%; border-bottom: 2px solid #0A2540; padding-bottom: 15px; margin-bottom: 20px; }
        .header table { width: 100%; border-collapse: collapse; }
        .logo { width: 180px; }
        .main-title { font-size: 15px; font-weight: bold; color: #0A2540; text-align: right; text-transform: uppercase; }
        
        .section-header { background-color: #0A2540; color: white; padding: 6px 10px; font-weight: bold; font-size: 10px; margin-top: 25px; text-transform: uppercase; letter-spacing: 1px; }
        
        table.data-table { width: 100%; border-collapse: collapse; border: 1px solid #0A2540; margin-top: 0; }
        table.data-table th, table.data-table td { border: 1px solid #e5e7eb; padding: 8px 12px; text-align: left; }
        table.data-table th { background-color: #f9fafb; font-weight: bold; width: 35%; color: #4b5563; font-size: 9px; text-transform: uppercase; }
        
        .label-val { font-weight: bold; color: #000; font-size: 10px; }
        .highlight { color: #0A2540; font-weight: 800; }
        
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 8px; }
        
        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-weight: bold; text-transform: uppercase; font-size: 9px; }
        .status-pt { background-color: #dcfce7; color: #166534; }
        .status-rz { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('img/logo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoBase64 = 'data:image/png;base64,' . $logoData;
        }
    @endphp

    <div class="header">
        <table>
            <tr>
                <td style="width: 50%;">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" class="logo">
                    @else
                        <div style="font-weight: 800; color: #0A2540; font-size: 20px; letter-spacing: -1px;">AUROFARMA <span style="font-weight: 300;">S.A.S.</span></div>
                    @endif
                </td>
                <td class="main-title">
                    {{ $item->destination_warehouse === 'RZ' ? 'ACTA DE TRASLADO A RECHAZO' : 'ACTA DE REINTEGRACIÓN A P.T.' }}<br>
                    <span style="font-size: 10px; color: #6b7280; font-weight: normal;">EXPEDIENTE ELECTRÓNICO AUROTRACE</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- BLOQUE 1: TRAZABILIDAD -->
    <div class="section-header">BLOQUE 1: TRAZABILIDAD Y DOCUMENTACIÓN</div>
    <table class="data-table">
        <tr>
            <th>Número Traslado de Ingreso (Siesa)</th>
            <td class="label-val highlight">{{ $item->transfer_number }}</td>
        </tr>
        <tr>
            <th>Número Traslado de Salida (Siesa)</th>
            <td class="label-val">{{ $item->exit_transfer_number ?: 'GESTIÓN MANUAL / PENDIENTE' }}</td>
        </tr>
        <tr>
            <th>Proceso de Ingreso</th>
            <td>{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') : '--' }}</td>
        </tr>
        <tr>
            <th>Cierre y Liberación</th>
            <td class="label-val">{{ $item->released_at ? \Carbon\Carbon::parse($item->released_at)->format('d/m/Y H:i') : '--' }}</td>
        </tr>
        <tr>
            <th>Bodega de Destino</th>
            <td>
                <span class="status-badge {{ $item->destination_warehouse === 'RZ' ? 'status-rz' : 'status-pt' }}">
                    {{ $item->destination_warehouse === 'RZ' ? 'RECHAZO (RZ)' : 'PRODUCTO TERMINADO (PT)' }}
                </span>
            </td>
        </tr>
    </table>

    <!-- BLOQUE 2: ESPECIFICACIONES TÉCNICAS -->
    <div class="section-header">BLOQUE 2: ESPECIFICACIONES TÉCNICAS DEL PRODUCTO</div>
    <table class="data-table">
        <tr>
            <th>Código de Ítem</th>
            <td class="label-val highlight">{{ $item->item_code }}</td>
        </tr>
        <tr>
            <th>Descripción Completa</th>
            <td style="font-size: 9px; font-weight: bold;">{{ $item->item ? $item->item->description : 'N/A' }}</td>
        </tr>
        <tr>
            <th>Número de Lote</th>
            <td class="label-val" style="font-family: 'Courier New', monospace; font-size: 11px;">{{ $item->lot_number }}</td>
        </tr>
        <tr>
            <th>Fecha de Vencimiento</th>
            <td class="label-val">{{ $item->expiration_date ? \Carbon\Carbon::parse($item->expiration_date)->format('d/m/Y') : 'N/A' }}</td>
        </tr>
        <tr>
            <th>Fabricante</th>
            <td>{{ $item->manufacturer ?: 'AUROFARMA S.A.S.' }}</td>
        </tr>
        <tr>
            <th>Cantidad Liberada Total</th>
            <td class="label-val highlight" style="font-size: 13px;">{{ number_format($item->quantity, 2) }} {{ $item->uom }}</td>
        </tr>
    </table>

    <!-- BLOQUE 3: BALANCE Y ACTIVIDADES -->
    <div class="section-header">BLOQUE 3: BALANCE DE MATERIALES Y ACTIVIDADES</div>
    <table class="data-table">
        <tr style="background-color: #f3f4f6;">
            <th style="width: 50%; text-align: center;">INSUMO / REQUERIMIENTO</th>
            <th style="width: 50%; text-align: center;">CONSUMO REAL (CIERRE)</th>
        </tr>
        <tr>
            <td>
                <div style="font-weight: bold;">Etiquetas: <span style="color: #6b7280;">{{ $item->req_label ?? 0 }} UND</span></div>
                <div style="font-weight: bold; margin-top: 4px;">Plegadizas: <span style="color: #6b7280;">{{ $item->req_box ?? 0 }} UND</span></div>
            </td>
            <td style="text-align: center; vertical-align: middle;">
                <div class="label-val" style="font-size: 11px;">{{ $item->used_labels ?? 0 }} ETIQUETAS</div>
                <div class="label-val" style="font-size: 11px; margin-top: 4px;">{{ $item->used_boxes ?? 0 }} PLEGADIZAS</div>
            </td>
        </tr>
        <tr>
            <th>Actividades / Otros Materiales</th>
            <td style="min-height: 80px; vertical-align: top; font-size: 9px; line-height: 1.6;">
                @php
                    $obs = $item->observations ?: 'PROCESO ESTÁNDAR REALIZADO SEGÚN PROTOCOLO DE REACONDICIONAMIENTO.';
                @endphp
                {{ $obs }}
            </td>
        </tr>
    </table>

    @if($item->destination_warehouse === 'RZ')
        <div class="section-header" style="background-color: #991b1b;">EVIDENCIA TÉCNICA DE RECHAZO</div>
        <table class="data-table" style="border-color: #991b1b;">
            <tr>
                <th style="color: #991b1b; width: 30%;">Motivo</th>
                <td style="color: #991b1b; font-weight: bold; font-size: 11px;">{{ $item->rejection_reason }}</td>
            </tr>
        </table>
        @if($item->rejection_photo_path)
            <div style="text-align: center; margin-top: 20px;">
                <img src="{{ storage_path('app/public/' . $item->rejection_photo_path) }}" style="max-height: 300px; border: 2px solid #fee2e2; border-radius: 8px;">
            </div>
        @endif
    @endif

    <!-- FIRMAS Y RESPONSABILIDAD -->
    <div style="margin-top: 40px; width: 100%;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50%; padding-top: 10px; text-align: center;">
                    <!-- Sello Digital CFR 21 -->
                    <div style="border: 2px solid #0A2540; padding: 10px; margin-bottom: 5px; background-color: #f8fafc; text-align: center; position: relative;">
                        <div style="position: absolute; top: 2px; right: 2px; font-size: 6px; color: #0A2540; font-weight: bold; border: 1px solid #0A2540; padding: 1px 3px; border-radius: 2px;">CFR 21 P11</div>
                        <div style="font-size: 8px; color: #64748b; margin-bottom: 2px; text-transform: uppercase; font-weight: bold; letter-spacing: 1px;">Sello Digital de Validación</div>
                        <div style="font-weight: 900; font-size: 11px; color: #0A2540; text-transform: uppercase;">{{ $item->releasedBy ? $item->releasedBy->name : 'PENDIENTE' }}</div>
                        <div style="font-size: 8px; color: #0A2540; margin-top: 2px; font-family: 'Courier New', monospace;">{{ $item->released_at ? $item->released_at->format('Y-m-d H:i:s') : '--' }}</div>
                        <div style="font-size: 6px; color: #94a3b8; margin-top: 4px; font-style: italic;">Firmado electrónicamente mediante AuroTrace Security Protocol</div>
                    </div>
                    <div style="border-top: 1px solid #0A2540; padding-top: 5px;">
                        <div style="font-weight: bold; font-size: 10px; text-transform: uppercase;">
                            {{ $item->releasedBy ? $item->releasedBy->name : 'ANALISTA DE PRODUCCIÓN' }}
                        </div>
                        <div style="font-size: 9px; color: #4b5563;">Responsable de Proceso</div>
                    </div>
                </td>
                <td style="width: 10%;">&nbsp;</td>
                <td style="width: 40%; border-top: 1px solid #0A2540; padding-top: 10px; text-align: center; vertical-align: bottom;">
                    <div style="font-weight: bold; font-size: 10px; text-transform: uppercase;">
                        DIRECTOR TÉCNICO
                    </div>
                    <div style="font-size: 9px; color: #4b5563;">Verificación / Liberación Final</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Aurofarma S.A.S. | Documento Digital Auditado | AuroTrace ERP v2 | {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
