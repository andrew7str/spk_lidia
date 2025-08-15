<?php
// admin/includes/header.php
require_once '../functions/auth.php';
redirect_if_not_logged_in(); // Pastikan user sudah login

// Ambil nama user dari sesi
$current_username = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin SPK</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Sertakan Chart.js jika akan digunakan di dashboard atau halaman lain -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="wrapper">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>SPK Lidia Fashion</h3>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="kriteria.php"><i class="fas fa-list-alt"></i> Kriteria</a></li>
                <li><a href="supplier.php"><i class="fas fa-truck"></i> Supplier</a></li>
                <li><a href="input_nilai.php"><i class="fas fa-edit"></i> Input Nilai</a></li>
                <li><a href="perhitungan_ahp.php"><i class="fas fa-calculator"></i> Perhitungan AHP</a></li>
                <li><a href="perhitungan_topsis.php"><i class="fas fa-chart-bar"></i> Perhitungan TOPSIS</a></li>
                <li><a href="hasil_seleksi.php"><i class="fas fa-trophy"></i> Hasil Seleksi</a></li>
                <li><a href="riwayat.php"><i class="fas fa-history"></i> Riwayat</a></li>
                <li><a href="profil.php"><i class="fas fa-user-circle"></i> Profil</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        <div class="main-content">
            <div class="navbar">
                <div class="navbar-left">
                    <!-- Bisa ditambahkan tombol toggle sidebar di sini -->
                </div>
                <div class="navbar-right">
                    <span>Halo, <?php echo htmlspecialchars($current_username); ?></span>
                </div>
            </div>
            <div class="content-area">
                <!-- Konten halaman akan dimuat di sini -->
