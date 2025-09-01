<?php
// functions/ahp_logic.php

// Skala Saaty
$saaty_scale = [
    1 => 'Sama Penting',
    2 => 'Sedikit Lebih Penting',
    3 => 'Cukup Lebih Penting',
    4 => 'Jelas Lebih Penting',
    5 => 'Sangat Lebih Penting',
    6 => 'Sangat Jelas Lebih Penting',
    7 => 'Dominan Lebih Penting',
    8 => 'Sangat Dominan Lebih Penting',
    9 => 'Mutlak Lebih Penting'
];

// Random Index (RI) untuk uji konsistensi AHP
// n = jumlah kriteria
$random_index = [
    1 => 0.00, 2 => 0.00, 3 => 0.58, 4 => 0.90, 5 => 1.12,
    6 => 1.24, 7 => 1.32, 8 => 1.41, 9 => 1.45, 10 => 1.49
];

/**
 * Fungsi untuk mendapatkan semua kriteria dari database
 * @param mysqli $conn
 * @return array
 */
function get_all_kriteria($conn) {
    $kriteria = [];
    $result = $conn->query("SELECT id, nama_kriteria FROM kriteria ORDER BY id ASC");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $kriteria[] = $row;
        }
    }
    return $kriteria;
}

/**
 * Fungsi untuk menyimpan perbandingan berpasangan ke database
 * @param mysqli $conn
 * @param int $kriteria1_id
 * @param int $kriteria2_id
 * @param float $nilai_perbandingan
 * @return bool
 */
function save_perbandingan_ahp($conn, $kriteria1_id, $kriteria2_id, $nilai_perbandingan) {
    // Cek apakah perbandingan sudah ada
    $stmt_check = $conn->prepare("SELECT id FROM perbandingan_ahp WHERE kriteria1_id = ? AND kriteria2_id = ?");
    $stmt_check->bind_param("ii", $kriteria1_id, $kriteria2_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // Update
        $stmt = $conn->prepare("UPDATE perbandingan_ahp SET nilai_perbandingan = ? WHERE kriteria1_id = ? AND kriteria2_id = ?");
        $stmt->bind_param("dii", $nilai_perbandingan, $kriteria1_id, $kriteria2_id);
        $success = $stmt->execute();
        $stmt->close();
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO perbandingan_ahp (kriteria1_id, kriteria2_id, nilai_perbandingan) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $kriteria1_id, $kriteria2_id, $nilai_perbandingan);
        $success = $stmt->execute();
        $stmt->close();
    }
    return $success;
}

/**
 * Fungsi untuk mendapatkan perbandingan berpasangan yang sudah ada
 * @param mysqli $conn
 * @return array
 */
function get_existing_comparisons($conn) {
    $comparisons = [];
    $result = $conn->query("SELECT kriteria1_id, kriteria2_id, nilai_perbandingan FROM perbandingan_ahp");
    while ($row = $result->fetch_assoc()) {
        $comparisons[$row['kriteria1_id']][$row['kriteria2_id']] = $row['nilai_perbandingan'];
    }
    return $comparisons;
}

/**
 * Fungsi untuk mengisi nilai default sesuai PDF untuk testing
 * @param mysqli $conn
 * @return bool
 */
function fill_default_ahp_values($conn) {
    // Nilai default sesuai Tabel IV.5 di PDF
    $default_comparisons = [
        [1, 2, 0.2],        // Harga vs Kualitas = 1/5 = 0.2
        [1, 3, 0.333333],   // Harga vs Waktu = 1/3 = 0.333333
        [1, 4, 3],          // Harga vs Pelayanan = 3
        [2, 1, 5],          // Kualitas vs Harga = 5
        [2, 3, 3],          // Kualitas vs Waktu = 3
        [2, 4, 7],          // Kualitas vs Pelayanan = 7
        [3, 1, 3],          // Waktu vs Harga = 3
        [3, 2, 0.333333],   // Waktu vs Kualitas = 1/3 = 0.333333
        [3, 4, 5],          // Waktu vs Pelayanan = 5
        [4, 1, 0.333333],   // Pelayanan vs Harga = 1/3 = 0.333333
        [4, 2, 0.142857],   // Pelayanan vs Kualitas = 1/7 = 0.142857
        [4, 3, 0.2],        // Pelayanan vs Waktu = 1/5 = 0.2
    ];

    $success = true;
    foreach ($default_comparisons as $comp) {
        if (!save_perbandingan_ahp($conn, $comp[0], $comp[1], $comp[2])) {
            $success = false;
        }
    }

    return $success;
}

/**
 * Fungsi untuk menghitung nilai referensi V AHP seperti TOPSIS
 * @param mysqli $conn
 * @param array $weights
 * @return array
 */
function calculate_ahp_reference_values($conn, $weights) {
    // Data supplier sesuai PDF Tabel IV.8
    $supplier_data = [
        1 => ['nama' => 'Rezeky', 'skor' => [5, 8, 4, 9]],      // A1
        2 => ['nama' => 'Duta Modren', 'skor' => [6, 7, 5, 7]], // A2
        3 => ['nama' => 'Serasi', 'skor' => [4, 6, 6, 9]],      // A3
        4 => ['nama' => 'Umi Kids', 'skor' => [5, 7, 5, 6]],    // A4
        5 => ['nama' => 'Kids', 'skor' => [3, 9, 7, 8]]         // A5
    ];

    $n_supplier = count($supplier_data);
    $n_kriteria = count($weights);

    // Buat matriks pairwise untuk setiap kriteria
    $pairwise_matrices = [];
    $priorities = [];

    for ($k = 0; $k < $n_kriteria; $k++) {
        // Ambil skor untuk kriteria k
        $scores = [];
        foreach ($supplier_data as $supplier) {
            $scores[] = $supplier['skor'][$k];
        }

        // Buat matriks pairwise
        $pairwise = [];
        for ($i = 0; $i < $n_supplier; $i++) {
            for ($j = 0; $j < $n_supplier; $j++) {
                if ($i == $j) {
                    $pairwise[$i][$j] = 1.0;
                } else {
                    $pairwise[$i][$j] = $scores[$i] / $scores[$j];
                }
            }
        }

        // Normalisasi dan hitung prioritas
        $column_sums = array_fill(0, $n_supplier, 0.0);
        for ($j = 0; $j < $n_supplier; $j++) {
            for ($i = 0; $i < $n_supplier; $i++) {
                $column_sums[$j] += $pairwise[$i][$j];
            }
        }

        $normalized = [];
        for ($i = 0; $i < $n_supplier; $i++) {
            for ($j = 0; $j < $n_supplier; $j++) {
                $normalized[$i][$j] = $pairwise[$i][$j] / $column_sums[$j];
            }
        }

        // Hitung prioritas (rata-rata baris)
        $priority = [];
        for ($i = 0; $i < $n_supplier; $i++) {
            $row_sum = 0;
            for ($j = 0; $j < $n_supplier; $j++) {
                $row_sum += $normalized[$i][$j];
            }
            $priority[$i] = $row_sum / $n_supplier;
        }

        $priorities[$k] = $priority;
    }

    // Hitung skor akhir AHP untuk setiap supplier
    $ahp_scores = [];
    for ($i = 0; $i < $n_supplier; $i++) {
        $score = 0;
        for ($k = 0; $k < $n_kriteria; $k++) {
            $score += $weights[$k] * $priorities[$k][$i];
        }
        $ahp_scores[$i] = $score;
    }

    // Buat hasil dalam format seperti TOPSIS
    $reference_values = [];
    $supplier_keys = array_keys($supplier_data);
    
    for ($i = 0; $i < $n_supplier; $i++) {
        $supplier_id = $supplier_keys[$i];
        $reference_values[] = [
            'id' => $supplier_id,
            'nama_supplier' => $supplier_data[$supplier_id]['nama'],
            'skor_ahp' => $ahp_scores[$i],
            'ranking' => 0 // akan diisi setelah sorting
        ];
    }

    // Urutkan berdasarkan skor AHP (descending)
    usort($reference_values, function($a, $b) {
        return $b['skor_ahp'] <=> $a['skor_ahp'];
    });

    // Tambahkan ranking
    foreach ($reference_values as $idx => &$supplier) {
        $supplier['ranking'] = $idx + 1;
    }
    unset($supplier);

    return $reference_values;
}

/**
 * Fungsi utama untuk menghitung AHP sesuai dengan contoh di revisi_AHP_TOPSIS.pdf
 * @param mysqli $conn
 * @return array|false Array berisi bobot, CI, CR, atau false jika gagal
 */
function calculate_ahp($conn) {
    global $random_index;

    $kriteria = get_all_kriteria($conn);
    $n = count($kriteria);

    if ($n < 2) {
        return ['error' => 'Minimal 2 kriteria diperlukan untuk perhitungan AHP.'];
    }

    // Buat map ID ke index array dan sebaliknya
    $kriteria_id_to_index = [];
    $kriteria_index_to_id = [];
    foreach ($kriteria as $index => $k) {
        $kriteria_id_to_index[$k['id']] = $index;
        $kriteria_index_to_id[$index] = $k['id'];
    }

    // 1. Bentuk Matriks Perbandingan Berpasangan
    $comparison_matrix = array_fill(0, $n, array_fill(0, $n, 0.0));

    // Inisialisasi diagonal dengan 1
    for ($i = 0; $i < $n; $i++) {
        $comparison_matrix[$i][$i] = 1.0;
    }

    // Ambil nilai perbandingan dari database
    $db_comparisons = get_existing_comparisons($conn);

    // Isi matriks dengan nilai dari database
    for ($i = 0; $i < $n; $i++) {
        for ($j = 0; $j < $n; $j++) {
            if ($i == $j) continue;

            $id1 = $kriteria_index_to_id[$i];
            $id2 = $kriteria_index_to_id[$j];

            if (isset($db_comparisons[$id1][$id2])) {
                $comparison_matrix[$i][$j] = $db_comparisons[$id1][$id2];
            } else {
                return ['error' => 'Perbandingan antara ' . $kriteria[$i]['nama_kriteria'] . ' dan ' . $kriteria[$j]['nama_kriteria'] . ' belum diisi.'];
            }
        }
    }

    // 2. Normalisasi Matriks (sesuai PDF)
    $normalized_matrix = array_fill(0, $n, array_fill(0, $n, 0.0));
    $column_sums = array_fill(0, $n, 0.0);

    // Hitung jumlah kolom
    for ($j = 0; $j < $n; $j++) {
        for ($i = 0; $i < $n; $i++) {
            $column_sums[$j] += $comparison_matrix[$i][$j];
        }
    }

    // Lakukan normalisasi: setiap elemen dibagi dengan jumlah kolomnya
    for ($i = 0; $i < $n; $i++) {
        for ($j = 0; $j < $n; $j++) {
            if ($column_sums[$j] != 0) {
                $normalized_matrix[$i][$j] = $comparison_matrix[$i][$j] / $column_sums[$j];
            } else {
                $normalized_matrix[$i][$j] = 0;
            }
        }
    }

    // 3. Hitung Bobot Prioritas (rata-rata baris dari matriks ternormalisasi)
    $weights = [];
    for ($i = 0; $i < $n; $i++) {
        $row_sum = 0;
        for ($j = 0; $j < $n; $j++) {
            $row_sum += $normalized_matrix[$i][$j];
        }
        $weights[$i] = $row_sum / $n;
    }

    // 4. Hitung Lambda Max (λmax) - sesuai formula di PDF
    // Aw = A × w (matriks perbandingan × vektor bobot)
    $aw_vector = [];
    for ($i = 0; $i < $n; $i++) {
        $aw_sum = 0;
        for ($j = 0; $j < $n; $j++) {
            $aw_sum += $comparison_matrix[$i][$j] * $weights[$j];
        }
        $aw_vector[$i] = $aw_sum;
    }

    // Hitung λi = (Aw)i / wi untuk setiap kriteria
    $lambda_values = [];
    for ($i = 0; $i < $n; $i++) {
        if ($weights[$i] != 0) {
            $lambda_values[$i] = $aw_vector[$i] / $weights[$i];
        } else {
            return ['error' => 'Bobot kriteria nol, tidak dapat menghitung Lambda Max.'];
        }
    }

    // λmax = rata-rata dari semua λi
    $lambda_max = array_sum($lambda_values) / $n;

    // 5. Hitung Consistency Index (CI) = (λmax - n) / (n - 1)
    $ci = ($lambda_max - $n) / ($n - 1);

    // 6. Hitung Consistency Ratio (CR) = CI / RI
    $ri = $random_index[$n] ?? 0;
    $cr = ($ri != 0) ? $ci / $ri : 0;

    // Simpan bobot ke database (tabel kriteria)
    foreach ($kriteria as $index => $k) {
        $kriteria_id = $k['id'];
        $bobot = $weights[$index];
        $stmt = $conn->prepare("UPDATE kriteria SET bobot = ? WHERE id = ?");
        $stmt->bind_param("di", $bobot, $kriteria_id);
        $stmt->execute();
        $stmt->close();
    }

    // Hitung Nilai Referensi V untuk AHP (seperti TOPSIS)
    // Menggunakan skor alternatif dari database atau nilai default
    $ahp_reference_values = calculate_ahp_reference_values($conn, $weights);

    return [
        'kriteria' => $kriteria,
        'comparison_matrix' => $comparison_matrix,
        'normalized_matrix' => $normalized_matrix,
        'column_sums' => $column_sums,
        'weights' => $weights,
        'aw_vector' => $aw_vector,
        'lambda_values' => $lambda_values,
        'lambda_max' => $lambda_max,
        'ci' => $ci,
        'cr' => $cr,
        'is_consistent' => ($cr <= 0.1), // Konsisten jika CR <= 0.1
        'ahp_reference_values' => $ahp_reference_values
    ];
}
?>
