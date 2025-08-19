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
 * Fungsi utama untuk menghitung TOPSIS sesuai dengan contoh di SKRIPSI AHP_TOPSIS.pdf
 * @param mysqli $conn
 * @return array|false Array berisi hasil TOPSIS, atau false jika gagal
 */
function calculate_topsis($conn) {
    $data = get_topsis_data($conn);

    if (isset($data['error'])) {
        return $data; // Mengembalikan pesan error dari get_topsis_data
    }

    $kriteria = $data['kriteria'];
    $supplier = $data['supplier'];
    $nilai_matrix_raw = $data['nilai']; // [id_supplier][id_kriteria] => nilai

    $n_supplier = count($supplier);
    $n_kriteria = count($kriteria);

    if ($n_supplier == 0 || $n_kriteria == 0) {
        return ['error' => 'Tidak ada supplier atau kriteria untuk dihitung.'];
    }

    // Buat map ID ke index array dan sebaliknya untuk kriteria dan supplier
    $kriteria_id_to_index = [];
    $kriteria_index_to_id = [];
    foreach ($kriteria as $index => $k) {
        $kriteria_id_to_index[$k['id']] = $index;
        $kriteria_index_to_id[$index] = $k['id'];
    }

    $supplier_id_to_index = [];
    $supplier_index_to_id = [];
    foreach ($supplier as $index => $s) {
        $supplier_id_to_index[$s['id']] = $index;
        $supplier_index_to_id[$index] = $s['id'];
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

    // 1. Matriks Keputusan Awal (X) - sudah di $nilai_matrix

    // 2. Normalisasi Matriks Keputusan (R) - sesuai rumus di PDF
    // Rij = Xij / sqrt(sum(Xij^2)) untuk setiap kolom j
    $normalized_matrix = array_fill(0, $n_supplier, array_fill(0, $n_kriteria, 0.0));
    $divisor = array_fill(0, $n_kriteria, 0.0); // Pembagi untuk normalisasi

    // Hitung pembagi untuk setiap kriteria (akar dari jumlah kuadrat kolom)
    for ($j = 0; $j < $n_kriteria; $j++) {
        $sum_sq = 0;
        for ($i = 0; $i < $n_supplier; $i++) {
            $sum_sq += pow($nilai_matrix[$i][$j], 2);
        }
        $divisor[$j] = sqrt($sum_sq);
    }

    // Lakukan normalisasi: Rij = Xij / sqrt(sum(Xij^2))
    for ($i = 0; $i < $n_supplier; $i++) {
        for ($j = 0; $j < $n_kriteria; $j++) {
            if ($divisor[$j] != 0) {
                $normalized_matrix[$i][$j] = $nilai_matrix[$i][$j] / $divisor[$j];
            } else {
                $normalized_matrix[$i][$j] = 0; // Hindari pembagian nol
            }
        }
    }

    // 3. Matriks Normalisasi Terbobot (V) - sesuai rumus di PDF
    // Vij = Rij * Wj (matriks ternormalisasi dikali bobot dari AHP)
    $weighted_normalized_matrix = array_fill(0, $n_supplier, array_fill(0, $n_kriteria, 0.0));
    foreach ($supplier as $s_idx => $s) {
        foreach ($kriteria as $k_idx => $k) {
            $weighted_normalized_matrix[$s_idx][$k_idx] = $normalized_matrix[$s_idx][$k_idx] * $k['bobot'];
        }
    }

    // 4. Tentukan Solusi Ideal Positif (A+) dan Solusi Ideal Negatif (A-) - sesuai PDF
    $ideal_positive = array_fill(0, $n_kriteria, 0.0); // A+
    $ideal_negative = array_fill(0, $n_kriteria, 0.0); // A-

    foreach ($kriteria as $k_idx => $k) {
        $column_values = array_column($weighted_normalized_matrix, $k_idx);
        
        // Untuk kriteria benefit: A+ = max, A- = min
        // Untuk kriteria cost: A+ = min, A- = max
        if ($k['tipe_kriteria'] == 'benefit') {
            $ideal_positive[$k_idx] = max($column_values);
            $ideal_negative[$k_idx] = min($column_values);
        } else { // 'cost'
            $ideal_positive[$k_idx] = min($column_values);
            $ideal_negative[$k_idx] = max($column_values);
        }
    }

    // 5. Hitung Jarak Setiap Alternatif ke Solusi Ideal - sesuai rumus di PDF
    // D+i = sqrt(sum((Vij - A+j)^2)) dan D-i = sqrt(sum((Vij - A-j)^2))
    $distance_positive = array_fill(0, $n_supplier, 0.0); // D+ [index_supplier]
    $distance_negative = array_fill(0, $n_supplier, 0.0); // D- [index_supplier]

    for ($i = 0; $i < $n_supplier; $i++) {
        $sum_sq_pos = 0;
        $sum_sq_neg = 0;
        for ($j = 0; $j < $n_kriteria; $j++) {
            $sum_sq_pos += pow(($weighted_normalized_matrix[$i][$j] - $ideal_positive[$j]), 2);
            $sum_sq_neg += pow(($weighted_normalized_matrix[$i][$j] - $ideal_negative[$j]), 2);
        }
        $distance_positive[$i] = sqrt($sum_sq_pos);
        $distance_negative[$i] = sqrt($sum_sq_neg);
    }

    // 6. Hitung Nilai Preferensi (V) - sesuai rumus di PDF
    // Vi = D-i / (D+i + D-i)
    $preferences = array_fill(0, $n_supplier, 0.0); // V [index_supplier]
    for ($i = 0; $i < $n_supplier; $i++) {
        $total_distance = $distance_positive[$i] + $distance_negative[$i];
        if ($total_distance != 0) {
            $preferences[$i] = $distance_negative[$i] / $total_distance;
        } else {
            $preferences[$i] = 0; // Hindari pembagian nol
        }
    }

    // 7. Ranking Alternatif (urutkan berdasarkan nilai preferensi tertinggi)
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

    // Urutkan berdasarkan nilai preferensi (descending - tertinggi ke terendah)
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
        'nilai_matrix_raw' => $nilai_matrix_raw, // Nilai asli dari DB
        'nilai_matrix' => $nilai_matrix, // Matriks nilai yang sudah di-map ke indeks
        'divisor' => $divisor, // Pembagi normalisasi untuk debugging
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
 * Fungsi untuk menyimpan hasil TOPSIS ke database
 * @param mysqli $conn
 * @param array $ranked_supplier
 * @return bool
 */
function save_topsis_results($conn, $ranked_supplier) {
    $conn->begin_transaction();
    try {
        $tanggal_seleksi = date('Y-m-d');

        // Hapus hasil seleksi sebelumnya untuk tanggal yang sama
        // Ini penting agar riwayat tidak duplikat untuk tanggal yang sama
        $stmt_delete = $conn->prepare("DELETE FROM hasil_seleksi WHERE tanggal_seleksi = ?");
        $stmt_delete->bind_param("s", $tanggal_seleksi);
        $stmt_delete->execute();
        $stmt_delete->close();

        foreach ($ranked_supplier as $s) {
            $stmt = $conn->prepare("INSERT INTO hasil_seleksi (id_supplier, nilai_preferensi, ranking, tanggal_seleksi) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("idis", $s['id'], $s['nilai_preferensi'], $s['ranking'], $tanggal_seleksi);
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
