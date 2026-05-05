<#
.SYNOPSIS
  Sincroniza wp/themes y wp/plugins entre el repo y la instalacion WP local.

.DESCRIPTION
  El repo contiene los fuentes "canonicos" del theme (ypva, ypva-child) y del
  theme-plugin (kotlis-plugin). El WP local en C:\dev\proyectos\yezraelperez.es
  es el que sirve el sitio en localhost para pruebas.

  Modos:
    push  Repo  ->  WP local        (despliega cambios del repo al WP)
    pull  WP local  ->  Repo        (recupera cambios hechos directamente en WP)
    diff  Lista archivos diferentes (no copia)

  Por defecto: push (la fuente de verdad es el repo).

.PARAMETER Mode
  push | pull | diff

.PARAMETER WpPath
  Ruta a la instalacion WP local. Por defecto C:\dev\proyectos\yezraelperez.es

.EXAMPLE
  .\scripts\sync-wp.ps1
  .\scripts\sync-wp.ps1 -Mode pull
  .\scripts\sync-wp.ps1 -Mode diff
#>

[CmdletBinding()]
param(
  [ValidateSet('push','pull','diff')]
  [string]$Mode = 'push',
  [string]$WpPath = 'C:\dev\proyectos\yezraelperez.es'
)

$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot

# Pares fijos: el plugin propio (en plugin/) y otros explicitos
$pairs = [System.Collections.Generic.List[hashtable]]::new()
$pairs.Add(@{ Repo = "$repoRoot\plugin"; Wp = "$WpPath\wp-content\plugins\yz-media-folders" })

# Auto-descubrimiento: cualquier subcarpeta dentro de wp/themes/ y wp/plugins/
foreach ($kind in @('themes','plugins')) {
  $base = Join-Path $repoRoot "wp\$kind"
  if (-not (Test-Path $base)) { continue }
  Get-ChildItem -Path $base -Directory | ForEach-Object {
    $pairs.Add(@{
      Repo = $_.FullName
      Wp   = Join-Path $WpPath "wp-content\$kind\$($_.Name)"
    })
  }
}

Write-Host "Modo: $Mode" -ForegroundColor Cyan
Write-Host "Repo: $repoRoot"
Write-Host "WP:   $WpPath"
Write-Host ""

# Excluidos comunes (cache, builds locales, etc.)
$excludeDirs = @('node_modules','.git','cache','.cache','dist','build','.sass-cache')

foreach ($pair in $pairs) {
  $repo = $pair.Repo
  $wp   = $pair.Wp

  if (-not (Test-Path $repo)) { Write-Host "  -- saltando (repo no existe): $repo" -ForegroundColor DarkYellow; continue }
  if (-not (Test-Path $wp))   { Write-Host "  -- creando destino: $wp" -ForegroundColor DarkYellow; New-Item -ItemType Directory -Path $wp -Force | Out-Null }

  switch ($Mode) {
    'push' {
      Write-Host "[push] $($repo.Replace($repoRoot,'.\'))  ->  $wp" -ForegroundColor Green
      & robocopy $repo $wp /MIR /XD @excludeDirs /NFL /NDL /NJH /NJS /NP | Out-Null
    }
    'pull' {
      Write-Host "[pull] $wp  ->  $($repo.Replace($repoRoot,'.\'))" -ForegroundColor Yellow
      & robocopy $wp $repo /MIR /XD @excludeDirs /NFL /NDL /NJH /NJS /NP | Out-Null
    }
    'diff' {
      Write-Host "[diff] $($repo.Replace($repoRoot,'.\'))  vs  $wp" -ForegroundColor Magenta
      $out = & robocopy $repo $wp /L /MIR /XD @excludeDirs /NJH /NJS /NDL /NP /NS /NC
      $changed = $out | Where-Object { $_ -and ($_ -notmatch '^\s*$') -and ($_ -notmatch '0\s+$') }
      if ($changed) { $changed | ForEach-Object { Write-Host "  $_" } }
      else { Write-Host "  sin diferencias" -ForegroundColor DarkGreen }
    }
  }
}

# robocopy devuelve >=8 si hubo error real; <8 son codigos de exito (con/sin copias)
if ($LASTEXITCODE -ge 8) {
  Write-Host ""
  Write-Host "Robocopy reporto errores (codigo $LASTEXITCODE)" -ForegroundColor Red
  exit 1
}

Write-Host ""
Write-Host "Hecho." -ForegroundColor Green
exit 0
