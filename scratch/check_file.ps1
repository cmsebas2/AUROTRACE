# Search migrations for any modifications to the products table
$dir = "d:\Sebastian\Escritorio\AUROTRACE\aurotrace_codigo\database\migrations"
Get-ChildItem -Path $dir -Filter *.php | ForEach-Object {
    $content = Get-Content $_.FullName
    for ($i = 0; $i -lt $content.Count; $i++) {
        if ($content[$i] -like '*Schema::table("products"*' -or $content[$i] -like "*Schema::table('products'*") {
            Write-Output "$($_.Name) (Line $($i + 1)): $($content[$i].Trim())"
        }
    }
}
