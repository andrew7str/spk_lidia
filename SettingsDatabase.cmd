@echo off
setlocal enabledelayedexpansion

echo ============================================
echo   SCRIPT SETUP DATABASE - AHP (TXT Version)
echo ============================================

:: Lokasi mysql.exe (ubah sesuai XAMPP / MySQL kamu)
set "mysql_exe=C:\xampp\mysql\bin\mysql.exe"
if not exist "%mysql_exe%" (
    echo ERROR: mysql.exe tidak ditemukan di "%mysql_exe%"
    pause
    exit /b
)

:: Minta input file .txt
echo Masukkan path lengkap file TXT berisi query SQL
echo (contoh: D:\db\schema.txt) :
set /p txt_file="TXT File = "

if not defined txt_file (
    echo ERROR: Tidak ada file TXT yang dimasukkan.
    pause
    exit /b
)

if not exist "%txt_file%" (
    echo ERROR: File TXT tidak ditemukan: %txt_file%
    pause
    exit /b
)

echo.
echo Menggunakan file: %txt_file%
echo Membuat database "spk_lidia_fashion" jika belum ada...

:: Eksekusi query dari file TXT
(
echo CREATE DATABASE IF NOT EXISTS spk_lidia_fashion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
echo USE spk_lidia_fashion;
type "%txt_file%"
) | "%mysql_exe%" -u root

if %errorlevel% neq 0 (
    echo ============================================
    echo   ERROR: Gagal mengeksekusi query TXT
    echo ============================================
) else (
    echo ============================================
    echo   Database berhasil dibuat dan tabel diimport
    echo ============================================
)

pause
