# Script para configurar y ejecutar Cloudflared en Windows

function Test-Command {
    param (
        [string]$Command
    )
    (Get-Command -Name $Command -ErrorAction SilentlyContinue) -ne $null
}

Write-Host "Verificando la instalación de cloudflared..."

if (Test-Command "cloudflared") {
    Write-Host "cloudflared ya está instalado y accesible en el PATH." -ForegroundColor Green
} else {
    Write-Host "cloudflared no se encontró en el PATH." -ForegroundColor Yellow
    Write-Host "Intentando instalar cloudflared usando Chocolatey..."

    if (Test-Command "choco") {
        Write-Host "Chocolatey está instalado. Intentando instalar cloudflared..."
        Start-Process powershell -Verb RunAs -ArgumentList "choco install cloudflared -y" -Wait
        if (Test-Command "cloudflared") {
            Write-Host "cloudflared instalado exitosamente con Chocolatey." -ForegroundColor Green
        } else {
            Write-Host "La instalación de cloudflared con Chocolatey falló o no se añadió al PATH." -ForegroundColor Red
            Write-Host "Por favor, intenta instalar cloudflared manualmente:" -ForegroundColor Red
            Write-Host "1. Descarga el instalador desde: https://github.com/cloudflare/cloudflared/releases" -ForegroundColor Red
            Write-Host "2. Ejecuta el instalador y sigue las instrucciones." -ForegroundColor Red
            Write-Host "3. Asegúrate de que 'C:\Program Files\Cloudflare\Cloudflared\' esté en tu variable de entorno PATH." -ForegroundColor Red
            Write-Host "4. Reinicia tu terminal después de la instalación manual." -ForegroundColor Red
            exit 1
        }
    } else {
        Write-Host "Chocolatey no está instalado." -ForegroundColor Red
        Write-Host "Por favor, instala cloudflared manualmente:" -ForegroundColor Red
        Write-Host "1. Descarga el instalador desde: https://github.com/cloudflare/cloudflared/releases" -ForegroundColor Red
        Write-Host "2. Ejecuta el instalador y sigue las instrucciones." -ForegroundColor Red
        Write-Host "3. Asegúrate de que 'C:\Program Files\Cloudflare\Cloudflared\' esté en tu variable de entorno PATH." -ForegroundColor Red
        Write-Host "4. Reinicia tu terminal después de la instalación manual." -ForegroundColor Red
        exit 1
    }
}

Write-Host "`ncloudflared está listo. Para iniciar el túnel, abre una NUEVA terminal y ejecuta:" -ForegroundColor Cyan
Write-Host "cloudflared tunnel --url http://localhost:8000" -ForegroundColor Cyan
Write-Host "`nRecuerda mantener esa terminal abierta mientras trabajas en tu aplicación." -ForegroundColor Cyan

# Reiniciar el servidor PHP (si no está ya corriendo)
Write-Host "`nVerificando si el servidor PHP está corriendo..."
$phpServerProcess = Get-Process -Name "php" -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -like "*localhost:8000*" }

if ($phpServerProcess) {
    Write-Host "El servidor PHP ya está corriendo." -ForegroundColor Green
} else {
    Write-Host "Iniciando el servidor PHP..."
    Start-Process php -ArgumentList "-S localhost:8000 -t public" -WorkingDirectory "c:\Users\margosa\Desktop\BookVibes\BookVibes" -NoNewWindow
    Write-Host "Servidor PHP iniciado en http://localhost:8000" -ForegroundColor Green
}
