<?php
// admin/hasil_seleksi.php
require_once 'includes/header.php';
require_once '../config/database.php';

// Ambil hasil seleksi terbaru dengan detail D+ dan D-
$ranked_results = [];
$detailed_results = [];

// Query untuk mendapatkan hasil seleksi dengan D+ dan D-
$result = $conn->query("
    SELECT hs.ranking, s.nama_supplier, hs.nilai_preferensi, hs.tanggal_seleksi, hs.d_plus, hs.d_minus
    FROM hasil_seleksi hs
    JOIN supplier s ON hs.id_supplier = s.id
    WHERE hs.tanggal_seleksi = (SELECT MAX(tanggal_seleksi) FROM hasil_seleksi)
    ORDER BY hs.ranking ASC
");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ranked_results[] = $row;
        $detailed_results[] = $row;
    }
}

// Jika tidak ada kolom d_plus dan d_minus, ambil dari session atau hitung ulang
if (empty($detailed_results) || !isset($detailed_results[0]['d_plus'])) {
    // Fallback: ambil dari hasil perhitungan TOPSIS terakhir
    if (isset($_SESSION['topsis_results'])) {
        $topsis_data = $_SESSION['topsis_results'];
        $detailed_results = $topsis_data['ranked_supplier'] ?? [];
    }
}

$chart_labels = [];
$chart_data = [];
if (!empty($ranked_results)) {
    foreach ($ranked_results as $res) {
        $chart_labels[] = htmlspecialchars($res['nama_supplier']);
        $chart_data[] = $res['nilai_preferensi'];
    }
}
?>

<h2>Hasil Seleksi Supplier</h2>

<?php if (empty($ranked_results)): ?>
    <div class="alert alert-warning">
        <p>Belum ada hasil seleksi yang tersedia. Silakan lakukan perhitungan TOPSIS terlebih dahulu.</p>
        <p><a href="perhitungan_topsis.php" class="btn btn-primary">Lakukan Perhitungan TOPSIS</a></p>
    </div>
<?php else: ?>
    <!-- Tabel Detail Perhitungan TOPSIS -->
    <div class="form-section">
        <h3>Detail Perhitungan TOPSIS</h3>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Supplier</th>
                        <th>D+</th>
                        <th>D-</th>
                        <th>V (Preferensi)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detailed_results as $res): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($res['nama_supplier']); ?></strong></td>
                            <td><?php echo number_format($res['d_plus'] ?? $res['D_plus'] ?? 0, 6); ?></td>
                            <td><?php echo number_format($res['d_minus'] ?? $res['D_minus'] ?? 0, 6); ?></td>
                            <td><?php echo number_format($res['nilai_preferensi'], 6); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tabel Hasil Ranking -->
    <div class="form-section">
        <h3>Tabel Hasil Ranking</h3>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Supplier</th>
                        <th>V (Preferensi)</th>
                        <th>Ranking</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ranked_results as $res): ?>
                        <tr <?php echo $res['ranking'] == 1 ? 'class="table-success"' : ''; ?>>
                            <td><strong><?php echo htmlspecialchars($res['nama_supplier']); ?></strong></td>
                            <td><?php echo number_format($res['nilai_preferensi'], 6); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $res['ranking'] == 1 ? 'success' : ($res['ranking'] <= 3 ? 'warning' : 'secondary'); ?>">
                                    <?php echo $res['ranking']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (!empty($ranked_results)): ?>
            <div class="alert alert-success">
                <h5><i class="fas fa-trophy"></i> Supplier Terbaik</h5>
                <p><strong><?php echo htmlspecialchars($ranked_results[0]['nama_supplier']); ?></strong> 
                dengan nilai preferensi <strong><?php echo number_format($ranked_results[0]['nilai_preferensi'], 6); ?></strong></p>
                <small class="text-muted">Tanggal Seleksi: <?php echo htmlspecialchars($ranked_results[0]['tanggal_seleksi']); ?></small>
            </div>
        <?php endif; ?>
    </div>

    <!-- Visualisasi Chart -->
    <div class="chart-container">
        <h3>Visualisasi Peringkat Supplier</h3>
        <canvas id="rankingChart"></canvas>
    </div>

    <script>
        const labels = <?php echo json_encode($chart_labels); ?>;
        const data = <?php echo json_encode($chart_data); ?>;

        const ctx = document.getElementById('rankingChart').getContext('2d');
        const rankingChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Nilai Preferensi',
                    data: data,
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',   // Hijau untuk ranking 1
                        'rgba(255, 193, 7, 0.8)',   // Kuning untuk ranking 2
                        'rgba(255, 152, 0, 0.8)',   // Orange untuk ranking 3
                        'rgba(108, 117, 125, 0.8)', // Abu untuk ranking 4
                        'rgba(220, 53, 69, 0.8)'    // Merah untuk ranking 5
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(255, 152, 0, 1)',
                        'rgba(108, 117, 125, 1)',
                        'rgba(220, 53, 69, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Perbandingan Nilai Preferensi Supplier'
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 1.0,
                        title: {
                            display: true,
                            text: 'Nilai Preferensi'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Supplier'
                        }
                    }
                }
            }
        });
    </script>
<?php endif; ?>

<?php
require_once 'includes/footer.php';
$conn->close();
?>
