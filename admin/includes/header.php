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
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h3>SPK Lidia Fashion</h3>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" data-tooltip="Dashboard"><i class="fas fa-tachometer-alt"></i><span class="menu-text">Dashboard</span></a></li>
                <li><a href="kriteria.php" data-tooltip="Kriteria"><i class="fas fa-list-alt"></i><span class="menu-text">Kriteria</span></a></li>
                <li><a href="supplier.php" data-tooltip="Supplier"><i class="fas fa-truck"></i><span class="menu-text">Supplier</span></a></li>
                <li><a href="input_nilai.php" data-tooltip="Input Nilai"><i class="fas fa-edit"></i><span class="menu-text">Input Nilai</span></a></li>
                <li><a href="perhitungan_ahp.php" data-tooltip="Perhitungan AHP"><i class="fas fa-calculator"></i><span class="menu-text">Perhitungan AHP</span></a></li>
                <li><a href="perhitungan_topsis.php" data-tooltip="Perhitungan TOPSIS"><i class="fas fa-chart-bar"></i><span class="menu-text">Perhitungan TOPSIS</span></a></li>
                <li><a href="hasil_seleksi.php" data-tooltip="Hasil Seleksi"><i class="fas fa-trophy"></i><span class="menu-text">Hasil Seleksi</span></a></li>
                <li><a href="riwayat.php" data-tooltip="Riwayat"><i class="fas fa-history"></i><span class="menu-text">Riwayat</span></a></li>
                <li><a href="profil.php" data-tooltip="Profil"><i class="fas fa-user-circle"></i><span class="menu-text">Profil</span></a></li>
                <li><a href="../logout.php" data-tooltip="Logout"><i class="fas fa-sign-out-alt"></i><span class="menu-text">Logout</span></a></li>
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
