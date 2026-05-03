# Sustituye las dos carpetas locales (PWA legacy + plugin instalado en WP)
# por junctions que apuntan al monorepo Media-Folders-APP.
#
# Resultado: editas en el monorepo y los cambios se ven instantaneamente
# en la PWA local y en tu WordPress local. Un solo source-of-truth.
#
# Uso:
#   .\link-monorepo.ps1                # preview, no toca nada
#   .\link-monorepo.ps1 -Run           # ejecuta de verdad (hace backup primero)
#   .\link-monorepo.ps1 -Revert        # deshace los junctions y restaura backup

param(
    [switch]$Run,
    [switch]$Revert
)

$ErrorActionPreference = "Stop"

$monoroot = "F:\dev\proyectos\Media-Folders-APP"

# Pares (carpeta_local, carpeta_en_monorepo)
$pairs = @(
    @{
        Local = "F:\dev\proyectos\yezraelperez-app"
        Mono  = "$monoroot\app"
        Tag   = "PWA"
    },
    @{
        Local = "F:\dev\proyectos\yezraelperez.es\wp-content\plugins\yz-media-folders"
        Mono  = "$monoroot\plugin"
        Tag   = "Plugin WP"
    }
)

# Validar que el monorepo existe
foreach ($p in $pairs) {
    if (-not (Test-Path $p.Mono)) { throw "No existe destino del monorepo: $($p.Mono)" }
}

# ── REVERT ───────────────────────────────────────────────────────────
if ($Revert) {
    foreach ($p in $pairs) {
        Write-Host ""
        Write-Host "=== $($p.Tag) ===" -ForegroundColor Cyan
        if (-not (Test-Path $p.Local)) {
            Write-Host "  No existe: $($p.Local)" -ForegroundColor Yellow
            continue
        }
        $item = Get-Item $p.Local -Force
        if ($item.LinkType -ne 'Junction') {
            Write-Host "  No es un junction. Salto." -ForegroundColor Yellow
            continue
        }
        Write-Host "  Borrando junction $($p.Local)..." -ForegroundColor Yellow
        cmd /c rmdir $p.Local | Out-Null

        # Buscar backup mas reciente
        $parent = Split-Path -Parent $p.Local
        $base   = Split-Path -Leaf $p.Local
        $backup = Get-ChildItem $parent -Directory -Filter "$base.backup-*" -ErrorAction SilentlyContinue |
                  Sort-Object LastWriteTime -Descending | Select-Object -First 1
        if ($backup) {
            Write-Host "  Restaurando backup: $($backup.Name)" -ForegroundColor Yellow
            Move-Item $backup.FullName $p.Local
        } else {
            Write-Host "  Sin backup encontrado. Carpeta queda vacia." -ForegroundColor Red
        }
    }
    Write-Host ""
    Write-Host "Revert completado." -ForegroundColor Green
    exit 0
}

# ── PREVIEW / RUN ────────────────────────────────────────────────────
Write-Host ""
Write-Host "=== Plan ===" -ForegroundColor Cyan
foreach ($p in $pairs) {
    $exists = Test-Path $p.Local
    $item   = if ($exists) { Get-Item $p.Local -Force } else { $null }
    $isLink = $item -and $item.LinkType -eq 'Junction'

    Write-Host ""
    Write-Host "[$($p.Tag)]"
    Write-Host "  Local: $($p.Local)"
    Write-Host "  Mono:  $($p.Mono)"

    if (-not $exists) {
        Write-Host "  Estado: NO EXISTE -> se creara junction directo." -ForegroundColor Yellow
    } elseif ($isLink) {
        Write-Host "  Estado: YA ES JUNCTION -> $($item.Target)" -ForegroundColor Green
    } else {
        $size = (Get-ChildItem $p.Local -Recurse -File -ErrorAction SilentlyContinue | Measure-Object -Property Length -Sum).Sum
        $sizeMB = [math]::Round($size / 1MB, 1)
        Write-Host "  Estado: CARPETA NORMAL ($sizeMB MB) -> backup + junction." -ForegroundColor Yellow
    }
}

if (-not $Run) {
    Write-Host ""
    Write-Host "Esto es preview. Ejecuta con -Run para aplicar." -ForegroundColor Yellow
    exit 0
}

# ── EJECUTAR ────────────────────────────────────────────────────────
foreach ($p in $pairs) {
    Write-Host ""
    Write-Host "=== Aplicando: $($p.Tag) ===" -ForegroundColor Cyan

    if (Test-Path $p.Local) {
        $item = Get-Item $p.Local -Force
        if ($item.LinkType -eq 'Junction') {
            Write-Host "  Ya es junction. Salto." -ForegroundColor Green
            continue
        }
        # Backup
        $backup = "$($p.Local).backup-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
        Write-Host "  Backup -> $backup" -ForegroundColor Yellow
        Move-Item $p.Local $backup
    }

    Write-Host "  Creando junction $($p.Local) -> $($p.Mono)" -ForegroundColor Cyan
    cmd /c mklink /J $p.Local $p.Mono | Out-Null

    $check = Get-Item $p.Local -Force
    if ($check.LinkType -eq 'Junction') {
        Write-Host "  OK" -ForegroundColor Green
    } else {
        throw "Junction no creado correctamente."
    }
}

Write-Host ""
Write-Host "=== Hecho ===" -ForegroundColor Green
Write-Host "Edita siempre en: $monoroot"
Write-Host "WordPress local y la PWA local ven los mismos archivos automaticamente."
Write-Host "Para deshacer: .\link-monorepo.ps1 -Revert"
