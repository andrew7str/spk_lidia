@echo off
setlocal enabledelayedexpansion

:: ==============================================
:: KONFIGURASI
:: ==============================================
set "mysql_exe=C:\xampp\mysql\bin\mysql.exe"
set "db_name=spk_lidia_fashion"
set "db_user=root"
set "db_pass="
set "log_file=db_setup.log"

:: ==============================================
:: FUNGSI UTAMA
:: ==============================================

:: Header
echo [%date% %time%] Memulai proses setup database > "%log_file%"
echo ============================================ >> "%log_file%"
echo   SCRIPT SETUP DATABASE - AHP >> "%log_file%"
echo ============================================ >> "%log_file%"
echo. >> "%log_file%"
echo Log akan disimpan di: %log_file%
echo.

:: Verifikasi MySQL
if not exist "%mysql_exe%" (
    echo ERROR: mysql.exe tidak ditemukan di %mysql_exe% >> "%log_file%"
    echo ERROR: mysql.exe tidak ditemukan di %mysql_exe%
    goto error
)

:: Buat file SQL sementara
set "temp_sql=%temp%\db_setup_temp.sql"

(
echo /* Script Setup Database AHP */
echo /* Dibuat pada: %date% %time% */

echo -- 1. Hapus database jika sudah ada dan buat baru
echo DROP DATABASE IF EXISTS `%db_name%`;
echo CREATE DATABASE `%db_name%` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
echo USE `%db_name%`;

echo -- 2. Hapus tabel lama jika ada
echo DROP TABLE IF EXISTS hasil_seleksi;
echo DROP TABLE IF EXISTS perbandingan_ahp;
echo DROP TABLE IF EXISTS nilai_supplier;
echo DROP TABLE IF EXISTS supplier;
echo DROP TABLE IF EXISTS kriteria;
echo DROP TABLE IF EXISTS users;

echo -- 3. Buat tabel users
echo CREATE TABLE users (
echo     id INT AUTO_INCREMENT PRIMARY KEY,
echo     username VARCHAR(50) NOT NULL UNIQUE,
echo     password VARCHAR(255) NOT NULL,
echo     role VARCHAR(20) DEFAULT 'admin',
echo     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
echo ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

echo -- 4. Buat tabel kriteria
echo CREATE TABLE kriteria (
echo     id INT AUTO_INCREMENT PRIMARY KEY,
echo     nama_kriteria VARCHAR(100) NOT NULL UNIQUE,
echo     tipe_kriteria ENUM('benefit', 'cost') NOT NULL,
echo     bobot DECIMAL(5,4) DEFAULT 0.0000,
echo     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
echo ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

echo -- 5. Buat tabel supplier
echo CREATE TABLE supplier (
echo     id INT AUTO_INCREMENT PRIMARY KEY,
echo     nama_supplier VARCHAR(100) NOT NULL UNIQUE,
echo     deskripsi TEXT,
echo     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
echo ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

echo -- 6. Buat tabel nilai_supplier
echo CREATE TABLE nilai_supplier (
echo     id INT AUTO_INCREMENT PRIMARY KEY,
echo     id_supplier INT NOT NULL,
echo     id_kriteria INT NOT NULL,
echo     nilai DECIMAL(5,2) NOT NULL,
echo     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
echo     FOREIGN KEY (id_supplier) REFERENCES supplier(id) ON DELETE CASCADE,
echo     FOREIGN KEY (id_kriteria) REFERENCES kriteria(id) ON DELETE CASCADE,
echo     UNIQUE (id_supplier, id_kriteria)
echo ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

echo -- 7. Buat tabel perbandingan_ahp
echo CREATE TABLE perbandingan_ahp (
echo     id INT AUTO_INCREMENT PRIMARY KEY,
echo     kriteria1_id INT NOT NULL,
echo     kriteria2_id INT NOT NULL,
echo     nilai_perbandingan DECIMAL(5,2) NOT NULL,
echo     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
echo     FOREIGN KEY (kriteria1_id) REFERENCES kriteria(id) ON DELETE CASCADE,
echo     FOREIGN KEY (kriteria2_id) REFERENCES kriteria(id) ON DELETE CASCADE,
echo     UNIQUE (kriteria1_id, kriteria2_id)
echo ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

echo -- 8. Buat tabel hasil_seleksi
echo CREATE TABLE hasil_seleksi (
echo     id INT AUTO_INCREMENT PRIMARY KEY,
echo     id_supplier INT NOT NULL,
echo     nilai_preferensi DECIMAL(10,8) NOT NULL,
echo     ranking INT NOT NULL,
echo     tanggal_seleksi DATE NOT NULL,
echo     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
echo     FOREIGN KEY (id_supplier) REFERENCES supplier(id) ON DELETE CASCADE
echo ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

echo -- 9. Insert pengguna default
echo INSERT INTO users (username, password, role) VALUES ('admin', 'password_admin', 'admin');

echo -- 10. Tampilkan pesan sukses
echo SELECT 'Database setup berhasil!' AS message;
) > "%temp_sql%"

:: Jalankan skrip SQL
echo Memproses setup database...
"%mysql_exe%" -u %db_user% %db_pass% -e "source %temp_sql%" >> "%log_file%" 2>&1

if %errorlevel% neq 0 (
    echo ERROR: Gagal menjalankan perintah MySQL >> "%log_file%"
    echo ERROR: Gagal menjalankan perintah MySQL
    goto error
)

:: Tampilkan hasil
echo.
echo ===== HASIL PROSES =====
type "%log_file%"
echo ========================
echo.

:: Bersihkan
if exist "%temp_sql%" del "%temp_sql%"

echo Proses setup database selesai. Silakan periksa phpMyAdmin.
echo.

pause
exit /b 0

:error
echo.
echo ===== ERROR TERJADI =====
type "%log_file%"
echo =========================
echo.
pause
exit /b 1
