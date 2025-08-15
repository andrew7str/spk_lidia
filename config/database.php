<?php
// config/database.php

$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = "";     // Default XAMPP password
$dbname = "spk_lidia_fashion"; // Nama database yang sudah dibuat

// Buat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}
// echo "Koneksi database berhasil!"; // Baris ini bisa dihapus setelah pengujian
?>
