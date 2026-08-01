# Search the codebase for references to Reconditioning or reacondicionamiento
$dir = "d:\Sebastian\Escritorio\AUROTRACE\aurotrace_codigo"
Get-ChildItem -Path $dir -Recurse -Include *.php,*.json -Exclude vendor | ForEach-Object {
    $content = Get-Content $_.FullName -ErrorAction SilentlyContinue
    if ($content) {
        for ($i = 0; $i -lt $content.Count; $i++) {
            if ($content[$i] -match 'reconditioning|Reconditioning|reacondicionamiento') {
                Write-Output "$($_.FullName.Replace($dir, '')) (Line $($i + 1)): $($content[$i].Trim())"
            }
        }
    }
}
