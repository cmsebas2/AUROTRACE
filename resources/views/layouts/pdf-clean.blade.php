<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EBR Master - Aurofarma</title>
    <style>
        @page {
            size: letter landscape;
            margin: 0.5cm 1.5cm; /* Margen oficial Johann v11 */
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt; /* Fuente de datos */
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            color: #000000;
            line-height: 1.0;
        }
        
        .master-container {
            width: 100%;
            margin: 0;
            padding: 0;
        }
        
        .page-break {
            page-break-after: always;
            clear: both;
        }
        
        /* Estructura Rígida Industrial */
        table {
            width: 100% !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            border: 1px solid black !important;
            margin-bottom: 0 !important; /* Elimina espacio entre tablas */
        }
        
        th, td { 
            border: 1px solid black !important;
            padding: 1px 2px !important; 
            text-align: center !important; 
            vertical-align: middle !important;
            height: 14px !important; /* Altura compacta */
            overflow: hidden;
        }
        
        .txt-bold { font-weight: bold !important; font-size: 9pt; } /* Títulos un poco más grandes */
        .bg-gris { background-color: #D9D9D9 !important; font-weight: bold !important; }
        
        .txt-left { text-align: left !important; }
        .description-cell { text-align: left !important; }
        
        img { max-height: 40px !important; }

        /* Ocultamiento absoluto de UI web */
        .no-print, .btn, button, nav, sidebar, footer {
            display: none !important;
        }
    </style>
</head>
<body>
    <div class="master-container">
        @yield('content')
    </div>
</body>
</html>
