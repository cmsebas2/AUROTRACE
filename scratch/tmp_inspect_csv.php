<?php
$lines = file('d:/Sebastian/Escritorio/AUROTRACE/aurotrace_codigo/lotes.csv');
echo "Total lines: " . count($lines) . "\n";

$headers = [];
foreach ($lines as $idx => $line) {
    if (trim($line) === '') continue;
    // Check if line looks like a header (contains letters and commas without many numbers at start)
    if (preg_match('/^[A-Z_áéíóúÁÉÍÓÚ\s¿?\(\)\/#,\.\-]+$/i', trim($line)) && !preg_match('/^\d+/', trim($line))) {
        $headers[] = ($idx + 1) . ": " . substr(trim($line), 0, 100);
    }
}

echo "Found potential headers:\n";
print_r(array_slice($headers, 0, 30));
