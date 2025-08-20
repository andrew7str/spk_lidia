<?php
// functions/topsis_logic.php

/**
 * Mengambil semua data yang diperlukan untuk perhitungan TOPSIS
 * @param mysqli $conn
 * @return array|false Array berisi kriteria, supplier, dan nilai, atau false jika gagal
 */
function get_topsis_data($conn) {
    $kriteria = [];
    $result_kriteria = $conn->query("SELECT id, nama_kriteria, tipe_kriteria, bobot FROM kriteria ORDER BY id ASC");
    if ($result_kriteria->num_rows > 0) {
        while ($row = $result_kriteria->fetch_assoc()) {
            // Pastikan bobot sudah ada dan valid
            if ($row['bobot'] == 0 || is_null($row['bobot'])) {
                return ['error' => 'Bobot kriteria "' . htmlspecialchars($row['nama_kriteria']) . '" belum dihitung atau nol. Harap lakukan perhitungan AHP terlebih dahulu.'];
            }
            $kriteria[] = $row;
        }
    } else {
        return ['error' => 'Tidak ada kriteria yang ditemukan.'];
    }

    $supplier = [];
    $result_supplier = $conn->query("SELECT id, nama_supplier FROM supplier ORDER BY id ASC");
    if ($result_supplier->num_rows > 0) {
        while ($row = $result_supplier->fetch_assoc()) {
            $supplier[] = $row;
        }
    } else {
        return ['error' => 'Tidak ada supplier yang ditemukan.'];
    }

    $nilai = [];
    $result_nilai = $conn->query("SELECT id_supplier, id_kriteria, nilai FROM nilai_supplier");
    if ($result_nilai->num_rows > 0) {
        while ($row = $result_nilai->fetch_assoc()) {
            $nilai[$row['id_supplier']][$row['id_kriteria']] = $row['nilai'];
        }
    } else {
        return ['error' => 'Belum ada nilai supplier yang diinput.'];
    }

    return [
        'kriteria' => $kriteria,
        'supplier' => $supplier,
        'nilai' => $nilai
    ];
}

/**
 * Fungsi utama untuk menghitung TOPSIS sesuai dengan perhitungan manual di SKRIPSI AHP_TOPSIS.pdf
 * @param mysqli $conn
 * @return array|false Array berisi hasil TOPSIS, atau false jika gagal
 */
function calculate_topsis($conn) {
    $data = get_topsis_data($conn);

    if (isset($data['error'])) {
        return $data;
    }

    $kriteria = $data['kriteria'];
    $supplier = $data['supplier'];
    $nilai_matrix_raw = $data['nilai'];

    $n_supplier = count($supplier);
    $n_kriteria = count($kriteria);

    if ($n_supplier == 0 || $n_kriteria == 0) {
        return ['error' => 'Tidak ada supplier atau kriteria untuk dihitung.'];
    }

    // Pastikan semua supplier memiliki nilai untuk semua kriteria
    $nilai_matrix = array_fill(0, $n_supplier, array_fill(0, $n_kriteria, 0.0));
    foreach ($supplier as $s_idx => $s) {
        foreach ($kriteria as $k_idx => $k) {
            if (!isset($nilai_matrix_raw[$s['id']][$k['id']])) {
                return ['error' => 'Nilai untuk supplier "' . htmlspecialchars($s['nama_supplier']) . '" pada kriteria "' . htmlspecialchars($k['nama_kriteria']) . '" belum diisi.'];
            }
            $nilai_matrix[$s_idx][$k_idx] = $nilai_matrix_raw[$s['id']][$k['id']];
        }
    }

    // 1. Matriks Keputusan Awal (X) - sesuai PDF
    // Data dari PDF:
    // Rezeky (A1): [5, 8, 4, 9]
    // Duta Modren (A2): [6, 7, 5, 7]  
    // Serasi (A3): [4, 6, 6, 9]
    // Umi Kids (A4): [5, 7, 5, 6]
    // Kids (A5): [3, 9, 7, 8]

    // 2. Normalisasi Matriks - sesuai rumus di PDF: Rij = Xij / sqrt(sum(Xij^2))
    $normalized_matrix = array_fill(0, $n_supplier, array_fill(0, $n_kriteria, 0.0));
    
    // Hitung akar jumlah kuadrat untuk setiap kolom (kriteria) - sesuai PDF
    $column_sqrt_sum = array_fill(0, $n_kriteria, 0.0);
    for ($j = 0; $j < $n_kriteria; $j++) {
        $sum_sq = 0;
        for ($i = 0; $i < $n_supplier; $i++) {
            $sum_sq += pow($nilai_matrix[$i][$j], 2);
        }
        $column_sqrt_sum[$j] = sqrt($sum_sq);
    }

    // Dari PDF, nilai yang diharapkan:
    // Harga (C1): 10.535654
    // Kualitas (C2): 16.703293  
    // Waktu (C3): 12.288206
    // Pelayanan (C4): 17.635192

    // Lakukan normalisasi: Rij = Xij / sqrt(sum(Xij^2))
    for ($i = 0; $i < $n_supplier; $i++) {
        for ($j = 0; $j < $n_kriteria; $j++) {
            if ($column_sqrt_sum[$j] != 0) {
                $normalized_matrix[$i][$j] = $nilai_matrix[$i][$j] / $column_sqrt_sum[$j];
            } else {
                $normalized_matrix[$i][$j] = 0;
            }
        }
    }

    // 3. Matriks Normalisasi Terbobot (V) - Vij = Rij * Wj
    $weighted_normalized_matrix = array_fill(0, $n_supplier, array_fill(0, $n_kriteria, 0.0));
    for ($i = 0; $i < $n_supplier; $i++) {
        for ($j = 0; $j < $n_kriteria; $j++) {
            $weighted_normalized_matrix[$i][$j] = $normalized_matrix[$i][$j] * $kriteria[$j]['bobot'];
        }
    }

    // 4. Tentukan Solusi Ideal Positif (A+) dan Solusi Ideal Negatif (A-)
    $ideal_positive = array_fill(0, $n_kriteria, 0.0); // A+
    $ideal_negative = array_fill(0, $n_kriteria, 0.0); // A-

    for ($j = 0; $j < $n_kriteria; $j++) {
        $column_values = array_column($weighted_normalized_matrix, $j);
        
        if ($kriteria[$j]['tipe_kriteria'] == 'benefit') {
            // Untuk kriteria benefit: A+ = max, A- = min
            $ideal_positive[$j] = max($column_values);
            $ideal_negative[$j] = min($column_values);
        } else { // 'cost'
            // Untuk kriteria cost: A+ = min, A- = max
            $ideal_positive[$j] = min($column_values);
            $ideal_negative[$j] = max($column_values);
        }
    }

    // Dari PDF, solusi ideal yang diharapkan:
    // Solusi Ideal Positif (A+):
    // - Harga (C1): 0.034711 (Kids)
    // - Kualitas (C2): 0.300605 (Kids)
    // - Waktu (C3): 0.085708 (Rezeky)
    // - Pelayanan (C4): 0.029039 (Rezeky & Serasi)
    
    // Solusi Ideal Negatif (A-):
    // - Harga (C1): 0.069421 (Duta Modern)
    // - Kualitas (C2): 0.200404 (Serasi)
    // - Waktu (C3): 0.149989 (Kids)
    // - Pelayanan (C4): 0.019359 (Umi Kids)

    // 5. Hitung Jarak Setiap Alternatif ke Solusi Ideal - Euclidean Distance
    $distance_positive = array_fill(0, $n_supplier, 0.0); // D+
    $distance_negative = array_fill(0, $n_supplier, 0.0); // D-

    for ($i = 0; $i < $n_supplier; $i++) {
        $sum_sq_pos = 0;
        $sum_sq_neg = 0;
        
        for ($j = 0; $j < $n_kriteria; $j++) {
            // D+ = sqrt(sum((Vij - A+j)^2))
            $sum_sq_pos += pow(($weighted_normalized_matrix[$i][$j] - $ideal_positive[$j]), 2);
            // D- = sqrt(sum((Vij - A-j)^2))
            $sum_sq_neg += pow(($weighted_normalized_matrix[$i][$j] - $ideal_negative[$j]), 2);
        }
        
        $distance_positive[$i] = sqrt($sum_sq_pos);
        $distance_negative[$i] = sqrt($sum_sq_neg);
    }

    // Dari PDF, hasil yang diharapkan:
    // Rezeky (A1): D+ = 0.040634, D- = 0.093926
    // Duta Modern (A2): D+ = 0.078537, D- = 0.054429
    // Serasi (A3): D+ = 0.109594, D- = 0.032989
    // Umi Kids (A4): D+ = 0.074503, D- = 0.055551
    // Kids (A5): D+ = 0.064362, D- = 0.106240

    // 6. Hitung Nilai Preferensi (V) - Vi = D- / (D+ + D-)
    $preferences = array_fill(0, $n_supplier, 0.0);
    for ($i = 0; $i < $n_supplier; $i++) {
        $total_distance = $distance_positive[$i] + $distance_negative[$i];
        if ($total_distance != 0) {
            $preferences[$i] = $distance_negative[$i] / $total_distance;
        } else {
            $preferences[$i] = 0;
        }
    }

    // Dari PDF, hasil preferensi yang diharapkan:
    // Rezeky (A1): 0.698025 (Ranking 1)
    // Kids (A5): 0.622735 (Ranking 2)
    // Umi Kids (A4): 0.427139 (Ranking 3)
    // Duta Modern (A2): 0.409345 (Ranking 4)
    // Serasi (A3): 0.231369 (Ranking 5)

    // 7. Ranking Alternatif berdasarkan nilai preferensi (descending)
    $ranked_supplier = [];
    foreach ($supplier as $s_idx => $s) {
        $ranked_supplier[] = [
            'id' => $s['id'],
            'nama_supplier' => $s['nama_supplier'],
            'nilai_preferensi' => $preferences[$s_idx],
            'D_plus' => $distance_positive[$s_idx],
            'D_minus' => $distance_negative[$s_idx]
        ];
    }

    // Urutkan berdasarkan nilai preferensi (descending)
    usort($ranked_supplier, function($a, $b) {
        return $b['nilai_preferensi'] <=> $a['nilai_preferensi'];
    });

    // Tambahkan ranking
    foreach ($ranked_supplier as $idx => &$s) {
        $s['ranking'] = $idx + 1;
    }
    unset($s); // Break the reference

    return [
        'kriteria' => $kriteria,
        'supplier' => $supplier,
        'nilai_matrix_raw' => $nilai_matrix_raw,
        'nilai_matrix' => $nilai_matrix,
        'column_sqrt_sum' => $column_sqrt_sum, // Untuk debugging dan verifikasi
        'normalized_matrix' => $normalized_matrix,
        'weighted_normalized_matrix' => $weighted_normalized_matrix,
        'ideal_positive' => $ideal_positive,
        'ideal_negative' => $ideal_negative,
        'distance_positive' => $distance_positive,
        'distance_negative' => $distance_negative,
        'preferences' => $preferences,
        'ranked_supplier' => $ranked_supplier
    ];
}

/**
 * Fungsi untuk menyimpan hasil TOPSIS ke database dengan D+ dan D-
 * @param mysqli $conn
 * @param array $ranked_supplier
 * @return bool
 */
function save_topsis_results($conn, $ranked_supplier) {
    $conn->begin_transaction();
    try {
        $tanggal_seleksi = date('Y-m-d');

        // Hapus hasil seleksi sebelumnya untuk tanggal yang sama
        $stmt_delete = $conn->prepare("DELETE FROM hasil_seleksi WHERE tanggal_seleksi = ?");
        $stmt_delete->bind_param("s", $tanggal_seleksi);
        $stmt_delete->execute();
        $stmt_delete->close();

        // Periksa apakah kolom d_plus dan d_minus ada di tabel
        $check_columns = $conn->query("SHOW COLUMNS FROM hasil_seleksi LIKE 'd_plus'");
        $has_d_columns = $check_columns->num_rows > 0;

        if (!$has_d_columns) {
            // Tambahkan kolom d_plus dan d_minus jika belum ada
            $conn->query("ALTER TABLE hasil_seleksi ADD COLUMN d_plus DECIMAL(10,6) DEFAULT NULL");
            $conn->query("ALTER TABLE hasil_seleksi ADD COLUMN d_minus DECIMAL(10,6) DEFAULT NULL");
        }

        foreach ($ranked_supplier as $s) {
            $stmt = $conn->prepare("INSERT INTO hasil_seleksi (id_supplier, nilai_preferensi, ranking, tanggal_seleksi, d_plus, d_minus) VALUES (?, ?, ?, ?, ?, ?)");
            $d_plus = $s['D_plus'] ?? $s['d_plus'] ?? 0;
            $d_minus = $s['D_minus'] ?? $s['d_minus'] ?? 0;
            $stmt->bind_param("idisdd", $s['id'], $s['nilai_preferensi'], $s['ranking'], $tanggal_seleksi, $d_plus, $d_minus);
            $stmt->execute();
            $stmt->close();
        }
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error saving TOPSIS results: " . $e->getMessage());
        return false;
    }
}
?>
