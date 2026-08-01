# PowerShell script to extract INSERT INTO statements and convert MySQL booleans (1/0) to PostgreSQL booleans (true/false)
$inputFile = "d:\Sebastian\Escritorio\AUROTRACE\aurotrace_codigo\respaldo_aurotrace_postgres.sql"
$outputFile = "d:\Sebastian\Escritorio\AUROTRACE\aurotrace_codigo\respaldo_aurotrace_only_inserts.sql"

$content = [System.IO.File]::ReadAllText($inputFile, [System.Text.Encoding]::UTF8)

# Map of tables and their boolean column names
$booleanColumnsMap = @{
    "product_manufacturing_plans" = @("active")
    "equipment" = @("is_active")
    "items" = @("is_purchased", "is_sold", "is_manufactured", "has_extension", "manages_batches", "batch_assignment", "manages_serial")
    "certificate_of_analyses" = @("is_approved")
    "weighing_and_dispensings" = @("is_verified")
    "quality_inspections" = @("result")
    "manufacturing_records" = @("visual_check")
    "line_clearances" = @("qa_presion_diferencial_conforme")
    "batch_packaging_results" = @("color_conforme", "odor_conforme", "texture_conforme", "particles_free")
    "reconditioning_items" = @("is_external", "is_released")
}

# Regex to match INSERT INTO statements
$matches = [regex]::Matches($content, '(?s)INSERT INTO "([^"]+)" \(([^)]+)\) VALUES\s*(.*?);\s*(?=\r?\n|$)')

$inserts = [System.Collections.Generic.List[string]]::new()
$valRegex = [regex]"'(?:''|[^'])*'|NULL|-?\d+(?:\.\d+)?"

foreach ($match in $matches) {
    $tableName = $match.Groups[1].Value
    $colsStr = $match.Groups[2].Value
    $valsStr = $match.Groups[3].Value
    
    # Skip sessions and migrations tables (migrations are already run and populated)
    if ($tableName -eq "sessions" -or $tableName -eq "migrations") {
        continue
    }
    
    # Parse columns into list
    $cols = @()
    foreach ($c in $colsStr.Split(',')) {
        $cols += $c.Trim().Trim('"')
    }
    
    # Determine boolean column indexes for this table
    $boolIndexes = @()
    if ($booleanColumnsMap.ContainsKey($tableName)) {
        $boolCols = $booleanColumnsMap[$tableName]
        for ($i = 0; $i -lt $cols.Count; $i++) {
            if ($boolCols -contains $cols[$i]) {
                $boolIndexes += $i
            }
        }
    }
    
    # If this table has boolean columns, parse and convert its rows
    if ($boolIndexes.Count -gt 0) {
        $rowMatches = [regex]::Matches($valsStr, '\((.*?)\)(?:,|\s*;)?(?=\s*\(|$)')
        $newRows = @()
        
        foreach ($rm in $rowMatches) {
            $rowValStr = $rm.Groups[1].Value
            $tokens = @()
            foreach ($tok in $valRegex.Matches($rowValStr)) {
                $tokens += $tok.Value
            }
            
            # Convert 1/0 to true/false at boolean indexes
            foreach ($idx in $boolIndexes) {
                if ($idx -lt $tokens.Count) {
                    $val = $tokens[$idx]
                    if ($val -eq "1") {
                        $tokens[$idx] = "true"
                    } elseif ($val -eq "0") {
                        $tokens[$idx] = "false"
                    }
                }
            }
            
            $newRows += "`t(" + [string]::Join(", ", $tokens) + ")"
        }
        
        $newValsStr = [string]::Join(",`r`n", $newRows)
        $stmt = "INSERT INTO `"$tableName`" ($colsStr) VALUES`r`n$newValsStr;"
        $inserts.Add($stmt)
    } else {
        $inserts.Add($match.Value.Trim())
    }
}

# Prepend settings to disable foreign keys
$header = "SET session_replication_role = 'replica';`r`n`r`n"
$footer = "`r`n`r`nSET session_replication_role = 'origin';"

$outputContent = $header + [string]::Join("`r`n`r`n", $inserts) + $footer

# Save output
[System.IO.File]::WriteAllText($outputFile, $outputContent, [System.Text.Encoding]::UTF8)
Write-Output "Extraction completed with type casting. Inserts saved to respaldo_aurotrace_only_inserts.sql"
