<#
.SYNOPSIS
  Borra un archivo del FTP remoto. Reutiliza credenciales de app/.vscode/sftp.json.

.EXAMPLE
  .\scripts\delete-remote-file.ps1 -RemotePath "/domains/yezraelperez.es/public_html/wp-content/plugins/yz-media-folders/opcache-reset.php"
#>

[CmdletBinding()]
param(
    [Parameter(Mandatory=$true)][string]$RemotePath
)

$ErrorActionPreference = "Stop"
$repoRoot = Split-Path -Parent $PSScriptRoot
$sftpFile = Join-Path $repoRoot "app\.vscode\sftp.json"
if (-not (Test-Path $sftpFile)) { throw "No existe $sftpFile" }
$cfg = Get-Content $sftpFile -Raw | ConvertFrom-Json

$ftpHost = $cfg.host
$ftpPort = if ($cfg.port) { $cfg.port } else { 21 }
$creds   = New-Object System.Net.NetworkCredential($cfg.username, $cfg.password)

$uri = "ftp://${ftpHost}:${ftpPort}${RemotePath}"
$req = [System.Net.FtpWebRequest]::Create($uri)
$req.Credentials = $creds
$req.Method      = [System.Net.WebRequestMethods+Ftp]::DeleteFile
$req.UsePassive  = $true

try {
    $resp = $req.GetResponse()
    Write-Host "Borrado: $RemotePath" -ForegroundColor Green
    Write-Host "  Codigo: $($resp.StatusCode)"
    $resp.Close()
} catch [System.Net.WebException] {
    $resp = $_.Exception.Response
    if ($resp.StatusDescription -match '550') {
        Write-Host "Archivo no existe (o ya borrado): $RemotePath" -ForegroundColor Yellow
    } else {
        throw
    }
}
