<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EBR Master Batch Record</title>
    <style>
        @page {
            size: letter landscape;
            margin: 1cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            color: #000000;
        }
        .page-break {
            page-break-after: always;
        }
        .tabla-rigida {
            width: 100% !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
        }
        /* Ocultar elementos de UI web que pudieran filtrarse */
        .no-print, .btn, .button, button, nav, sidebar, header:not(.doc-header) {
            display: none !important;
        }
        /* Forzar visualización de imágenes para DomPDF */
        img {
            max-width: 100%;
        }
    </style>
</head>
<body>
    <div class="pdf-container">
        @yield('content')
    </div>
</body>
</html>
