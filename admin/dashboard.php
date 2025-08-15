<?php
// admin/dashboard.php
require_once 'includes/header.php'; // Sertakan header
?>

                <h2>Dashboard</h2>
                <p>Selamat datang di Sistem Pendukung Keputusan Pemilihan Supplier Toko Lidia Fashion.</p>

                <div class="dashboard-cards">
                    <div class="card">
                        <h3>Total Kriteria</h3>
                        <p>5</p> <!-- Data ini nanti diambil dari database -->
                    </div>
                    <div class="card">
                        <h3>Total Supplier</h3>
                        <p>10</p> <!-- Data ini nanti diambil dari database -->
                    </div>
                    <div class="card">
                        <h3>Seleksi Terakhir</h3>
                        <p>2025-03-15</p> <!-- Data ini nanti diambil dari database -->
                    </div>
                </div>

                <!-- Contoh area untuk grafik -->
                <div class="chart-container">
                    <h3>Peringkat Supplier Terakhir</h3>
                    <canvas id="lastRankingChart"></canvas>
                </div>

<?php
require_once 'includes/footer.php'; // Sertakan footer
?>
<script>
    // Contoh data untuk Chart.js (nanti diambil dari PHP)
    const labels = ['Supplier Kids', 'Supplier Serasi', 'Supplier Rejeky', 'Supplier Duta Modern', 'Supplier Umi Kids'];
    const data = [0.8143, 0.5214, 0.4493, 0.1874, 0.3478]; // Nilai preferensi dari perhitungan manual

    const ctx = document.getElementById('lastRankingChart').getContext('2d');
    const lastRankingChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Nilai Preferensi',
                data: data,
                backgroundColor: [
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(153, 102, 255, 0.6)',
                    'rgba(255, 159, 64, 0.6)',
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(54, 162, 235, 0.6)'
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 1 // Nilai preferensi TOPSIS biasanya antara 0 dan 1
                }
            }
        }
    });
</script>
