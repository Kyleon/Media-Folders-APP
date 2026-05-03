# Redirige C:\Users\<usuario>\.claude\projects\ a una carpeta dentro de Dropbox
# usando un junction de Windows (no requiere permisos de administrador).
#
# Resultado: los chats y memorias de Claude Code se sincronizan automáticamente
# entre todos los equipos donde tengas Dropbox + este script aplicado.
#
# Uso:
#   .\sync-claude-to-dropbox.ps1                    # detecta Dropbox automáticamente
#   .\sync-claude-to-dropbox.ps1 -DropboxRoot "C:\Users\yezra\Dropbox"
#   .\sync-claude-to-dropbox.ps1 -Revert            # deshace el junction y restaura

param(
    [string]$DropboxRoot = "",
    [switch]$Revert
)

$ErrorActionPreference = "Stop"

# ── Localizar la carpeta de Dropbox ────────────────────────────────────
function Get-DropboxRoot {
    # 1. Si el usuario lo pasó como parámetro
    if ($DropboxRoot) { return $DropboxRoot }

    # 2. Buscar el info.json oficial de Dropbox
    $info = "$env:LOCALAPPDATA\Dropbox\info.json"
    if (Test-Path $info) {
        $j = Get-Content $info -Raw | ConvertFrom-Json
        if ($j.personal -and $j.personal.path) { return $j.personal.path }
        if ($j.business -and $j.business.path) { return $j.business.path }
    }

    # 3. Asumir la ruta clásica
    $candidate = "$env:USERPROFILE\Dropbox"
    if (Test-Path $candidate) { return $candidate }

    throw "No se encontró Dropbox. Pásalo con -DropboxRoot 'C:\ruta\Dropbox'"
}

$claudeProjects = "$env:USERPROFILE\.claude\projects"
$dbxRoot        = Get-DropboxRoot
$dbxProjects    = Join-Path $dbxRoot ".claude-projects"

Write-Host ""
Write-Host "Local:   $claudeProjects" -ForegroundColor Cyan
Write-Host "Dropbox: $dbxProjects"   -ForegroundColor Cyan
Write-Host ""

# ── REVERT: deshacer junction y volver a copiar a local ───────────────
if ($Revert) {
    if (-not (Test-Path $claudeProjects)) {
        throw "No existe $claudeProjects — nada que deshacer."
    }
    $item = Get-Item $claudeProjects -Force
    if ($item.LinkType -ne 'Junction') {
        throw "$claudeProjects no es un junction. No se revierte automáticamente."
    }

    Write-Host "Deshaciendo junction..." -ForegroundColor Yellow
    cmd /c rmdir $claudeProjects | Out-Null

    Write-Host "Copiando contenido de Dropbox de vuelta a local..." -ForegroundColor Yellow
    robocopy $dbxProjects $claudeProjects /E /NFL /NDL /NJH /NJS | Out-Null

    Write-Host "Hecho. La carpeta de Dropbox sigue ahí — bórrala manualmente cuando estés listo." -ForegroundColor Green
    exit 0
}

# ── Verificar que Claude Code no esté corriendo ───────────────────────
$running = Get-Process -ErrorAction SilentlyContinue | Where-Object { $_.Name -like "*claude*" -or $_.MainWindowTitle -like "*Claude Code*" }
if ($running) {
    Write-Host "⚠ Hay procesos de Claude Code activos:" -ForegroundColor Red
    $running | ForEach-Object { Write-Host "   - $($_.Name) ($($_.Id))" }
    Write-Host "Ciérralos antes de continuar." -ForegroundColor Red
    exit 1
}

# ── Si ya es un junction, salir ───────────────────────────────────────
if (Test-Path $claudeProjects) {
    $item = Get-Item $claudeProjects -Force
    if ($item.LinkType -eq 'Junction') {
        Write-Host "Ya hay un junction. Apunta a: $($item.Target)" -ForegroundColor Yellow
        exit 0
    }
}

# ── Crear carpeta en Dropbox si no existe ─────────────────────────────
if (-not (Test-Path $dbxProjects)) {
    Write-Host "Creando $dbxProjects..." -ForegroundColor Cyan
    New-Item -ItemType Directory -Path $dbxProjects -Force | Out-Null
}

# ── Copiar contenido local → Dropbox (merge si ya hay algo) ───────────
if (Test-Path $claudeProjects) {
    Write-Host "Copiando proyectos locales a Dropbox..." -ForegroundColor Cyan
    robocopy $claudeProjects $dbxProjects /E /NFL /NDL /NJH /NJS | Out-Null

    # Backup por si acaso
    $backup = "$claudeProjects.backup-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
    Write-Host "Haciendo backup local en $backup..." -ForegroundColor Cyan
    Move-Item $claudeProjects $backup
}

# ── Crear junction ────────────────────────────────────────────────────
Write-Host "Creando junction..." -ForegroundColor Cyan
cmd /c mklink /J $claudeProjects $dbxProjects | Out-Null

# ── Verificar ─────────────────────────────────────────────────────────
$item = Get-Item $claudeProjects -Force
if ($item.LinkType -eq 'Junction') {
    Write-Host ""
    Write-Host "✓ Hecho." -ForegroundColor Green
    Write-Host "  $claudeProjects → $($item.Target)" -ForegroundColor Green
    Write-Host ""
    Write-Host "Si tenías backup, está en: $backup" -ForegroundColor DarkGray
    Write-Host "Repite este script en tu otro equipo (con la misma ruta de Dropbox)." -ForegroundColor DarkGray
    Write-Host ""
    Write-Host "⚠ NO uses Claude Code en los dos equipos a la vez (conflictos de Dropbox)." -ForegroundColor Yellow
} else {
    throw "El junction no se creó correctamente."
}
