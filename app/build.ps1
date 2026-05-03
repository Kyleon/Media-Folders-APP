# Build de la PWA + abre la carpeta dist lista para subir.
# Uso: .\build.ps1

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

Write-Host "[1/3] Limpiando build anterior..." -ForegroundColor Cyan
if (Test-Path "dist") { Remove-Item -Recurse -Force "dist" }

Write-Host "[2/3] Construyendo..." -ForegroundColor Cyan
& npm run build
if ($LASTEXITCODE -ne 0) { throw "Build falló" }

Write-Host "[3/3] Abriendo carpeta dist..." -ForegroundColor Cyan
$dist = Join-Path $PSScriptRoot "dist"
$size = (Get-ChildItem $dist -Recurse | Measure-Object -Property Length -Sum).Sum
$mb   = [math]::Round($size / 1MB, 2)

Write-Host ""
Write-Host "Build OK." -ForegroundColor Green
Write-Host "Tamaño total: $mb MB" -ForegroundColor Green
Write-Host "Carpeta: $dist" -ForegroundColor Green
Write-Host ""
Write-Host "Sube TODO el contenido de dist/ (no la carpeta dist en sí)" -ForegroundColor Yellow
Write-Host "a la carpeta del subdominio app.yezraelperez.es en Hostinger." -ForegroundColor Yellow

Start-Process explorer $dist
