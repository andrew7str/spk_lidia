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
 * Fungsi utama untuk menghitung AHP
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
                // Jika perbandingan belum ada, ini adalah masalah.
                // Untuk AHP, semua perbandingan harus ada.
                // Anda bisa mengembalikan error atau mengisi dengan nilai default (misal 1)
                // Namun, mengisi dengan default bisa membuat hasil tidak akurat.
                return ['error' => 'Perbandingan antara ' . $kriteria[$i]['nama_kriteria'] . ' dan ' . $kriteria[$j]['nama_kriteria'] . ' belum diisi.'];
            }
        }
    }

    // 2. Normalisasi Matriks
    $normalized_matrix = array_fill(0, $n, array_fill(0, $n, 0.0));
    $column_sums = array_fill(0, $n, 0.0);

    // Hitung jumlah kolom
    for ($j = 0; $j < $n; $j++) {
        for ($i = 0; $i < $n; $i++) {
            $column_sums[$j] += $comparison_matrix[$i][$j];
        }
    }

    // Lakukan normalisasi
    for ($i = 0; $i < $n; $i++) {
        for ($j = 0; $j < $n; $j++) {
            if ($column_sums[$j] != 0) {
                $normalized_matrix[$i][$j] = $comparison_matrix[$i][$j] / $column_sums[$j];
            } else {
                $normalized_matrix[$i][$j] = 0; // Hindari pembagian dengan nol
            }
        }
    }

    // 3. Hitung Bobot Prioritas (Eigenvector)
    $weights = [];
    for ($i = 0; $i < $n; $i++) {
        $row_sum = array_sum($normalized_matrix[$i]);
        $weights[$i] = $row_sum / $n;
    }

    // 4. Hitung Lambda Max (λmax)
    $lambda_max_sum = 0;
    for ($i = 0; $i < $n; $i++) {
        $weighted_sum = 0;
        for ($j = 0; $j < $n; $j++) {
            $weighted_sum += $comparison_matrix[$i][$j] * $weights[$j];
        }
        // Hindari pembagian dengan nol jika bobot sangat kecil
        if ($weights[$i] != 0) {
            $lambda_max_sum += ($weighted_sum / $weights[$i]);
        } else {
            // Handle case where weight is zero, might indicate an issue with input or calculation
            return ['error' => 'Bobot kriteria nol, tidak dapat menghitung Lambda Max.'];
        }
    }
    $lambda_max = $lambda_max_sum / $n;

    // 5. Hitung Consistency Index (CI)
    $ci = ($lambda_max - $n) / ($n - 1);

    // 6. Hitung Consistency Ratio (CR)
    $ri = $random_index[$n] ?? 0; // Ambil RI berdasarkan jumlah kriteria
    $cr = ($ri != 0) ? $ci / $ri : 0; // Hindari pembagian dengan nol

    // Simpan bobot ke database (tabel kriteria)
    foreach ($kriteria as $index => $k) {
        $kriteria_id = $k['id'];
        $bobot = $weights[$index];
        $stmt = $conn->prepare("UPDATE kriteria SET bobot = ? WHERE id = ?");
        $stmt->bind_param("di", $bobot, $kriteria_id);
        $stmt->execute();
        $stmt->close();
    }

    return [
        'kriteria' => $kriteria,
        'comparison_matrix' => $comparison_matrix,
        'normalized_matrix' => $normalized_matrix,
        'weights' => $weights,
        'lambda_max' => $lambda_max,
        'ci' => $ci,
        'cr' => $cr,
        'is_consistent' => ($cr <= 0.1) // Konsisten jika CR <= 0.1
    ];
}
?>
