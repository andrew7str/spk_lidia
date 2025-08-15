<?php
// admin/hasil_seleksi.php
require_once 'includes/header.php';
require_once '../config/database.php';

$ranked_results = [];
$result = $conn->query("
    SELECT hs.ranking, s.nama_supplier, hs.nilai_preferensi, hs.tanggal_seleksi
    FROM hasil_seleksi hs
    JOIN supplier s ON hs.id_supplier = s.id
    WHERE hs.tanggal_seleksi = (SELECT MAX(tanggal_seleksi) FROM hasil_seleksi)
    ORDER BY hs.ranking ASC
");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ranked_results[] = $row;
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
    <p>Belum ada hasil seleksi yang tersedia. Silakan lakukan perhitungan TOPSIS terlebih dahulu.</p>
<?php else: ?>
    <div class="table-section">
        <h3>Peringkat Supplier Terbaru (Tanggal: <?php echo htmlspecialchars($ranked_results[0]['tanggal_seleksi']); ?>)</h3>
        <table>
            <thead>
                <tr>
                    <th>Peringkat</th>
                    <th>Nama Supplier</th>
                    <th>Nilai Preferensi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ranked_results as $res): ?>
                    <tr>
                        <td><?php echo $res['ranking']; ?></td>
                        <td><?php echo htmlspecialchars($res['nama_supplier']); ?></td>
                        <td><?php echo number_format($res['nilai_preferensi'], 4); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

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
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 1.0 // Nilai preferensi TOPSIS biasanya antara 0 dan 1
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
