
Add-Type -AssemblyName System.Drawing

# Create a bitmap of size 512x512
$size = 512
$bitmap = New-Object System.Drawing.Bitmap $size, $size

# Create a graphics object
$graphics = [System.Drawing.Graphics]::FromImage($bitmap)
$graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
$graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic

# Colors - Finance Theme (Green/Dark)
$bgColor = [System.Drawing.ColorTranslator]::FromHtml("#10b981") # Emerald Green
$fgColor = [System.Drawing.ColorTranslator]::FromHtml("#ffffff") # White

# Fill background
$graphics.Clear($bgColor)

# Define Pen and Brush
$penThickness = 40
$pen = New-Object System.Drawing.Pen $fgColor, $penThickness
$brush = New-Object System.Drawing.SolidBrush $fgColor

# Design: Minimalist Chart / Wallet / Coin symbol
# Let's do a rising chart bar graph symbol

$barWidth = 70
$spacing = 40
$centerY = $size / 2

# Bar 1 (Short)
$h1 = 150
$x1 = 110
$y1 = 350 - $h1
$r1 = New-Object System.Drawing.Rectangle $x1, $y1, $barWidth, $h1
$graphics.FillRectangle($brush, $r1)

# Bar 2 (Medium)
$h2 = 220
$x2 = $x1 + $barWidth + $spacing
$y2 = 350 - $h2
$r2 = New-Object System.Drawing.Rectangle $x2, $y2, $barWidth, $h2
$graphics.FillRectangle($brush, $r2)

# Bar 3 (Tall - Growth)
$h3 = 300
$x3 = $x2 + $barWidth + $spacing
$y3 = 350 - $h3
$r3 = New-Object System.Drawing.Rectangle $x3, $y3, $barWidth, $h3
$graphics.FillRectangle($brush, $r3)

# Add a subtle arrow line going up?
# Or a circle background?
# Let's keep it simple: Bars on a Green Background.
# Maybe a white circle outline
$circleRect = New-Object System.Drawing.Rectangle 50, 50, 412, 412
$pen.Width = 20
$graphics.DrawEllipse($pen, $circleRect)

# Save
$outputFile = "c:\xampp\htdocs\budget-tracker\apple-touch-icon.png"
$bitmap.Save($outputFile, [System.Drawing.Imaging.ImageFormat]::Png)

# Cleanup
$graphics.Dispose()
$bitmap.Dispose()
$pen.Dispose()
$brush.Dispose()
