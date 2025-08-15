<?php
// admin/kriteria.php

require_once 'includes/header.php';

require_once '../config/database.php'; // Koneksi database

$message = '';

$message_type = '';

// --- Logika Tambah/Edit Kriteria ---

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

$nama_kriteria = $_POST['nama_kriteria'];
$tipe_kriteria = $_POST['tipe_kriteria'];
$id_kriteria = $_POST['id_kriteria'] ?? null;
$bobot = isset($_POST['bobot']) ? (float)$_POST['bobot'] : 0.0; // Ambil bobot dari form
if ($id_kriteria) {
    // Update Kriteria
    $stmt = $conn->prepare("UPDATE kriteria SET nama_kriteria = ?, tipe_kriteria = ?, bobot = ? WHERE id = ?");
    $stmt->bind_param("ssdi", $nama_kriteria, $tipe_kriteria, $bobot, $id_kriteria);
    if ($stmt->execute()) {
        $message = "Kriteria berhasil diperbarui!";
        $message_type = "success";
    } else {
        $message = "Gagal memperbarui kriteria: " . $stmt->error;
        $message_type = "danger";
    }
    $stmt->close();
} else {
    // Tambah Kriteria Baru
    $stmt = $conn->prepare("INSERT INTO kriteria (nama_kriteria, tipe_kriteria, bobot) VALUES (?, ?, ?)");
    $stmt->bind_param("ssd", $nama_kriteria, $tipe_kriteria, $bobot);
    if ($stmt->execute()) {
        $message = "Kriteria berhasil ditambahkan!";
        $message_type = "success";
    } else {
        $message = "Gagal menambahkan kriteria: " . $stmt->error;
        $message_type = "danger";
    }
    $stmt->close();
}
}

// --- Logika Hapus Kriteria ---

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {

$id_kriteria = $_GET['id'];
$stmt = $conn->prepare("DELETE FROM kriteria WHERE id = ?");
$stmt->bind_param("i", $id_kriteria);
if ($stmt->execute()) {
    $message = "Kriteria berhasil dihapus!";
    $message_type = "success";
} else {
    $message = "Gagal menghapus kriteria: " . $stmt->error;
    $message_type = "danger";
}
$stmt->close();
}

// --- Ambil Data Kriteria untuk Tampilan ---

$kriteria_data = [];

$result = $conn->query("SELECT id, nama_kriteria, tipe_kriteria, bobot FROM kriteria ORDER BY id ASC");

if ($result->num_rows > 0) {

while ($row = $result->fetch_assoc()) {
    $kriteria_data[] = $row;
}
}

// --- Ambil Data Kriteria untuk Form Edit (jika ada parameter edit) ---

$edit_kriteria = null;

if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {

$id_edit = $_GET['id'];
$stmt = $conn->prepare("SELECT id, nama_kriteria, tipe_kriteria, bobot FROM kriteria WHERE id = ?");
$stmt->bind_param("i", $id_edit);
$stmt->execute();
$result_edit = $stmt->get_result();
if ($result_edit->num_rows == 1) {
    $edit_kriteria = $result_edit->fetch_assoc();
}
$stmt->close();
}

?>

<h2>Manajemen Kriteria</h2>
<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
<?php endif; ?>
<div class="form-section">
<h3><?php echo $edit_kriteria ? 'Edit Kriteria' : 'Tambah Kriteria Baru'; ?></h3>
<form action="kriteria.php" method="POST">
    <?php if ($edit_kriteria): ?>
        <input type="hidden" name="id_kriteria" value="<?php echo $edit_kriteria['id']; ?>">
    <?php endif; ?>
    <div class="form-group">
        <label for="nama_kriteria">Nama Kriteria:</label>
        <input type="text" id="nama_kriteria" name="nama_kriteria" value="<?php echo $edit_kriteria ? htmlspecialchars($edit_kriteria['nama_kriteria']) : ''; ?>" required>
    </div>
    <div class="form-group">
        <label for="tipe_kriteria">Tipe Kriteria:</label>
        <select id="tipe_kriteria" name="tipe_kriteria" required>
            <option value="benefit" <?php echo ($edit_kriteria && $edit_kriteria['tipe_kriteria'] == 'benefit') ? 'selected' : ''; ?>>Benefit (Semakin Tinggi Semakin Baik)</option>
            <option value="cost" <?php echo ($edit_kriteria && $edit_kriteria['tipe_kriteria'] == 'cost') ? 'selected' : ''; ?>>Cost (Semakin Rendah Semakin Baik)</option>
        </select>
    </div>
    <!-- Tambahkan input untuk bobot -->
    <div class="form-group">
        <label for="bobot">Bobot AHP:</label>
        <input type="number" step="0.0001" min="0" max="1" id="bobot" name="bobot" value="<?php echo $edit_kriteria ? number_format($edit_kriteria['bobot'], 4) : '0.0000'; ?>" required>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?php echo $edit_kriteria ? 'Update Kriteria' : 'Tambah Kriteria'; ?></button>
        <?php if ($edit_kriteria): ?>
            <a href="kriteria.php" class="btn btn-secondary">Batal Edit</a>
        <?php endif; ?>
    </div>
</form>
</div>
<div class="table-section">
<h3>Daftar Kriteria</h3>
<?php if (empty($kriteria_data)): ?>
    <p>Belum ada kriteria yang ditambahkan.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Kriteria</th>
                <th>Tipe</th>
                <th>Bobot AHP</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($kriteria_data as $kriteria): ?>
                <tr>
                    <td><?php echo $kriteria['id']; ?></td>
                    <td><?php echo htmlspecialchars($kriteria['nama_kriteria']); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($kriteria['tipe_kriteria'])); ?></td>
                    <td><?php echo number_format($kriteria['bobot'], 4); ?></td>
                    <td>
                        <a href="kriteria.php?action=edit&id=<?php echo $kriteria['id']; ?>" class="btn btn-info btn-sm">Edit</a>
                        <a href="kriteria.php?action=delete&id=<?php echo $kriteria['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus kriteria ini? Ini akan menghapus semua nilai terkait!');">Hapus</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</div>
<?php
require_once 'includes/footer.php';

$conn->close();

?>