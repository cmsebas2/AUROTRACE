# PowerShell script to convert MySQL SQL dump to PostgreSQL SQL dump
$inputFile = "d:\Sebastian\Escritorio\AUROTRACE\aurotrace_codigo\respaldo_aurotrace.sql"
$outputFile = "d:\Sebastian\Escritorio\AUROTRACE\aurotrace_codigo\respaldo_aurotrace_postgres.sql"

$content = [System.IO.File]::ReadAllText($inputFile, [System.Text.Encoding]::UTF8)

# 1. Remove MySQL comment blocks and database declarations
$content = $content -replace "(?m)^/\*!.*?\*/;", ""
$content = $content -replace "(?m)^CREATE DATABASE.*?;", ""
$content = $content -replace "(?m)^USE .*?;", ""
$content = $content -replace "(?m)^-- .*?$", ""

# 2. Convert insert statements backticks to double quotes for Postgres
# We use regex replacement. In PowerShell, we can use [regex]::Replace
$content = [regex]::Replace($content, 'INSERT INTO `([^`]+)`', 'INSERT INTO "$1"')
$content = [regex]::Replace($content, '`([^`]+)`', '"$1"')

# 3. MySQL backslash escapes to Postgres single quote escapes
$content = $content.Replace("\'", "''")

# 4. Remove table locks
$content = $content -replace "(?m)^LOCK TABLES.*?;", ""
$content = $content -replace "(?m)^UNLOCK TABLES;", ""

# Save output file
[System.IO.File]::WriteAllText($outputFile, $content, [System.Text.Encoding]::UTF8)
Write-Output "Conversion completed successfully. Output saved to respaldo_aurotrace_postgres.sql"
