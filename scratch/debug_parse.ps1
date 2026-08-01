# Parse the user_message.json file and output details
$json = Get-Content "d:\Sebastian\Escritorio\AUROTRACE\aurotrace_codigo\scratch\user_message.json" -Raw -Encoding UTF8 | ConvertFrom-Json
Write-Output "Type: $($json.type)"
Write-Output "Content Length: $($json.content.Length)"
$lines = $json.content -split "`n"
Write-Output "Total Lines: $($lines.Count)"
for ($i = 0; $i -lt 10; $i++) {
    Write-Output "Line `${i}: $($lines[$i])"
}
