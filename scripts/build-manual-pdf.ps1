<#
.SYNOPSIS
  Convierte docs/manual.html a docs/manual.pdf usando Chrome o Edge headless.

.DESCRIPTION
  Detecta automaticamente Chrome o Edge instalado y los usa con --print-to-pdf
  para generar un PDF identico al que verias imprimiendo desde el navegador.
  No requiere dependencias externas.

.EXAMPLE
  .\scripts\build-manual-pdf.ps1
#>

[CmdletBinding()]
param()

$ErrorActionPreference = "Stop"
$repoRoot = Split-Path -Parent $PSScriptRoot
$inputHtml = Join-Path $repoRoot "docs\manual.html"
$outputPdf = Join-Path $repoRoot "docs\manual.pdf"

if (-not (Test-Path $inputHtml)) {
    throw "No se encuentra $inputHtml"
}

# Buscar Edge primero (mejor con headless en Windows), luego Chrome
$candidates = @(
    "$env:ProgramFiles\Microsoft\Edge\Application\msedge.exe",
    "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe",
    "$env:LOCALAPPDATA\Microsoft\Edge\Application\msedge.exe",
    "$env:ProgramFiles\Google\Chrome\Application\chrome.exe",
    "${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe",
    "$env:LOCALAPPDATA\Google\Chrome\Application\chrome.exe"
)

$browser = $null
foreach ($p in $candidates) {
    if (Test-Path $p) { $browser = $p; break }
}

if (-not $browser) {
    throw "No se encuentra Chrome ni Edge instalado. Instala uno y reintenta."
}

Write-Host "Usando: $browser" -ForegroundColor Cyan
Write-Host "Generando PDF..." -ForegroundColor Cyan

# file:// URL — Windows requiere triple slash
$fileUri = "file:///" + $inputHtml.Replace('\', '/')

$tmpProfile = Join-Path $env:TEMP ("chrome_pdf_" + [guid]::NewGuid().ToString())

$args = @(
    '--headless=new'
    '--disable-gpu'
    '--no-sandbox'
    '--disable-dev-shm-usage'
    '--no-first-run'
    '--no-default-browser-check'
    '--disable-extensions'
    '--user-data-dir=' + $tmpProfile
    '--virtual-time-budget=8000'
    '--print-to-pdf=' + $outputPdf
    '--print-to-pdf-no-header'
    $fileUri
)

# Eliminar PDF previo
if (Test-Path $outputPdf) { Remove-Item $outputPdf -Force }

# Lanzamos via Start-Process para que el stderr de Chrome (registration_request)
# no contamine el output de PowerShell. Esperamos a que termine.
Start-Process -FilePath $browser -ArgumentList $args -Wait `
    -WindowStyle Hidden `
    -RedirectStandardOutput "$env:TEMP\chrome_pdf_out.log" `
    -RedirectStandardError  "$env:TEMP\chrome_pdf_err.log" | Out-Null
Remove-Item "$env:TEMP\chrome_pdf_out.log","$env:TEMP\chrome_pdf_err.log" -ErrorAction SilentlyContinue

# Limpieza del perfil temporal
if (Test-Path $tmpProfile) {
    Remove-Item $tmpProfile -Recurse -Force -ErrorAction SilentlyContinue
}

if (-not (Test-Path $outputPdf)) {
    throw "No se genero el PDF. Comprueba que $browser funciona."
}

$size = (Get-Item $outputPdf).Length
$kb = [Math]::Round($size / 1KB, 1)
Write-Host ""
Write-Host "PDF generado: $outputPdf" -ForegroundColor Green
Write-Host "Tamano: $kb KB" -ForegroundColor DarkGray
