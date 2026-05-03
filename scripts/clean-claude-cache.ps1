# Limpia las carpetas regenerables de ~/.claude/ para reducir el peso
# que se sincroniza por Dropbox/OneDrive/Drive.
#
# Solo borra lo que Claude Code regenera automaticamente cuando lo necesita.
# Las sesiones, proyectos, memorias y configuracion NO se tocan.
#
# Uso:
#   .\clean-claude-cache.ps1            # muestra que borraria sin hacer nada
#   .\clean-claude-cache.ps1 -Run       # ejecuta la limpieza

param(
    [switch]$Run
)

$ErrorActionPreference = "SilentlyContinue"

$claude = "$env:USERPROFILE\.claude"
if (-not (Test-Path $claude)) { throw "No existe $claude" }

$cleanable = @(
    "cache",
    "debug",
    "downloads",
    "telemetry",
    "shell-snapshots",
    "file-history",
    "session-env"
)

$totalBytes = 0
$summary = @()

foreach ($name in $cleanable) {
    $path = Join-Path $claude $name
    if (-not (Test-Path $path)) { continue }
    $size = (Get-ChildItem $path -Recurse -File -ErrorAction SilentlyContinue | Measure-Object -Property Length -Sum).Sum
    if (-not $size) { $size = 0 }
    $totalBytes += $size
    $summary += [PSCustomObject]@{
        Carpeta = $name
        SizeMB  = [math]::Round($size / 1MB, 1)
        Path    = $path
    }
}

$summary | Format-Table -AutoSize

$totalMB = [math]::Round($totalBytes / 1MB, 1)
Write-Host ""
Write-Host "Total liberable: $totalMB MB" -ForegroundColor Cyan

if (-not $Run) {
    Write-Host ""
    Write-Host "Previsualizacion. Ejecuta con -Run para borrar." -ForegroundColor Yellow
    exit 0
}

Write-Host ""
Write-Host "Borrando..." -ForegroundColor Yellow
foreach ($row in $summary) {
    if (Test-Path $row.Path) {
        Get-ChildItem $row.Path -Recurse -Force | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue
        Write-Host ("  OK " + $row.Carpeta) -ForegroundColor Green
    }
}

Write-Host ""
Write-Host "Hecho. $totalMB MB liberados." -ForegroundColor Green
Write-Host "Claude Code regenerara lo que necesite automaticamente." -ForegroundColor DarkGray
