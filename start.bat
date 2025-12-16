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
if exist "d:\BookVibes\php\php.exe" (
  set "PHP_EXE=d:\BookVibes\php\php.exe"
  echo Usando PHP portable incluido en el proyecto
  "d:\BookVibes\php\php.exe" -d extension_dir="d:\BookVibes\php\ext" -d extension=pdo_mysql -d extension=openssl -d extension=curl -d extension=mbstring -S localhost:%PHP_PORT% -t public
) else (
  echo Usando PHP del sistema
  php -S localhost:%PHP_PORT% -t public
)
pause
