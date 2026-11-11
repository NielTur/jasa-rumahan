# scripts/bundle-chatgpt.ps1
# Menggabungkan dokumen proyek jadi 3 file untuk ChatGPT (batas 3 file per chat/Project).
# Jalankan dari mana saja: powershell -ExecutionPolicy Bypass -File scripts/bundle-chatgpt.ps1
# Hasil: chatgpt-bundle/ di root repo (sudah di-gitignore). Jangan edit hasilnya; edit file sumber lalu jalankan ulang.

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$out  = Join-Path $root 'chatgpt-bundle'
New-Item -ItemType Directory -Force -Path $out | Out-Null

# Urutan file di dalam tiap bundel = urutan baca yang diinginkan.
$bundles = [ordered]@{
    '1_ATURAN_STATUS_RENCANA.md'  = @('AI_RULES.md', 'docs/PROGRESS.md', 'docs/PLAN_LANJUTAN.md')
    '2_KONTRAK_API.md'            = @('docs/API_CONTRACT.md')
    '3_KEPUTUSAN_DAN_DESAIN.md'   = @('docs/DECISIONS.md', 'docs/Dokumentasi_Sistem_Marketplace_Jasa_Rumahan.md')
}

$utf8 = New-Object System.Text.UTF8Encoding($false)   # UTF-8 tanpa BOM, aman untuk karakter Indonesia dan panah

foreach ($name in $bundles.Keys) {
    $sb = New-Object System.Text.StringBuilder
    [void]$sb.AppendLine("<!-- Bundel ChatGPT: $name. Dibuat otomatis oleh scripts/bundle-chatgpt.ps1, jangan diedit. -->")
    foreach ($rel in $bundles[$name]) {
        $path = Join-Path $root $rel
        if (-not (Test-Path $path)) { throw "File sumber tidak ditemukan: $rel" }
        [void]$sb.AppendLine('')
        [void]$sb.AppendLine("<!-- ===== FILE: $rel ===== -->")
        [void]$sb.AppendLine('')
        [void]$sb.AppendLine([System.IO.File]::ReadAllText($path, $utf8))
    }
    $target = Join-Path $out $name
    [System.IO.File]::WriteAllText($target, $sb.ToString(), $utf8)
    $kb = [math]::Round((Get-Item $target).Length / 1KB, 1)
    Write-Host ("{0}  ({1} KB)" -f $name, $kb)
}
Write-Host "Selesai. Unggah 3 file di $out ke Project ChatGPT (hapus versi lama dulu)."
