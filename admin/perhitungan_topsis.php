<?php
// admin/perhitungan_topsis.php
require_once 'includes/header.php';
require_once '../config/database.php';
require_once '../functions/topsis_logic.php'; // Logika TOPSIS

$message = '';
$message_type = '';
$topsis_results = null;

// --- Logika Hitung TOPSIS ---
if (isset($_POST['calculate_topsis'])) {
    $topsis_results = calculate_topsis($conn);
    if (isset($topsis_results['error'])) {
        $message = $topsis_results['error'];
        $message_type = "danger";
    } else {
        if (save_topsis_results($conn, $topsis_results['ranked_supplier'])) {
            $message = "Perhitungan TOPSIS selesai dan hasil disimpan!";
            $message_type = "success";
        } else {
            $message = "Perhitungan TOPSIS selesai, tetapi gagal menyimpan hasil.";
            $message_type = "warning";
        }
    }
}

// Ambil data untuk tampilan (jika sudah pernah dihitung atau untuk preview)
$data_for_display = get_topsis_data($conn);
?>

<h2>Perhitungan TOPSIS (Technique for Order Preference by Similarity to Ideal Solution)</h2>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<?php if (!$data_for_display): ?>
    <p>Pastikan Anda sudah memiliki kriteria, supplier, dan nilai yang terisi. Serta bobot kriteria sudah dihitung melalui AHP.</p>
    <p>Silakan cek halaman <a href="kriteria.php">Kriteria</a>, <a href="supplier.php">Supplier</a>, <a href="input_nilai.php">Input Nilai</a>, dan <a href="perhitungan_ahp.php">Perhitungan AHP</a>.</p>
<?php else: ?>
    <div class="form-section">
        <h3>Mulai Perhitungan TOPSIS</h3>
        <p>Pastikan bobot kriteria sudah dihitung dan konsisten dari halaman Perhitungan AHP.</p>
        <form action="perhitungan_topsis.php" method="POST">
            <div class="form-actions">
                <button type="submit" name="calculate_topsis" class="btn btn-primary">Hitung TOPSIS Sekarang</button>
            </div>
        </form>
    </div>

    <?php if ($topsis_results && !isset($topsis_results['error'])): ?>
        <div class="table-section">
            <h3>Matriks Keputusan Awal (X)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <?php foreach ($topsis_results['kriteria'] as $k): ?>
                            <th><?php echo htmlspecialchars($k['nama_kriteria']); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topsis_results['supplier'] as $s_idx => $s): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['nama_supplier']); ?></td>
                            <?php foreach ($topsis_results['kriteria'] as $k_idx => $k): ?>
                                <td><?php echo number_format($topsis_results['nilai_matrix'][$s['id']][$k['id']], 2); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Matriks Normalisasi (R)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <?php foreach ($topsis_results['kriteria'] as $k): ?>
                            <th><?php echo htmlspecialchars($k['nama_kriteria']); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topsis_results['supplier'] as $s_idx => $s): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['nama_supplier']); ?></td>
                            <?php foreach ($topsis_results['kriteria'] as $k_idx => $k): ?>
                                <td><?php echo number_format($topsis_results['normalized_matrix'][$s_idx][$k_idx], 4); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Matriks Normalisasi Terbobot (V)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <?php foreach ($topsis_results['kriteria'] as $k): ?>
                            <th><?php echo htmlspecialchars($k['nama_kriteria']); ?><br>(Bobot: <?php echo number_format($k['bobot'], 4); ?>)</th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topsis_results['supplier'] as $s_idx => $s): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['nama_supplier']); ?></td>
                            <?php foreach ($topsis_results['kriteria'] as $k_idx => $k): ?>
                                <td><?php echo number_format($topsis_results['weighted_normalized_matrix'][$s_idx][$k_idx], 4); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Solusi Ideal Positif (A+) dan Negatif (A-)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Kriteria</th>
                        <th>A+ (Ideal Positif)</th>
                        <th>A- (Ideal Negatif)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topsis_results['kriteria'] as $k_idx => $k): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($k['nama_kriteria']); ?></td>
                            <td><?php echo number_format($topsis_results['ideal_positive'][$k_idx], 4); ?></td>
                            <td><?php echo number_format($topsis_results['ideal_negative'][$k_idx], 4); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Jarak ke Solusi Ideal (D+ dan D-)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>D+ (Jarak ke Ideal Positif)</th>
                        <th>D- (Jarak ke Ideal Negatif)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topsis_results['supplier'] as $s_idx => $s): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['nama_supplier']); ?></td>
                            <td><?php echo number_format($topsis_results['distance_positive'][$s_idx], 4); ?></td>
                            <td><?php echo number_format($topsis_results['distance_negative'][$s_idx], 4); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Nilai Preferensi (V) dan Peringkat Akhir</h3>
            <table>
                <thead>
                    <tr>
                        <th>Peringkat</th>
                        <th>Supplier</th>
                        <th>Nilai Preferensi (V)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topsis_results['ranked_supplier'] as $s): ?>
                        <tr>
                            <td><?php echo $s['ranking']; ?></td>
                            <td><?php echo htmlspecialchars($s['nama_supplier']); ?></td>
                            <td><?php echo number_format($s['nilai_preferensi'], 4); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php
require_once 'includes/footer.php';
$conn->close();
?>
