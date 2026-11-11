# scripts/dump-code.ps1
# Menggabungkan kode backend jadi SATU file teks untuk dilampirkan ke chat AI.
# Jalankan dari mana saja: powershell -ExecutionPolicy Bypass -File scripts/dump-code.ps1
# Hasil: chatgpt-bundle/KODE_BACKEND.txt (folder di-gitignore). Tidak menyertakan .env, vendor, storage, atau kunci apa pun.

$ErrorActionPreference = 'Stop'
$root    = Split-Path -Parent $PSScriptRoot
$backend = Join-Path $root 'backend'
$out     = Join-Path $root 'chatgpt-bundle'
New-Item -ItemType Directory -Force -Path $out | Out-Null
$target  = Join-Path $out 'KODE_BACKEND.txt'
$utf8    = New-Object System.Text.UTF8Encoding($false)

# Hanya folder/file ini (daftar putih). *.php untuk folder; file tunggal diambil apa adanya.
$items = @('app', 'routes', 'database/migrations', 'database/seeders', 'tests', 'resources/views',
           'config/marketplace.php', 'phpunit.xml', 'composer.json')

$sb = New-Object System.Text.StringBuilder
$count = 0
foreach ($item in $items) {
    $path = Join-Path $backend $item
    if (-not (Test-Path $path)) { continue }
    if ((Get-Item $path).PSIsContainer) {
        $files = Get-ChildItem -Path $path -Recurse -File | Where-Object { $_.Extension -in '.php' } | Sort-Object FullName
    } else {
        $files = @(Get-Item $path)
    }
    foreach ($f in $files) {
        $rel = $f.FullName.Substring($backend.Length + 1).Replace('\', '/')
        [void]$sb.AppendLine("=== FILE: backend/$rel ===")
        [void]$sb.AppendLine([System.IO.File]::ReadAllText($f.FullName, $utf8))
        $count++
    }
}
[System.IO.File]::WriteAllText($target, $sb.ToString(), $utf8)
$kb = [math]::Round((Get-Item $target).Length / 1KB, 1)
Write-Host "$count file digabung -> $target ($kb KB)"
