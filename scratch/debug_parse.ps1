# Diagnostic script to see why equipment table boolean values did not get replaced
$inputFile = "d:\Sebastian\Escritorio\AUROTRACE\aurotrace_codigo\respaldo_aurotrace_postgres.sql"
$content = [System.IO.File]::ReadAllText($inputFile, [System.Text.Encoding]::UTF8)

$matches = [regex]::Matches($content, '(?s)INSERT INTO "([^"]+)" \(([^)]+)\) VALUES\s*(.*?);\s*(?=\r?\n|$)')

Write-Output "Total matches: $($matches.Count)"

foreach ($match in $matches) {
    $tableName = $match.Groups[1].Value
    if ($tableName -eq "equipment") {
        Write-Output "Found equipment table!"
        Write-Output "Columns: $($match.Groups[2].Value)"
        Write-Output "Values raw: $($match.Groups[3].Value)"
        
        $cols = @()
        foreach ($c in $match.Groups[2].Value.Split(',')) {
            $cols += $c.Trim().Trim('"')
        }
        
        $boolCols = @("is_active")
        $boolIndexes = @()
        for ($i = 0; $i -lt $cols.Count; $i++) {
            if ($boolCols -contains $cols[$i]) {
                $boolIndexes += $i
            }
        }
        
        Write-Output "is_active column index: $boolIndexes"
        
        $rowMatches = [regex]::Matches($match.Groups[3].Value, '\((.*?)\)(?:,|\s*;)?(?=\s*\(|$)')
        Write-Output "Total rows parsed: $($rowMatches.Count)"
        
        $valRegex = [regex]"'(?:''|[^'])*'|NULL|-?\d+(?:\.\d+)?"
        foreach ($rm in $rowMatches) {
            $rowValStr = $rm.Groups[1].Value
            $tokens = @()
            foreach ($tok in $valRegex.Matches($rowValStr)) {
                $tokens += $tok.Value
            }
            Write-Output "Row parsed values: $([string]::Join(' | ', $tokens))"
        }
    }
}
