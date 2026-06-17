# Deploy automático: build + upload por FTP del contenido de dist/ al subdominio.
# Lee credenciales de .vscode/sftp.json.
# Uso: .\deploy.ps1 [-SkipBuild]

param(
    [switch]$SkipBuild
)

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

# ── 1. Cargar credenciales ────────────────────────────────────────────
$sftpFile = ".\.vscode\sftp.json"
if (-not (Test-Path $sftpFile)) {
    throw ".vscode/sftp.json no existe. Configúralo primero con tus credenciales FTP."
}
$cfg = Get-Content $sftpFile -Raw | ConvertFrom-Json
if (-not $cfg.host -or -not $cfg.username -or -not $cfg.password) {
    throw "Credenciales incompletas en .vscode/sftp.json"
}
if ($cfg.password -like "PEGA_AQUI*") {
    throw "Edita .vscode/sftp.json y pon la password real."
}

$ftpHost   = $cfg.host
$ftpUser   = $cfg.username
$ftpPass   = $cfg.password
$ftpPort   = if ($cfg.port) { $cfg.port } else { 21 }
$remoteDir = $cfg.remotePath.TrimEnd('/')

Write-Host ""
Write-Host "==> Deploy a $ftpHost$remoteDir/" -ForegroundColor Cyan

# ── 2. Build ──────────────────────────────────────────────────────────
if (-not $SkipBuild) {
    Write-Host "[1/3] Build..." -ForegroundColor Cyan
    if (Test-Path "dist") { Remove-Item -Recurse -Force "dist" }
    & npm run build
    if ($LASTEXITCODE -ne 0) { throw "Build falló" }
} else {
    Write-Host "[1/3] (Build saltado, usando dist/ existente)" -ForegroundColor Yellow
}

if (-not (Test-Path "dist")) { throw "dist/ no existe. Ejecuta sin -SkipBuild." }

# ── 3. Recorrer dist/ y subir por FTP ─────────────────────────────────
Write-Host "[2/3] Conectando a FTP..." -ForegroundColor Cyan

$creds = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)

function Ensure-FtpDir {
    param([string]$path)
    $uri = "ftp://${ftpHost}:${ftpPort}${path}"
    try {
        $req = [System.Net.FtpWebRequest]::Create($uri)
        $req.Credentials = $creds
        $req.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $req.UsePassive = $true
        $req.UseBinary = $true
        $req.GetResponse().Close()
    } catch [System.Net.WebException] {
        # 550 = directorio ya existe; ignorar
        $resp = $_.Exception.Response
        if ($resp -and ($resp.StatusCode -eq 'ActionNotTakenFileUnavailable' -or
                        $resp.StatusDescription -match '550')) {
            return
        }
        throw
    }
}

function Upload-FtpFile {
    param([string]$local, [string]$remote)
    $uri = "ftp://${ftpHost}:${ftpPort}${remote}"
    $req = [System.Net.FtpWebRequest]::Create($uri)
    $req.Credentials = $creds
    $req.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
    $req.UsePassive = $true
    $req.UseBinary = $true
    $req.KeepAlive = $true

    $bytes = [System.IO.File]::ReadAllBytes($local)
    $req.ContentLength = $bytes.Length
    $stream = $req.GetRequestStream()
    $stream.Write($bytes, 0, $bytes.Length)
    $stream.Close()
    $resp = $req.GetResponse()
    $resp.Close()
}

Write-Host "[3/3] Subiendo archivos..." -ForegroundColor Cyan

$root  = (Resolve-Path ".\dist").Path
# Filtramos .map: si por error vite.config.js queda con sourcemaps activos,
# evitamos publicarlos en producción.
$items = Get-ChildItem -Recurse -File ".\dist" | Where-Object { $_.Extension -ne ".map" }
$total = $items.Count
$count = 0
$totalBytes = 0

# Pre-crear directorios remotos
$dirs = $items | ForEach-Object {
    $rel = $_.DirectoryName.Substring($root.Length).Replace('\','/')
    if ($rel) { $rel } else { $null }
} | Where-Object { $_ } | Select-Object -Unique

foreach ($d in $dirs) {
    $remoteSub = "${remoteDir}${d}"
    Ensure-FtpDir -path $remoteSub
}

foreach ($f in $items) {
    $count++
    $rel = $f.FullName.Substring($root.Length).Replace('\','/')
    $remote = "${remoteDir}${rel}"
    Write-Host ("  [{0}/{1}] {2}  ({3} KB)" -f $count, $total, $rel, [math]::Round($f.Length/1KB,1)) -ForegroundColor DarkGray
    Upload-FtpFile -local $f.FullName -remote $remote
    $totalBytes += $f.Length
}

$mb = [math]::Round($totalBytes / 1MB, 2)
Write-Host ""
Write-Host "Deploy OK: $total archivos · $mb MB" -ForegroundColor Green

# Encontrar el dominio para abrir el navegador
$origin = $remoteDir -replace '/domains/([^/]+)/public_html/app/?', 'https://app.$1'
if ($origin -ne $remoteDir) {
    Write-Host "Visita: $origin" -ForegroundColor Cyan

    # Health-check: pegamos GET al subdominio esperando 200. Si falla, el
    # deploy se considera roto (exit 1 para que CI/wrapper lo detecte).
    Write-Host "[health-check] $origin/" -ForegroundColor Cyan
    try {
        $resp = Invoke-WebRequest -Uri "$origin/" -UseBasicParsing -TimeoutSec 15 -ErrorAction Stop
        if ($resp.StatusCode -ne 200) {
            Write-Host "Health-check FAILED: status $($resp.StatusCode)" -ForegroundColor Red
            exit 1
        }
        Write-Host "Health-check OK ($($resp.StatusCode))" -ForegroundColor Green
    } catch {
        Write-Host "Health-check FAILED: $($_.Exception.Message)" -ForegroundColor Red
        exit 1
    }
}
