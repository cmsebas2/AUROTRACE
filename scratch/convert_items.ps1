# Load user message and parse items to SQL
$json = Get-Content "d:\Sebastian\Escritorio\AUROTRACE\aurotrace_codigo\scratch\user_message.json" -Raw -Encoding UTF8 | ConvertFrom-Json
$content = $json.content
$lines = $content -split "`n"

$sqlLines = @()
$sqlLines += "-- Import of updated items"
$sqlLines += "BEGIN;"

$count = 0
foreach ($line in $lines) {
    # Skip header or empty lines
    if ($line -like "*Item*Referencia*" -or [string]::IsNullOrWhiteSpace($line) -or $line -like "*USER_REQUEST*") {
        continue
    }
    
    # Parse tab-separated values
    $parts = $line -split "`t"
    if ($parts.Count -lt 4) {
        # Try splitting by multiple spaces if tabs aren't captured correctly
        $parts = $line -split "\s{2,}"
    }
    
    if ($parts.Count -ge 4) {
        $itemCode = $parts[0].Trim()
        $reference = $parts[1].Trim()
        $description = $parts[2].Trim().Replace("'", "''") # Escape SQL single quotes
        $uom = $parts[3].Trim()
        
        # Build standard PostgreSQL UPSERT query
        $sql = "INSERT INTO `"items`" (`"item_code`", `"reference`", `"description`", `"inventory_uom`", `"created_at`", `"updated_at`") " +
               "VALUES ('$itemCode', '$reference', '$description', '$uom', NOW(), NOW()) " +
               "ON CONFLICT (`"item_code`") DO UPDATE SET " +
               "`"reference`" = EXCLUDED.`"reference`", `"description`" = EXCLUDED.`"description`", `"inventory_uom`" = EXCLUDED.`"inventory_uom`", `"updated_at`" = NOW();"
               
        # Replace backticks with double quotes for PostgreSQL
        $sql = $sql.Replace("`"", '"')
        $sqlLines += $sql
        $count++
    }
}

$sqlLines += "COMMIT;"
$sqlLines | Out-File "d:\Sebastian\Escritorio\AUROTRACE\aurotrace_codigo\scratch\import_items.sql" -Encoding UTF8
Write-Output "Successfully generated $count UPSERT queries in scratch\import_items.sql"
