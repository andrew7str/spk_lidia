<?php
// admin/perhitungan_ahp.php
require_once 'includes/header.php';
require_once '../config/database.php';
require_once '../functions/ahp_logic.php';

$message = '';
$message_type = '';
$ahp_results = null;

// --- Logika Simpan Perbandingan ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_perbandingan'])) {
    $perbandingan = $_POST['perbandingan'] ?? [];
    $success_count = 0;
    $error_count = 0;
    $error_details = [];

    if (empty($perbandingan)) {
        $message = "Tidak ada data perbandingan yang dikirim.";
        $message_type = "danger";
    } else {
        foreach ($perbandingan as $k1_id => $values) {
            foreach ($values as $k2_id => $nilai) {
                if ($k1_id == $k2_id) continue; // Skip diagonal
                
                // Validasi nilai
                if (empty($nilai) || !is_numeric($nilai)) {
                    $error_details[] = "Nilai perbandingan kriteria $k1_id vs $k2_id tidak valid";
                    $error_count++;
                    continue;
                }
                
                $nilai = (float)$nilai;
                
                // Simpan nilai perbandingan
                if (save_perbandingan_ahp($conn, $k1_id, $k2_id, $nilai)) {
                    $success_count++;
                } else {
                    $error_count++;
                    $error_details[] = "Gagal menyimpan perbandingan: kriteria $k1_id vs $k2_id";
                }
                
                // Simpan nilai kebalikan (reciprocal)
                if ($nilai != 0) {
                    $nilai_kebalikan = 1 / $nilai;
                    if (save_perbandingan_ahp($conn, $k2_id, $k1_id, $nilai_kebalikan)) {
                        $success_count++;
                    } else {
                        $error_count++;
                        $error_details[] = "Gagal menyimpan kebalikan: kriteria $k2_id vs $k1_id";
                    }
                }
            }
        }

        if ($error_count == 0) {
            $message = "Perbandingan berpasangan berhasil disimpan! ($success_count data tersimpan)";
            $message_type = "success";
        } else {
            $message = "Ada $error_count kesalahan saat menyimpan. $success_count data berhasil disimpan.";
            if (!empty($error_details)) {
                $message .= "<br><small>" . implode("<br>", array_slice($error_details, 0, 5)) . "</small>";
            }
            $message_type = "warning";
        }
    }
}

// --- Logika Isi Nilai Default ---
if (isset($_POST['fill_default_values'])) {
    if (fill_default_ahp_values($conn)) {
        $message = "Nilai default sesuai PDF berhasil diisi!";
        $message_type = "success";
    } else {
        $message = "Gagal mengisi nilai default.";
        $message_type = "danger";
    }
}

// --- Logika Hitung AHP ---
if (isset($_POST['calculate_ahp'])) {
    $ahp_results = calculate_ahp($conn);
    if (isset($ahp_results['error'])) {
        $message = $ahp_results['error'];
        $message_type = "danger";
    } else {
        $cr_status = $ahp_results['is_consistent'] ? "Konsisten" : "Tidak Konsisten";
        $message = "Perhitungan AHP selesai. CR: " . number_format($ahp_results['cr'], 4) . " ($cr_status)";
        $message_type = $ahp_results['is_consistent'] ? "success" : "warning";
        if (!$ahp_results['is_consistent']) {
            $message .= "<br><small>CR > 0.1 menunjukkan perbandingan tidak konsisten. Pertimbangkan untuk memperbaiki nilai perbandingan.</small>";
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
    <div class="alert alert-warning">
        <p>Minimal 2 kriteria diperlukan untuk melakukan perhitungan AHP.</p>
        <p>Silakan tambahkan kriteria di halaman <a href="kriteria.php">Kriteria</a>.</p>
    </div>
<?php else: ?>
    <div class="form-section">
        <h3>Input Perbandingan Berpasangan Kriteria</h3>
        <p>Berikan nilai perbandingan antara setiap pasangan kriteria menggunakan skala Saaty (1-9).</p>
        <div class="alert alert-info">
            <strong>Panduan Skala Saaty:</strong><br>
            1 = Sama penting, 3 = Cukup lebih penting, 5 = Sangat lebih penting, 
            7 = Dominan lebih penting, 9 = Mutlak lebih penting<br>
            2, 4, 6, 8 = Nilai antara
        </div>
        
        <form action="perhitungan_ahp.php" method="POST" id="ahpForm">
            <div class="table-responsive">
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
                                            <div class="text-center font-weight-bold">1</div>
                                            <input type="hidden" name="perbandingan[<?php echo $k1['id']; ?>][<?php echo $k2['id']; ?>]" value="1">
                                        <?php else: ?>
                                            <?php
                                            // Nilai default sesuai PDF untuk mempermudah input
                                            $default_values = [
                                                '1_2' => 0.2,      // Harga vs Kualitas = 1/5
                                                '1_3' => 0.333333, // Harga vs Waktu = 1/3  
                                                '1_4' => 3,        // Harga vs Pelayanan = 3
                                                '2_3' => 3,        // Kualitas vs Waktu = 3
                                                '2_4' => 7,        // Kualitas vs Pelayanan = 7
                                                '3_4' => 5,        // Waktu vs Pelayanan = 5
                                            ];
                                            
                                            $key = $k1['id'] . '_' . $k2['id'];
                                            $current_value = '';
                                            
                                            if (isset($existing_comparisons[$k1['id']][$k2['id']])) {
                                                $current_value = $existing_comparisons[$k1['id']][$k2['id']];
                                            } elseif (isset($default_values[$key])) {
                                                $current_value = $default_values[$key];
                                            }
                                            ?>
                                            <input type="number" 
                                                   name="perbandingan[<?php echo $k1['id']; ?>][<?php echo $k2['id']; ?>]" 
                                                   class="form-control form-control-sm" 
                                                   step="0.000001" 
                                                   min="0.111111" 
                                                   max="9" 
                                                   value="<?php echo $current_value; ?>" 
                                                   placeholder="1-9 atau 1/x"
                                                   required>
                                            <small class="text-muted">
                                                <?php 
                                                if ($k1['nama_kriteria'] == 'Harga' && $k2['nama_kriteria'] == 'Kualitas') echo '1/5 = 0.2';
                                                elseif ($k1['nama_kriteria'] == 'Harga' && $k2['nama_kriteria'] == 'Waktu') echo '1/3 = 0.333333';
                                                elseif ($k1['nama_kriteria'] == 'Harga' && $k2['nama_kriteria'] == 'Pelayanan') echo '3';
                                                elseif ($k1['nama_kriteria'] == 'Kualitas' && $k2['nama_kriteria'] == 'Waktu') echo '3';
                                                elseif ($k1['nama_kriteria'] == 'Kualitas' && $k2['nama_kriteria'] == 'Pelayanan') echo '7';
                                                elseif ($k1['nama_kriteria'] == 'Waktu' && $k2['nama_kriteria'] == 'Pelayanan') echo '5';
                                                ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="fill_default_values" class="btn btn-info">
                    <i class="fas fa-magic"></i> Isi Nilai Default PDF
                </button>
                <button type="submit" name="submit_perbandingan" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Perbandingan
                </button>
                <button type="submit" name="calculate_ahp" class="btn btn-success">
                    <i class="fas fa-calculator"></i> Hitung AHP
                </button>
            </div>
        </form>
    </div>

    <!-- Debug Info (hapus setelah testing) -->
    <div class="debug-section" style="margin-top: 20px;">
        <details>
            <summary>Debug Info (klik untuk expand)</summary>
            <div class="alert alert-secondary">
                <strong>Jumlah Kriteria:</strong> <?php echo $n_kriteria; ?><br>
                <strong>Perbandingan Tersimpan:</strong> <?php echo count($existing_comparisons); ?><br>
                <strong>Total Perbandingan Diperlukan:</strong> <?php echo $n_kriteria * $n_kriteria; ?>
            </div>
        </details>
    </div>

    <?php if ($ahp_results && !isset($ahp_results['error'])): ?>
        <div class="table-section">
            <h3>Hasil Perhitungan AHP</h3>
            
            <h4>Matriks Perbandingan</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
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
                                <th><?php echo htmlspecialchars($kriteria_list[$i]['nama_kriteria']); ?></th>
                                <?php for ($j = 0; $j < $n_kriteria; $j++): ?>
                                    <td><?php echo number_format($ahp_results['comparison_matrix'][$i][$j], 4); ?></td>
                                <?php endfor; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <h4>Bobot Prioritas Kriteria</h4>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th>Kriteria</th>
                            <th>Bobot</th>
                            <th>Persentase</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kriteria_list as $index => $kriteria): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($kriteria['nama_kriteria']); ?></td>
                                <td><?php echo number_format($ahp_results['weights'][$index], 4); ?></td>
                                <td><?php echo number_format($ahp_results['weights'][$index] * 100, 2); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h4>Uji Konsistensi</h4>
            <div class="consistency-info">
                <div class="row">
                    <div class="col-md-3">
                        <div class="info-box">
                            <label>Lambda Max (λmax):</label>
                            <span><?php echo number_format($ahp_results['lambda_max'], 4); ?></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box">
                            <label>Consistency Index (CI):</label>
                            <span><?php echo number_format($ahp_results['ci'], 4); ?></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box">
                            <label>Consistency Ratio (CR):</label>
                            <span class="<?php echo $ahp_results['is_consistent'] ? 'text-success' : 'text-warning'; ?>">
                                <?php echo number_format($ahp_results['cr'], 4); ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box">
                            <label>Status:</label>
                            <span class="<?php echo $ahp_results['is_consistent'] ? 'text-success' : 'text-warning'; ?>">
                                <?php echo $ahp_results['is_consistent'] ? 'Konsisten' : 'Tidak Konsisten'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <?php if (!$ahp_results['is_consistent']): ?>
                <div class="alert alert-warning mt-3">
                    <strong>Saran Perbaikan:</strong><br>
                    CR > 0.1 menunjukkan inkonsistensi dalam perbandingan. Pertimbangkan untuk:
                    <ul>
                        <li>Meninjau kembali nilai perbandingan yang mungkin tidak logis</li>
                        <li>Memastikan konsistensi transitif (jika A > B dan B > C, maka A > C)</li>
                        <li>Mengurangi perbedaan nilai yang terlalu ekstrem</li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>

            <?php if (isset($ahp_results['ahp_reference_values']) && !empty($ahp_results['ahp_reference_values'])): ?>
            <h4>Nilai Referensi V AHP (Seperti TOPSIS)</h4>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th>Supplier</th>
                            <th>Skor</th>
                            <th>Ranking</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ahp_results['ahp_reference_values'] as $supplier): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($supplier['nama_supplier']); ?></td>
                                <td><?php echo number_format($supplier['skor_ahp'], 6); ?></td>
                                <td><?php echo $supplier['ranking']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="alert alert-info">
                <strong>Catatan:</strong> Nilai Referensi V AHP ini dihitung berdasarkan data supplier sesuai dengan Tabel IV.8 di PDF revisi AHP-TOPSIS. 
                Hasil ini menunjukkan ranking supplier berdasarkan metode AHP dengan menggunakan bobot kriteria yang telah dihitung.
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<style>
.form-section {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.table-section {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.form-actions {
    margin-top: 20px;
    text-align: center;
}

.form-actions .btn {
    margin: 0 10px;
    padding: 10px 20px;
}

.consistency-info .info-box {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    text-align: center;
    margin-bottom: 10px;
}

.consistency-info .info-box label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
    color: #666;
}

.consistency-info .info-box span {
    font-size: 18px;
    font-weight: bold;
}

.text-muted {
    font-size: 11px;
    color: #6c757d !important;
}

.form-control-sm {
    height: calc(1.5em + 0.5rem + 2px);
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    line-height: 1.5;
    border-radius: 0.2rem;
}
</style>

<script>
// Fungsi untuk mengisi nilai default sesuai PDF
function fillDefaultValues() {
    // Nilai default sesuai PDF
    const defaultValues = {
        '1_2': 0.2,      // Harga vs Kualitas = 1/5
        '1_3': 0.333333, // Harga vs Waktu = 1/3  
        '1_4': 3,        // Harga vs Pelayanan = 3
        '2_3': 3,        // Kualitas vs Waktu = 3
        '2_4': 7,        // Kualitas vs Pelayanan = 7
        '3_4': 5,        // Waktu vs Pelayanan = 5
    };
    
    // Isi nilai ke input fields
    Object.keys(defaultValues).forEach(key => {
        const input = document.querySelector(`input[name="perbandingan[${key.split('_')[0]}][${key.split('_')[1]}]"]`);
        if (input) {
            input.value = defaultValues[key];
        }
    });
    
    alert('Nilai default sesuai PDF telah diisi!');
}

// Tambahkan tombol untuk mengisi nilai default
document.addEventListener('DOMContentLoaded', function() {
    const formActions = document.querySelector('.form-actions');
    if (formActions) {
        const defaultButton = document.createElement('button');
        defaultButton.type = 'button';
        defaultButton.className = 'btn btn-info';
        defaultButton.innerHTML = '<i class="fas fa-magic"></i> Isi Nilai Default PDF';
        defaultButton.onclick = fillDefaultValues;
        
        formActions.insertBefore(defaultButton, formActions.firstChild);
    }
});
</script>

<?php
require_once 'includes/footer.php';
$conn->close();
?>
