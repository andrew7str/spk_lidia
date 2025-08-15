<?php
// admin/input_nilai.php
require_once 'includes/header.php';
require_once '../config/database.php'; // Koneksi database

$message = '';
$message_type = '';

// --- Logika Simpan/Update Nilai ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_nilai'])) {
    $nilai_input = $_POST['nilai']; // Array asosiatif: [id_supplier][id_kriteria] => nilai

    foreach ($nilai_input as $id_supplier => $kriteria_values) {
        foreach ($kriteria_values as $id_kriteria => $nilai) {
            // Cek apakah nilai sudah ada
            $stmt_check = $conn->prepare("SELECT id FROM nilai_supplier WHERE id_supplier = ? AND id_kriteria = ?");
            $stmt_check->bind_param("ii", $id_supplier, $id_kriteria);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                // Update nilai
                $stmt_update = $conn->prepare("UPDATE nilai_supplier SET nilai = ? WHERE id_supplier = ? AND id_kriteria = ?");
                $stmt_update->bind_param("dii", $nilai, $id_supplier, $id_kriteria);
                $stmt_update->execute();
                $stmt_update->close();
            } else {
                // Tambah nilai baru
                $stmt_insert = $conn->prepare("INSERT INTO nilai_supplier (id_supplier, id_kriteria, nilai) VALUES (?, ?, ?)");
                $stmt_insert->bind_param("iid", $id_supplier, $id_kriteria, $nilai);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
            $stmt_check->close();
        }
    }
    $message = "Nilai supplier berhasil disimpan/diperbarui!";
    $message_type = "success";
}

// --- Ambil Data Kriteria dan Supplier ---
$kriteria_data = [];
$result_kriteria = $conn->query("SELECT id, nama_kriteria FROM kriteria ORDER BY id ASC");
if ($result_kriteria->num_rows > 0) {
    while ($row = $result_kriteria->fetch_assoc()) {
        $kriteria_data[] = $row;
    }
}

$supplier_data = [];
$result_supplier = $conn->query("SELECT id, nama_supplier FROM supplier ORDER BY id ASC");
if ($result_supplier->num_rows > 0) {
    while ($row = $result_supplier->fetch_assoc()) {
        $supplier_data[] = $row;
    }
}

// --- Ambil Nilai yang Sudah Ada ---
$existing_nilai = [];
$result_nilai = $conn->query("SELECT id_supplier, id_kriteria, nilai FROM nilai_supplier");
if ($result_nilai->num_rows > 0) {
    while ($row = $result_nilai->fetch_assoc()) {
        $existing_nilai[$row['id_supplier']][$row['id_kriteria']] = $row['nilai'];
    }
}
?>

<h2>Input Nilai Supplier</h2>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<?php if (empty($kriteria_data) || empty($supplier_data)): ?>
    <p>Silakan tambahkan setidaknya satu kriteria dan satu supplier terlebih dahulu di halaman <a href="kriteria.php">Kriteria</a> dan <a href="supplier.php">Supplier</a>.</p>
<?php else: ?>
    <div class="form-section">
        <h3>Masukkan Nilai untuk Setiap Supplier dan Kriteria (Skala 1-9)</h3>
        <form action="input_nilai.php" method="POST">
            <table>
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <?php foreach ($kriteria_data as $kriteria): ?>
                            <th><?php echo htmlspecialchars($kriteria['nama_kriteria']); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($supplier_data as $supplier): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($supplier['nama_supplier']); ?></td>
                            <?php foreach ($kriteria_data as $kriteria): ?>
                                <td>
                                    <input type="number" name="nilai[<?php echo $supplier['id']; ?>][<?php echo $kriteria['id']; ?>]"
                                           min="1" max="9" step="0.01"
                                           value="<?php echo $existing_nilai[$supplier['id']][$kriteria['id']] ?? ''; ?>"
                                           required>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="form-actions">
                <button type="submit" name="submit_nilai" class="btn btn-primary">Simpan Nilai</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php
require_once 'includes/footer.php';
$conn->close();
?>
