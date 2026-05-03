# Mueve sesiones de chat de Claude Code de un proyecto a otro.
# Uso tipico: te has equivocado de cwd al abrir Claude y los chats fueron
# a parar a la carpeta equivocada. Este script las traslada al proyecto correcto.
#
# Uso:
#   .\move-claude-session.ps1 -From "f--dev-proyectos-LeBike" -To "f--dev-proyectos-Media-Folders-APP"
#   .\move-claude-session.ps1 -From "..." -To "..." -Last 1     # solo la sesion mas reciente
#   .\move-claude-session.ps1 -List                              # solo listar proyectos

param(
    [string]$From,
    [string]$To,
    [int]$Last = 0,
    [switch]$List,
    [switch]$Run
)

$ErrorActionPreference = "Stop"

$projectsDir = "$env:USERPROFILE\.claude\projects"
if (-not (Test-Path $projectsDir)) { throw "No existe $projectsDir" }

if ($List -or (-not $From -and -not $To)) {
    Write-Host "Proyectos disponibles en ~/.claude/projects/:" -ForegroundColor Cyan
    Get-ChildItem $projectsDir -Directory | ForEach-Object {
        $sessions = (Get-ChildItem $_.FullName -Filter "*.jsonl" -ErrorAction SilentlyContinue | Measure-Object).Count
        $size = (Get-ChildItem $_.FullName -Recurse -File -ErrorAction SilentlyContinue | Measure-Object -Property Length -Sum).Sum
        [PSCustomObject]@{
            Carpeta  = $_.Name
            Sesiones = $sessions
            SizeMB   = [math]::Round($size / 1MB, 2)
        }
    } | Format-Table -AutoSize
    exit 0
}

if (-not $From -or -not $To) { throw "Faltan -From y -To" }

$src = Join-Path $projectsDir $From
$dst = Join-Path $projectsDir $To

if (-not (Test-Path $src)) { throw "Origen no existe: $src" }

# Crear destino si no existe
if (-not (Test-Path $dst)) {
    Write-Host "Creando $dst..." -ForegroundColor Cyan
    if ($Run) { New-Item -ItemType Directory -Path $dst | Out-Null }
}

# Listar sesiones disponibles ordenadas por fecha (mas recientes primero)
$sessions = Get-ChildItem $src -Filter "*.jsonl" | Sort-Object LastWriteTime -Descending

if ($Last -gt 0) {
    $sessions = $sessions | Select-Object -First $Last
}

Write-Host ""
Write-Host "Sesiones a mover: $($sessions.Count)" -ForegroundColor Yellow
$sessions | ForEach-Object {
    $sizeKB = [math]::Round($_.Length / 1KB, 0)
    Write-Host ("  {0}  {1} KB  {2}" -f $_.LastWriteTime.ToString("yyyy-MM-dd HH:mm"), $sizeKB, $_.Name)
}

if (-not $Run) {
    Write-Host ""
    Write-Host "Previsualizacion. Anade -Run para mover de verdad." -ForegroundColor Yellow
    exit 0
}

Write-Host ""
foreach ($s in $sessions) {
    $target = Join-Path $dst $s.Name
    Write-Host ("Moviendo {0}..." -f $s.Name) -ForegroundColor DarkGray
    Move-Item -Path $s.FullName -Destination $target -Force
}

Write-Host ""
Write-Host ("Hecho. {0} sesiones movidas de {1} a {2}." -f $sessions.Count, $From, $To) -ForegroundColor Green
