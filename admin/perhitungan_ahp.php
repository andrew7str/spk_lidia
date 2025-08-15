<?php
// admin/perhitungan_ahp.php
require_once 'includes/header.php';
require_once '../config/database.php';
require_once '../functions/ahp_logic.php';

// Aktifkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';
$message_type = '';
$ahp_results = null;

// --- Logika Simpan Perbandingan ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_perbandingan'])) {
    $perbandingan = $_POST['perbandingan'] ?? [];
    $success_count = 0;
    $error_count = 0;
    $error_details = [];

    foreach ($perbandingan as $k1_id => $values) {
        foreach ($values as $k2_id => $nilai) {
            if ($k1_id == $k2_id) continue; // Skip diagonal
            
            // Simpan nilai perbandingan
            if (save_perbandingan_ahp($conn, $k1_id, $k2_id, $nilai)) {
                $success_count++;
            } else {
                $error_count++;
                $error_details[] = "Gagal menyimpan perbandingan: $k1_id vs $k2_id";
            }
            
            // Simpan nilai kebalikan
            if (save_perbandingan_ahp($conn, $k2_id, $k1_id, 1/$nilai)) {
                $success_count++;
            } else {
                $error_count++;
                $error_details[] = "Gagal menyimpan kebalikan: $k2_id vs $k1_id";
            }
        }
    }

    if ($error_count == 0) {
        $message = "Perbandingan berpasangan berhasil disimpan!";
        $message_type = "success";
    } else {
        $message = "Ada kesalahan saat menyimpan perbandingan. Silakan coba lagi.";
        $message_type = "danger";
        $message .= "<br>" . implode("<br>", $error_details);
    }
}

// --- Logika Hitung AHP ---
if (isset($_POST['calculate_ahp'])) {
    $ahp_results = calculate_ahp($conn);
    if (isset($ahp_results['error'])) {
        $message = $ahp_results['error'];
        $message_type = "danger";
    } else {
        $message = "Perhitungan AHP selesai. CR: " . number_format($ahp_results['cr'], 4);
        $message_type = $ahp_results['is_consistent'] ? "success" : "warning";
        if (!$ahp_results['is_consistent']) {
            $message .= " (Tidak Konsisten! CR > 0.1. Harap perbaiki perbandingan Anda.)";
        }
    }
}

// Ambil data kriteria untuk tampilan
$kriteria_list = get_all_kriteria($conn);
$n_kriteria = count($kriteria_list);

// Ambil perbandingan yang sudah ada untuk mengisi form
$existing_comparisons = get_existing_comparisons($conn);
?>

<h2>Perhitungan AHP (Analytical Hierarchy Process)</h2>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<?php if ($n_kriteria < 2): ?>
    <p>Minimal 2 kriteria diperlukan untuk melakukan perhitungan AHP. Silakan tambahkan kriteria di halaman <a href="kriteria.php">Kriteria</a>.</p>
<?php else: ?>
    <div class="form-section">
        <h3>Input Perbandingan Berpasangan Kriteria</h3>
        <p>Berikan nilai perbandingan antara setiap pasangan kriteria menggunakan skala Saaty (1-9).</p>
        <p class="text-danger"><strong>Pastikan semua kotak diisi sebelum menyimpan!</strong></p>
        
        <form action="perhitungan_ahp.php" method="POST">
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Kriteria</th>
                        <?php foreach ($kriteria_list as $k1): ?>
                            <th><?php echo htmlspecialchars($k1['nama_kriteria']); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kriteria_list as $k1): ?>
                        <tr>
                            <th><?php echo htmlspecialchars($k1['nama_kriteria']); ?></th>
                            <?php foreach ($kriteria_list as $k2): ?>
                                <td>
                                    <?php if ($k1['id'] == $k2['id']): ?>
                                        <div class="text-center">1</div>
                                        <input type="hidden" name="perbandingan[<?php echo $k1['id']; ?>][<?php echo $k2['id']; ?>]" value="1">
                                    <?php else: ?>
                                        <select name="perbandingan[<?php echo $k1['id']; ?>][<?php echo $k2['id']; ?>]" class="form-control" required>
                                            <option value="">Pilih Nilai</option>
                                            <?php foreach ($saaty_scale as $value => $desc): ?>
                                                <option value="<?php echo $value; ?>"
                                                    <?php
                                                    // Cek apakah nilai perbandingan sudah ada
                                                    if (isset($existing_comparisons[$k1['id']][$k2['id']]) && 
                                                        $existing_comparisons[$k1['id']][$k2['id']] == $value) {
                                                        echo 'selected';
                                                    }
                                                    ?>
                                                ><?php echo $value . ' - ' . $desc; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="form-actions">
                <button type="submit" name="submit_perbandingan" class="btn btn-primary">Simpan Perbandingan</button>
                <button type="submit" name="calculate_ahp" class="btn btn-success">Hitung AHP</button>
            </div>
        </form>
    </div>

    <?php if ($ahp_results && !isset($ahp_results['error'])): ?>
        <div class="table-section">
            <h3>Hasil Perhitungan AHP</h3>
            <h4>Matriks Perbandingan</h4>
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Kriteria</th>
                        <?php foreach ($kriteria_list as $k): ?>
                            <th><?php echo htmlspecialchars($k['nama_kriteria']); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 0; $i < $n_kriteria; $i++): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($kriteria_list[$i]['nama_kriteria']); ?></strong></td>
                            <?php for ($j = 0; $j < $n_kriteria; $j++): ?>
                                <td><?php echo number_format($ahp_results['comparison_matrix'][$i][$j], 4); ?></td>
                            <?php endfor; ?>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>

            <h4>Matriks Normalisasi</h4>
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Kriteria</th>
                        <?php foreach ($kriteria_list as $k): ?>
                            <th><?php echo htmlspecialchars($k['nama_kriteria']); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 0; $i < $n_kriteria; $i++): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($kriteria_list[$i]['nama_kriteria']); ?></strong></td>
                            <?php for ($j = 0; $j < $n_kriteria; $j++): ?>
                                <td><?php echo number_format($ahp_results['normalized_matrix'][$i][$j], 4); ?></td>
                            <?php endfor; ?>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>

            <h4>Bobot Prioritas Kriteria</h4>
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Kriteria</th>
                        <th>Bobot</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kriteria_list as $index => $kriteria): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($kriteria['nama_kriteria']); ?></td>
                            <td><?php echo number_format($ahp_results['weights'][$index], 4); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h4>Uji Konsistensi</h4>
            <p>Lambda Max (λmax): <?php echo number_format($ahp_results['lambda_max'], 4); ?></p>
            <p>Consistency Index (CI): <?php echo number_format($ahp_results['ci'], 4); ?></p>
            <p>Consistency Ratio (CR): <?php echo number_format($ahp_results['cr'], 4); ?> (Target CR <= 0.1)</p>
            <p>Status Konsistensi: <strong><?php echo $ahp_results['is_consistent'] ? 'Konsisten' : 'Tidak Konsisten'; ?></strong></p>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php
require_once 'includes/footer.php';
$conn->close();
?>
