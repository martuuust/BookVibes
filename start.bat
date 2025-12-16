@echo off
setlocal
title BookVibes Server
echo ------------------------------------------
echo Iniciando servidor de BookVibes...
echo Puedes acceder en: http://localhost:8000
echo Cierra esta ventana para detener el servidor.
echo ------------------------------------------
set PHP_PORT=8000
set PHP_EXE=
set "PROJECT_ROOT=%~dp0"
if exist "%PROJECT_ROOT%php\php.exe" (
  set "PHP_EXE=%PROJECT_ROOT%php\php.exe"
  echo Usando PHP portable incluido en el proyecto
  "%PROJECT_ROOT%php\php.exe" -d extension_dir="%PROJECT_ROOT%php\ext" -d extension=pdo_mysql -d extension=openssl -d extension=curl -d extension=mbstring -S localhost:%PHP_PORT% -t public
) else (
  echo Usando PHP del sistema
  php -S localhost:%PHP_PORT% -t public
)
pause
