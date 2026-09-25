<#
.SYNOPSIS
  Helpers FTP compartidos por deploy.ps1, deploy-plugin.ps1 y delete-remote-file.ps1.

  Initialize-FtpTls activa FTPS explícito (AUTH TLS) para que usuario y
  password no viajen en claro. Devuelve $true/$false para asignar a
  $req.EnableSsl.

  - Por defecto: FTPS activado. Para desactivarlo (NO recomendado) añade
    "secure": false en app/.vscode/sftp.json.
  - El host suele ser una IP y el certificado de Hostinger está emitido para
    su propio dominio, así que aceptamos un certificado con cadena válida
    aunque el nombre no coincida. Cualquier otro error (autofirmado,
    caducado, CA desconocida) se rechaza.
#>

function Initialize-FtpTls {
    param([Parameter(Mandatory)] $Cfg)

    $secure = $true
    if ($Cfg.PSObject.Properties['secure'] -and $Cfg.secure -eq $false) { $secure = $false }

    if ($secure) {
        [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.SecurityProtocolType]::Tls12
        [System.Net.ServicePointManager]::ServerCertificateValidationCallback = {
            param($sender, $cert, $chain, $errors)
            return ($errors -eq [System.Net.Security.SslPolicyErrors]::None) -or
                   ($errors -eq [System.Net.Security.SslPolicyErrors]::RemoteCertificateNameMismatch)
        }
    } else {
        Write-Host "  AVISO: FTP sin cifrar ('secure': false en sftp.json). Credenciales en claro." -ForegroundColor Yellow
    }
    return $secure
}

<#
  URI con ruta ABSOLUTA en el servidor. FtpWebRequest interpreta la ruta
  relativa al directorio inicial de la sesión; si Hostinger cambia ese
  directorio (p. ej. a /public_html), "/domains/..." deja de existir.
  El prefijo %2F fuerza un CWD absoluto desde la raíz de la cuenta.
#>
function Get-FtpUri {
    param([string]$FtpHost, $Port, [string]$Path)
    return "ftp://${FtpHost}:${Port}/%2F" + $Path.TrimStart('/')
}
