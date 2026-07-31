<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Batch Record - {{ $op->lote }}</title>
    <style>
        @page { margin: 100px 25px; }
        header { position: fixed; top: -80px; left: 0px; right: 0px; height: 100px; text-align: center; border-bottom: 2px solid #000; }
        footer { position: fixed; bottom: -60px; left: 0px; right: 0px; height: 50px; text-align: center; font-size: 10px; color: #555; border-top: 1px solid #ccc; padding-top: 10px; }
        .page-number:after { content: counter(page); }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; line-height: 1.4; color: #333; }
        
        .container { width: 100%; }
        .row { width: 100%; clear: both; }
        .col { float: left; }
        .w-50 { width: 50%; }
        .w-100 { width: 100%; }
        
        .logo { height: 50px; float: right; }
        .title { text-align: left; float: left; }
        
        .section-title { background-color: #f3f4f6; padding: 5px 10px; font-weight: bold; text-transform: uppercase; border-left: 4px solid #1e40af; margin: 20px 0 10px 0; font-size: 13px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table, th, td { border: 1px solid #ddd; }
        th { background-color: #f9fafb; padding: 8px; text-align: left; font-size: 10px; text-transform: uppercase; }
        td { padding: 8px; vertical-align: top; font-size: 11px; }
        
        .text-bold { font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .mt-4 { margin-top: 20px; }
        
        .alert { color: #dc2626; border: 1px solid #fecaca; background-color: #fef2f2; padding: 5px; font-size: 10px; }
        .signature-box { border: 1px solid #000; padding: 10px; margin-top: 5px; font-size: 10px; }
        .meaning { font-style: italic; color: #666; }
    </style>
</head>
<body>
    <header>
        <div class="row">
            <div class="title" style="width: 70%;">
                <h2 style="margin: 0; color: #1e40af;">AUROFARMA S.A.S.</h2>
                <h3 style="margin: 0; font-weight: normal;">EXPEDIENTE ELECTRÓNICO DE LOTE (EBR)</h3>
            </div>
            <div style="width: 30%; float: right; text-align: right;">
                <img src="{{ public_path('img/logo.png') }}" class="logo">
            </div>
        </div>
    </header>

    <footer>
        AUROFARMA ERP - GENEALOGÍA 360° - LOTE: {{ $op->lote }} - Fecha de Impresión: {{ date('d/m/Y H:i') }} - Página <span class="page-number"></span>
    </footer>

    <div class="container">
        <!-- DATOS GENERALES -->
        <div class="section-title">1. Información General del Lote</div>
        <table>
            <tr>
                <th width="25%">Producto</th>
                <td width="25%" class="text-bold">{{ $op->product->name }}</td>
                <th width="25%">Número de Lote</th>
                <td width="25%" class="text-bold">{{ $op->lote }}</td>
            </tr>
            <tr>
                <th>Tamaño del Lote</th>
                <td>{{ number_format($op->bulk_size_kg, 2) }} {{ $op->unit }}</td>
                <th>Estado Final</th>
                <td><span class="text-bold">{{ $op->status }}</span></td>
            </tr>
            <tr>
                <th>Fecha Manufactura</th>
                <td>{{ \Carbon\Carbon::parse($op->manufacturing_date)->format('d/m/Y') }}</td>
                <th>Fecha Expiración</th>
                <td>{{ \Carbon\Carbon::parse($op->expiration_date)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <th>Rendimiento Final</th>
                <td class="text-bold @if($op->final_yield_percentage < 90 || $op->final_yield_percentage > 110) alert @endif">
                    {{ number_format($op->final_yield_percentage, 2) }}%
                </td>
                <th>Unidades Obtenidas</th>
                <td>{{ number_format($op->packagingResult->units_obtained ?? 0, 0) }} UN</td>
            </tr>
        </table>

        <!-- SUMINISTROS -->
        <div class="section-title">2. Trazabilidad de Materias Primas</div>
        <table>
            <thead>
                <tr>
                    <th>Componente / Material</th>
                    <th>Lote del Proveedor</th>
                    <th width="15%" class="text-right">Cant. Teórica</th>
                    <th width="15%" class="text-right">Cant. Real</th>
                    <th width="10%">Unidad</th>
                </tr>
            </thead>
            <tbody>
                @foreach($op->dispensing->dispensingDetails as $detail)
                <tr>
                    <td>{{ $detail->formulaIngredient->material_name }}</td>
                    <td class="text-bold">{{ $detail->lote_mp }}</td>
                    <td class="text-right">{{ number_format($detail->cantidad_teorica, 3) }}</td>
                    <td class="text-right text-bold">{{ number_format($detail->cantidad_real, 3) }}</td>
                    <td>{{ $detail->formulaIngredient->unit }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- MANUFACTURA -->
        <div class="section-title">3. Registro de Operaciones (Manufactura)</div>
        @foreach($op->manufacturingExecutions->where('step_type', '!=', 'INGREDIENTE') as $exec)
        <div style="margin-bottom: 10px; border: 1px solid #eee; padding: 10px;">
            <div style="font-weight: bold; color: #1e40af; border-bottom: 1px solid #1e40af; margin-bottom: 5px;">
                {{ $exec->planStep->description }}
            </div>
            <table style="border: none; margin-bottom: 0;">
                <tr style="border: none;">
                    <td style="border: none; width: 50%;">
                        <strong>Inicio:</strong> {{ $exec->start_time }} | <strong>Fin:</strong> {{ $exec->end_time }}<br>
                        <strong>RPM:</strong> {{ $exec->rpm ?: 'N/A' }} | <strong>IPC:</strong> {{ $exec->ipc_result ?: 'N/A' }}
                    </td>
                    <td style="border: none; width: 50%;">
                        <div class="signature-box">
                            <strong>FIRMADO ELECTRÓNICAMENTE POR (REALIZÓ):</strong><br>
                            {{ $exec->user->name }} - {{ $exec->signed_at->format('d/m/Y H:i') }}<br>
                            <span class="meaning">Significado: Ejecución técnica conformada</span>
                        </div>
                        @if($exec->qaUser)
                        <div class="signature-box" style="margin-top: 5px; border-color: #1e40af;">
                            <strong>VERIFICACIÓN DE CALIDAD (VERIFICÓ):</strong><br>
                            {{ $exec->qaUser->name }} - {{ $exec->qa_verified_at->format('d/m/Y H:i') }}<br>
                            <span class="meaning">Significado: Revisión de integridad documental realizada</span>
                        </div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
        @endforeach

        <!-- AUDIT TRAIL -->
        <div style="page-break-before: always;"></div>
        <div class="section-title">4. Trazabilidad Forense (Audit Trail Completo)</div>
        <p style="font-size: 9px; color: #666; margin-bottom: 10px;">
            Este listado contiene todos los eventos críticos, desviaciones y firmas registradas para este lote, en cumplimiento con el estándar 21 CFR Part 11.
        </p>
        <table>
            <thead>
                <tr>
                    <th width="15%">Fecha/Hora</th>
                    <th width="15%">Acción</th>
                    <th width="35%">Justificación / Observación</th>
                    <th width="15%">Firmante</th>
                    <th width="20%">En nombre de</th>
                </tr>
            </thead>
            <tbody>
                @foreach($auditLogs as $log)
                <tr @if($log->is_alert) style="background-color: #fff1f2;" @endif>
                    <td style="font-size: 9px;">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                    <td class="text-bold" style="font-size: 9px;">{{ $log->action }}</td>
                    <td style="font-size: 9px;">
                        @if($log->is_alert) <span class="alert">ALERTA:</span> @endif
                        {{ $log->reason ?: 'Evento de proceso verificado.' }}
                        @if($log->justification)
                            <div style="margin-top: 3px; font-style: italic; color: #dc2626;">Justificación: {{ $log->justification }}</div>
                        @endif
                    </td>
                    <td style="font-size: 9px;">{{ App\Models\User::find($log->signer_id)->name ?? 'SISTEMA' }}</td>
                    <td style="font-size: 9px;">{{ App\Models\User::find($log->on_behalf_of_id)->name ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- CERTIFICACIÓN FINAL -->
        <div class="mt-4" style="border: 2px solid #000; padding: 20px; background-color: #f8fafc;">
            <h3 style="margin-top: 0; text-align: center;">CERTIFICACIÓN FINAL DE CALIDAD</h3>
            <p style="text-align: justify; font-size: 10px;">
                Yo, el abajo firmante, certifico que los registros contenidos en este Batch Record han sido revisados y se encuentran en cumplimiento con las Buenas Prácticas de Manufactura (BPM) y los estándares de AUROFARMA. El producto ha sido evaluado técnica y analíticamente para su liberación al mercado.
            </p>
            <div style="width: 300px; margin: 20px auto; border-top: 1px solid #000; text-align: center; padding-top: 5px;">
                @php $releaseLog = $auditLogs->where('action', 'Lote Liberado y Certificado')->last(); @endphp
                @if($releaseLog)
                    <strong>{{ App\Models\User::find($releaseLog->on_behalf_of_id)->name }}</strong><br>
                    <span>Responsable de Calidad</span><br>
                    <span style="font-size: 9px;">Fecha de Firma: {{ $releaseLog->created_at->format('d/m/Y H:i:s') }}</span><br>
                    <span style="font-size: 8px; color: #555;">ID Único de Firma (SHA-256): {{ hash('sha256', $releaseLog->id . $op->lote) }}</span>
                @else
                    <span style="color: #999; font-style: italic;">Pendiente de Liberación por QA</span>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
