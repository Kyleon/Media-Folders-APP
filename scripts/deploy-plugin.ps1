<#
.SYNOPSIS
  Deploy de plugins (y opcionalmente themes) a Hostinger via FTP.

.DESCRIPTION
  Reutiliza credenciales de app/.vscode/sftp.json.

  Targets soportados (auto-descubiertos):
    plugin/                          -> wp-content/plugins/yz-media-folders/
    wp/plugins/<name>/               -> wp-content/plugins/<name>/
    wp/themes/<name>/                -> wp-content/themes/<name>/

  Por defecto sube TODOS los plugins (yz-media-folders + wp/plugins/*).
  Themes solo se incluyen si se pasa -IncludeThemes.

.PARAMETER Target
  Lista de targets a subir (acepta wildcards). Si vacio = todos los plugins.
  Ejemplos: yz-media-folders, yzmf-*, ypva-child

.PARAMETER IncludeThemes
  Tambien incluir themes (wp/themes/*) en el deploy.

.PARAMETER List
  Solo lista los targets disponibles, sin subir.

.PARAMETER DryRun
  Lista archivos que se subirian, sin subirlos.

.EXAMPLE
  .\scripts\deploy-plugin.ps1
  .\scripts\deploy-plugin.ps1 -Target yzmf-client-portal
  .\scripts\deploy-plugin.ps1 -Target yzmf-* -DryRun
  .\scripts\deploy-plugin.ps1 -IncludeThemes -Target ypva-child
  .\scripts\deploy-plugin.ps1 -List
#>

[CmdletBinding()]
param(
    [string[]]$Target = @(),
    [switch]$IncludeThemes,
    [switch]$List,
    [switch]$DryRun
)

$ErrorActionPreference = "Stop"
$repoRoot = Split-Path -Parent $PSScriptRoot

# ── 1. Credenciales (mismas que el PWA) ────────────────────────────────
$sftpFile = Join-Path $repoRoot "app\.vscode\sftp.json"
if (-not (Test-Path $sftpFile)) { throw "No existe $sftpFile" }
$cfg = Get-Content $sftpFile -Raw | ConvertFrom-Json

$ftpHost = $cfg.host
$ftpUser = $cfg.username
$ftpPass = $cfg.password
$ftpPort = if ($cfg.port) { $cfg.port } else { 21 }
$baseRemote = '/domains/yezraelperez.es/public_html/wp-content'

# ── 2. Construir lista de targets ──────────────────────────────────────
# Estructura: @{ Name; LocalDir; RemoteDir; Kind }
$targets = [System.Collections.Generic.List[hashtable]]::new()

# El plugin propio en /plugin
if (Test-Path (Join-Path $repoRoot 'plugin')) {
    $targets.Add(@{
        Name      = 'yz-media-folders'
        LocalDir  = Join-Path $repoRoot 'plugin'
        RemoteDir = "$baseRemote/plugins/yz-media-folders"
        Kind      = 'plugin'
    })
}

# Plugins en wp/plugins/*
$wpPluginsDir = Join-Path $repoRoot 'wp\plugins'
if (Test-Path $wpPluginsDir) {
    Get-ChildItem -Path $wpPluginsDir -Directory | ForEach-Object {
        $targets.Add(@{
            Name      = $_.Name
            LocalDir  = $_.FullName
            RemoteDir = "$baseRemote/plugins/$($_.Name)"
            Kind      = 'plugin'
        })
    }
}

# Themes en wp/themes/* (solo si IncludeThemes)
if ($IncludeThemes) {
    $wpThemesDir = Join-Path $repoRoot 'wp\themes'
    if (Test-Path $wpThemesDir) {
        Get-ChildItem -Path $wpThemesDir -Directory | ForEach-Object {
            $targets.Add(@{
                Name      = $_.Name
                LocalDir  = $_.FullName
                RemoteDir = "$baseRemote/themes/$($_.Name)"
                Kind      = 'theme'
            })
        }
    }
}

# Filtrar por -Target (con wildcards)
if ($Target.Count -gt 0) {
    $filtered = [System.Collections.Generic.List[hashtable]]::new()
    foreach ($t in $targets) {
        foreach ($pattern in $Target) {
            if ($t.Name -like $pattern) { $filtered.Add($t); break }
        }
    }
    $targets = $filtered
}

if ($List) {
    Write-Host ""
    Write-Host "Targets disponibles:" -ForegroundColor Cyan
    foreach ($t in $targets) {
        Write-Host ("  {0,-30} {1,-7} -> {2}" -f $t.Name, $t.Kind, $t.RemoteDir)
    }
    exit 0
}

if ($targets.Count -eq 0) {
    Write-Host "No hay targets que coincidan." -ForegroundColor Yellow
    exit 0
}

Write-Host ""
Write-Host "==> Deploy a $ftpHost" -ForegroundColor Cyan
Write-Host ("    Targets: " + ($targets | ForEach-Object { $_.Name }) -join ', ')
if ($DryRun) { Write-Host "    (DryRun: solo listado, sin subir)" -ForegroundColor Yellow }

# ── 3. FTP helpers ─────────────────────────────────────────────────────
$creds = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)

function Ensure-FtpDir {
    param([string]$path)
    $uri = "ftp://${ftpHost}:${ftpPort}${path}"
    try {
        $req = [System.Net.FtpWebRequest]::Create($uri)
        $req.Credentials = $creds
        $req.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $req.UsePassive = $true; $req.UseBinary = $true
        $req.GetResponse().Close()
    } catch [System.Net.WebException] {
        $resp = $_.Exception.Response
        if ($resp -and ($resp.StatusCode -eq 'ActionNotTakenFileUnavailable' -or
                        $resp.StatusDescription -match '550')) { return }
        throw
    }
}

function Upload-FtpFile {
    param([string]$local, [string]$remote)
    $uri = "ftp://${ftpHost}:${ftpPort}${remote}"
    $req = [System.Net.FtpWebRequest]::Create($uri)
    $req.Credentials = $creds
    $req.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
    $req.UsePassive = $true; $req.UseBinary = $true; $req.KeepAlive = $true
    $bytes = [System.IO.File]::ReadAllBytes($local)
    $req.ContentLength = $bytes.Length
    $stream = $req.GetRequestStream()
    $stream.Write($bytes, 0, $bytes.Length); $stream.Close()
    $resp = $req.GetResponse(); $resp.Close()
}

# Excluidos comunes
$exclude = @(
    'users.json','setup.php','config.php','.git','.gitignore',
    'node_modules','.cache','cache','.DS_Store','Thumbs.db'
)

# ── 4. Procesar cada target ────────────────────────────────────────────
$grandTotalFiles = 0
$grandTotalBytes = 0

foreach ($t in $targets) {
    $localDir  = $t.LocalDir
    $remoteDir = $t.RemoteDir
    $root = (Resolve-Path $localDir).Path

    Write-Host ""
    Write-Host "── $($t.Name) ──" -ForegroundColor Magenta
    Write-Host "   $localDir  ->  $remoteDir"

    $items = Get-ChildItem -Path $localDir -Recurse -File | Where-Object {
        $rel = $_.FullName.Substring($root.Length + 1).Replace('\','/')
        $top = $rel.Split('/')[0]
        $matchExcluded = $false
        foreach ($e in $exclude) {
            if ($_.Name -eq $e -or $top -eq $e -or $rel -like "*/$e/*") { $matchExcluded = $true; break }
        }
        -not $matchExcluded
    }

    if ($items.Count -eq 0) { Write-Host "   (vacio)" -ForegroundColor DarkGray; continue }

    # Crear directorios remotos
    $dirs = $items | ForEach-Object {
        $rel = $_.DirectoryName.Substring($root.Length).Replace('\','/')
        if ($rel) { $rel } else { $null }
    } | Where-Object { $_ } | Select-Object -Unique | Sort-Object { $_.Length }

    if (-not $DryRun) {
        # Asegurar el directorio raiz primero
        Ensure-FtpDir -path $remoteDir
        foreach ($d in $dirs) {
            Ensure-FtpDir -path "${remoteDir}${d}"
        }
    }

    # Subir archivos
    $count = 0
    $bytes = 0
    foreach ($f in $items) {
        $count++
        $rel = $f.FullName.Substring($root.Length).Replace('\','/')
        $remote = "${remoteDir}${rel}"
        $kb = [math]::Round($f.Length/1KB,1)
        Write-Host ("   [{0}/{1}] {2} ({3} KB)" -f $count, $items.Count, $rel.TrimStart('/'), $kb) -ForegroundColor DarkGray
        if (-not $DryRun) {
            try { Upload-FtpFile -local $f.FullName -remote $remote }
            catch { Write-Host "      ERROR: $($_.Exception.Message)" -ForegroundColor Red; throw }
        }
        $bytes += $f.Length
    }

    $grandTotalFiles += $count
    $grandTotalBytes += $bytes
    $mb = [math]::Round($bytes / 1MB, 2)
    Write-Host ("   $count archivos / $mb MB") -ForegroundColor DarkGreen
}

$totalMb = [math]::Round($grandTotalBytes / 1MB, 2)
Write-Host ""
if ($DryRun) {
    Write-Host "DryRun total: $grandTotalFiles archivos / $totalMb MB" -ForegroundColor Yellow
} else {
    Write-Host "Deploy OK: $grandTotalFiles archivos / $totalMb MB" -ForegroundColor Green
}

# Tips
Write-Host ""
Write-Host "Tips:" -ForegroundColor Cyan
Write-Host "  - Activa los plugins nuevos en WP Admin > Plugins"
Write-Host "  - Borra transients yzmf_stats_cache y yzmf_stats_exif_cache para refresh"
Write-Host "  - YZMF Hotlink Watermark crea su .htaccess al activarse (necesita permisos)"
Write-Host "  - Tras instalar yzmf-client-portal, ve a Settings y guarda permalinks para que /g/{token} funcione"

exit 0
