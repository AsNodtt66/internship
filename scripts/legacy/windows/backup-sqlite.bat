@echo off
setlocal

set SRC=%~dp0database\database.sqlite
set BACKUP_DIR=%~dp0database\backups

if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

for /f "tokens=1-4 delims=/ " %%a in ('date /t') do set DATESTAMP=%%c%%b%%a
for /f "tokens=1-2 delims=: " %%a in ('time /t') do set TIMESTAMP=%%a%%b

set DEST=%BACKUP_DIR%\database-%DATESTAMP%-%TIMESTAMP%.sqlite

copy "%SRC%" "%DEST%" >nul

if %ERRORLEVEL% EQU 0 (
    echo [OK] Backup tersimpan di: %DEST%
) else (
    echo [GAGAL] Backup tidak berhasil, cek apakah database.sqlite ada.
)

pause
