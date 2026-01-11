@echo off
setlocal
title BookVibes Setup

echo ------------------------------------------
echo Configurando BookVibes...
echo ------------------------------------------

:: 1. Crear .env si no existe
if not exist ".env" (
    echo [INFO] Creando archivo .env desde ejemplo...
    copy ".env.example" ".env" >nul
    echo [OK] Archivo .env creado. Por favor editalo con tus claves API.
) else (
    echo [INFO] Archivo .env ya existe.
)

:: 2. Verificar dependencias de Composer
if exist "composer.json" (
    if not exist "vendor" (
        echo [INFO] Carpeta vendor no encontrada. Intentando instalar dependencias...
        call composer install --no-dev --optimize-autoloader
        if %ERRORLEVEL% NEQ 0 (
            echo [WARN] No se pudo ejecutar composer install.
            echo        Asegurate de tener Composer instalado o que el proyecto funcione sin el.
        ) else (
            echo [OK] Dependencias instaladas.
        )
    )
)

:: 3. Iniciar servidor
echo.
echo [INFO] Iniciando servidor...
call start.bat
