# Bootstrap del proyecto Media-Folders-APP en un equipo nuevo.
# Asume que ya tienes instalados: Git, Node.js LTS, Dropbox (sincronizado),
# y has habilitado ExecutionPolicy = RemoteSigned para CurrentUser.
#
# Lee docs/MULTI-EQUIPO.md primero si tienes dudas.
#
# Uso normal (repo ya clonado):
#   cd F:\dev\proyectos\Media-Folders-APP\scripts
#   .\setup-laptop.ps1
#
# Si el repo aun no esta clonado, clonalo primero:
#   mkdir -Force F:\dev\proyectos
#   cd F:\dev\proyectos
#   git clone https://github.com/Kyleon/Media-Folders-APP.git
#   cd Media-Folders-APP\scripts
#   .\setup-laptop.ps1
#
# Argumentos opcionales:
#   -RootPath "F:\dev\proyectos"        ruta donde clonar el monorepo (default: F:\dev\proyectos)
#   -SkipNpmInstall                     no ejecutar npm install (lo haras tu manualmente)
#   -SkipClaudeSync                     no crear el junction de Dropbox a ~/.claude/

param(
    [string]$RootPath = "F:\dev\proyectos",
    [switch]$SkipNpmInstall,
    [switch]$SkipClaudeSync
)

$ErrorActionPreference = "Stop"

function Step($n, $msg) { Write-Host ""; Write-Host "[$n] $msg" -ForegroundColor Cyan }
function OK($msg)       { Write-Host "  $msg" -ForegroundColor Green }
function WARN($msg)     { Write-Host "  $msg" -ForegroundColor Yellow }
function ERR($msg)      { Write-Host "  $msg" -ForegroundColor Red }

$projectName = "Media-Folders-APP"
$projectPath = Join-Path $RootPath $projectName
$repoUrl     = "https://github.com/Kyleon/Media-Folders-APP.git"

Write-Host ""
Write-Host "=================================================="
Write-Host "  Setup Media-Folders-APP en equipo nuevo"
Write-Host "=================================================="

# ── 1. Comprobar dependencias ───────────────────────────────────────────
Step 1 "Verificando dependencias instaladas..."

$missing = @()
if (-not (Get-Command git  -ErrorAction SilentlyContinue)) { $missing += "git" }
if (-not (Get-Command node -ErrorAction SilentlyContinue)) { $missing += "node" }
if (-not (Get-Command npm  -ErrorAction SilentlyContinue)) { $missing += "npm" }

if ($missing.Count -gt 0) {
    ERR "Faltan: $($missing -join ', ')"
    Write-Host "Instala lo que falte y vuelve a lanzar este script."
    Write-Host "  Git:      https://git-scm.com/download/win"
    Write-Host "  Node LTS: https://nodejs.org/es/download"
    exit 1
}
OK ("git $(git --version | ForEach-Object { ($_ -split ' ')[2] })")
OK ("node $(node -v)")
OK ("npm $(npm -v)")

# ── 2. Verificar ExecutionPolicy ────────────────────────────────────────
Step 2 "Verificando ExecutionPolicy de PowerShell..."
$pol = Get-ExecutionPolicy -Scope CurrentUser
if ($pol -eq "Restricted" -or $pol -eq "AllSigned") {
    WARN "ExecutionPolicy = $pol. Esto puede impedir ejecutar npm.ps1"
    WARN "Ejecuta como admin: Set-ExecutionPolicy -Scope CurrentUser -ExecutionPolicy RemoteSigned"
} else {
    OK "ExecutionPolicy = $pol (OK)"
}

# ── 3. Clonar el repo ───────────────────────────────────────────────────
Step 3 "Preparando repositorio en $projectPath..."

if (-not (Test-Path $RootPath)) {
    OK "Creando $RootPath"
    New-Item -ItemType Directory -Path $RootPath -Force | Out-Null
}

if (Test-Path $projectPath) {
    if (Test-Path "$projectPath\.git") {
        OK "El repo ya existe en $projectPath. Hago git pull."
        Push-Location $projectPath
        git pull --ff-only 2>&1 | Out-Host
        Pop-Location
    } else {
        ERR "$projectPath existe pero no es un repo git. Renombrala o borrala antes de continuar."
        exit 1
    }
} else {
    Push-Location $RootPath
    OK "Clonando $repoUrl..."
    git clone $repoUrl $projectName 2>&1 | Out-Host
    Pop-Location
}

# ── 4. npm install ──────────────────────────────────────────────────────
Step 4 "Instalando dependencias de la PWA..."

if ($SkipNpmInstall) {
    WARN "Saltado por flag -SkipNpmInstall"
} else {
    Push-Location "$projectPath\app"
    npm install 2>&1 | Out-Host
    Pop-Location
    if ($LASTEXITCODE -ne 0) {
        ERR "npm install fallo. Revisa el log."
    } else {
        OK "Dependencias instaladas"
    }
}

# ── 5. Configurar sftp.json ─────────────────────────────────────────────
Step 5 "Configurando .vscode/sftp.json..."

$sftpDir  = "$projectPath\app\.vscode"
$sftpFile = "$sftpDir\sftp.json"
if (Test-Path $sftpFile) {
    OK "Ya existe sftp.json. No se sobrescribe."
} else {
    if (-not (Test-Path $sftpDir)) { New-Item -ItemType Directory -Path $sftpDir -Force | Out-Null }
    $template = @'
{
  "name": "YPVA App",
  "host": "145.14.152.131",
  "protocol": "ftp",
  "port": 21,
  "username": "u604760889",
  "password": "PEGA_AQUI_TU_PASSWORD_FTP",
  "remotePath": "/domains/yezraelperez.es/public_html/app/",
  "context": "dist",
  "uploadOnSave": false,
  "ignore": [
    ".vscode", ".git", "node_modules", "src", "public",
    "package.json", "package-lock.json", "vite.config.js",
    "index.html", "build.ps1", "README.md", ".gitignore", ".DS_Store"
  ]
}
'@
    Set-Content -Path $sftpFile -Value $template -Encoding UTF8
    WARN "sftp.json creado como plantilla. Edita la password antes de hacer deploy:"
    WARN "  $sftpFile"
}

# ── 6. Junction de Claude Code en Dropbox ───────────────────────────────
Step 6 "Verificando sincronizacion de Claude Code via Dropbox..."

if ($SkipClaudeSync) {
    WARN "Saltado por flag -SkipClaudeSync"
} else {
    $dbxClaude = "$env:USERPROFILE\Dropbox\.claude"
    $locClaude = "$env:USERPROFILE\.claude"

    if (-not (Test-Path $dbxClaude)) {
        WARN "No encontre $dbxClaude. Asegurate de tener Dropbox sincronizado."
        WARN "Si no quieres este paso, vuelve a lanzar con -SkipClaudeSync."
    } else {
        $needsLink = $true
        if (Test-Path $locClaude) {
            $item = Get-Item $locClaude -Force
            if ($item.LinkType -eq 'Junction') {
                OK "~/.claude ya es junction a $($item.Target)"
                $needsLink = $false
            } else {
                $bk = "$locClaude.backup-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
                OK "~/.claude existe como carpeta normal. Backup -> $bk"
                Move-Item $locClaude $bk
            }
        }
        if ($needsLink) {
            cmd /c mklink /J $locClaude $dbxClaude | Out-Null
            $check = Get-Item $locClaude -Force
            if ($check.LinkType -eq 'Junction') {
                OK "Junction creado: $locClaude -> $dbxClaude"
            } else {
                ERR "El junction no se creo. Revisa permisos."
            }
        }
    }
}

# ── 7. Resumen final ────────────────────────────────────────────────────
Write-Host ""
Write-Host "=================================================="
Write-Host "  Setup completado"
Write-Host "=================================================="
Write-Host ""
Write-Host "Repo:      $projectPath"
Write-Host "Doc:       $projectPath\docs\MULTI-EQUIPO.md"
Write-Host ""
Write-Host "Proximos pasos:"
Write-Host "  1. Edita la password en $sftpFile (si vas a hacer deploy)"
Write-Host "  2. Para arrancar la PWA en local:"
Write-Host "       cd $projectPath\app"
Write-Host "       npm run dev"
Write-Host "  3. Para retomar el chat de Claude Code:"
Write-Host "       cd $projectPath"
Write-Host "       claude"
Write-Host "       /resume"
Write-Host ""
Write-Host "  No uses Claude Code en los dos equipos a la vez (conflictos Dropbox)."
Write-Host ""
