# Genera los iconos PWA (192, 512, 512-maskable) directamente con .NET (System.Drawing).
# No requiere ImageMagick ni librerias externas.
#
# Salida: ../public/icons/{icon-192.png, icon-512.png, icon-512-maskable.png}
#
# Uso: cd app/scripts y .\generate-icons.ps1

$ErrorActionPreference = "Stop"

Add-Type -AssemblyName System.Drawing

$outDir = Join-Path $PSScriptRoot "..\public\icons"
if (-not (Test-Path $outDir)) {
    New-Item -ItemType Directory -Path $outDir -Force | Out-Null
}

# Colores del proyecto (consistentes con tokens.css)
$bgColor = [System.Drawing.Color]::FromArgb(15, 15, 15)        # #0f0f0f
$accent  = [System.Drawing.Color]::FromArgb(200, 169, 126)     # #c8a97e

function New-Icon {
    param(
        [int]$Size,
        [string]$OutPath,
        [bool]$Maskable = $false
    )

    $bmp = New-Object System.Drawing.Bitmap $Size, $Size
    $g   = [System.Drawing.Graphics]::FromImage($bmp)
    $g.SmoothingMode     = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $g.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::AntiAlias
    $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic

    if ($Maskable) {
        # Maskable: fondo a borde completo, "Y" mas pequeña dentro del 80% safe zone
        $bgBrush = New-Object System.Drawing.SolidBrush $bgColor
        $g.FillRectangle($bgBrush, 0, 0, $Size, $Size)
        $bgBrush.Dispose()
        $fontSize = $Size * 0.42
    } else {
        # Normal: rectangulo redondeado al 18% del lado (estandar iOS-like)
        $radius = [int]($Size * 0.18)
        $path = New-Object System.Drawing.Drawing2D.GraphicsPath
        $d = $radius * 2
        $path.AddArc(0,             0,            $d, $d, 180, 90)
        $path.AddArc($Size - $d,    0,            $d, $d, 270, 90)
        $path.AddArc($Size - $d,    $Size - $d,   $d, $d,   0, 90)
        $path.AddArc(0,             $Size - $d,   $d, $d,  90, 90)
        $path.CloseFigure()

        $bgBrush = New-Object System.Drawing.SolidBrush $bgColor
        $g.FillPath($bgBrush, $path)
        $bgBrush.Dispose()
        $path.Dispose()
        $fontSize = $Size * 0.55
    }

    # Letra "Y" centrada
    $font = New-Object System.Drawing.Font("Helvetica", $fontSize, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)
    $textBrush = New-Object System.Drawing.SolidBrush $accent

    $sf = New-Object System.Drawing.StringFormat
    $sf.Alignment     = [System.Drawing.StringAlignment]::Center
    $sf.LineAlignment = [System.Drawing.StringAlignment]::Center

    # Pequeño ajuste vertical
    $rect = New-Object System.Drawing.RectangleF 0, ($Size * 0.04), $Size, $Size

    $g.DrawString("Y", $font, $textBrush, $rect, $sf)

    $textBrush.Dispose()
    $font.Dispose()
    $sf.Dispose()
    $g.Dispose()

    $bmp.Save($OutPath, [System.Drawing.Imaging.ImageFormat]::Png)
    $bmp.Dispose()

    $kb = [math]::Round((Get-Item $OutPath).Length / 1KB, 1)
    Write-Host ("  {0}  ({1} KB)" -f $OutPath, $kb) -ForegroundColor Green
}

Write-Host ""
Write-Host "Generando iconos en $outDir" -ForegroundColor Cyan

New-Icon -Size 192 -OutPath (Join-Path $outDir "icon-192.png")
New-Icon -Size 512 -OutPath (Join-Path $outDir "icon-512.png")
New-Icon -Size 512 -OutPath (Join-Path $outDir "icon-512-maskable.png") -Maskable $true

Write-Host ""
Write-Host "Hecho." -ForegroundColor Green
Write-Host "Recuerda hacer build de la PWA: cd .. y .\build.ps1" -ForegroundColor DarkGray
