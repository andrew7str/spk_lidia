<?php
// admin/supplier.php
require_once 'includes/header.php';
require_once '../config/database.php'; // Koneksi database

$message = '';
$message_type = '';

// --- Logika Tambah/Edit Supplier ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_supplier = $_POST['nama_supplier'];
    $alamat = $_POST['alamat'] ?? '';
    $telepon = $_POST['telepon'] ?? '';
    $id_supplier = $_POST['id_supplier'] ?? null;

    if ($id_supplier) {
        // Update Supplier
        $stmt = $conn->prepare("UPDATE supplier SET nama_supplier = ?, alamat = ?, telepon = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nama_supplier, $alamat, $telepon, $id_supplier);
        if ($stmt->execute()) {
            $message = "Supplier berhasil diperbarui!";
            $message_type = "success";
        } else {
            $message = "Gagal memperbarui supplier: " . $stmt->error;
            $message_type = "danger";
        }
        $stmt->close();
    } else {
        // Tambah Supplier Baru
        $stmt = $conn->prepare("INSERT INTO supplier (nama_supplier, alamat, telepon) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nama_supplier, $alamat, $telepon);
        if ($stmt->execute()) {
            $message = "Supplier berhasil ditambahkan!";
            $message_type = "success";
        } else {
            $message = "Gagal menambahkan supplier: " . $stmt->error;
            $message_type = "danger";
        }
        $stmt->close();
    }
}

// --- Logika Hapus Supplier ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_supplier = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM supplier WHERE id = ?");
    $stmt->bind_param("i", $id_supplier);
    if ($stmt->execute()) {
        $message = "Supplier berhasil dihapus!";
        $message_type = "success";
    } else {
        $message = "Gagal menghapus supplier: " . $stmt->error;
        $message_type = "danger";
    }
    $stmt->close();
}

// --- Ambil Data Supplier untuk Tampilan ---
$supplier_data = [];
$result = $conn->query("SELECT id, nama_supplier, alamat, telepon FROM supplier ORDER BY id ASC");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $supplier_data[] = $row;
    }
}

// --- Ambil Data Supplier untuk Form Edit (jika ada parameter edit) ---
$edit_supplier = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id_edit = $_GET['id'];
    $stmt = $conn->prepare("SELECT id, nama_supplier, alamat, telepon FROM supplier WHERE id = ?");
    $stmt->bind_param("i", $id_edit);
    $stmt->execute();
    $result_edit = $stmt->get_result();
    if ($result_edit->num_rows == 1) {
        $edit_supplier = $result_edit->fetch_assoc();
    }
    $stmt->close();
}
?>

<h2>Manajemen Supplier</h2>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<div class="form-section">
    <h3><?php echo $edit_supplier ? 'Edit Supplier' : 'Tambah Supplier Baru'; ?></h3>
    <form action="supplier.php" method="POST">
        <?php if ($edit_supplier): ?>
            <input type="hidden" name="id_supplier" value="<?php echo $edit_supplier['id']; ?>">
        <?php endif; ?>
        <div class="form-group">
            <label for="nama_supplier">Nama Supplier:</label>
            <input type="text" id="nama_supplier" name="nama_supplier" value="<?php echo $edit_supplier ? htmlspecialchars($edit_supplier['nama_supplier']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="alamat">Alamat:</label>
            <textarea id="alamat" name="alamat" rows="3"><?php echo $edit_supplier ? htmlspecialchars($edit_supplier['alamat']) : ''; ?></textarea>
        </div>
        <div class="form-group">
            <label for="telepon">Telepon:</label>
            <input type="text" id="telepon" name="telepon" value="<?php echo $edit_supplier ? htmlspecialchars($edit_supplier['telepon']) : ''; ?>">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $edit_supplier ? 'Update Supplier' : 'Tambah Supplier'; ?></button>
            <?php if ($edit_supplier): ?>
                <a href="supplier.php" class="btn btn-secondary">Batal Edit</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="table-section">
    <h3>Daftar Supplier</h3>
    <?php if (empty($supplier_data)): ?>
        <p>Belum ada supplier yang ditambahkan.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Supplier</th>
                    <th>Alamat</th>
                    <th>Telepon</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($supplier_data as $supplier): ?>
                    <tr>
                        <td><?php echo $supplier['id']; ?></td>
                        <td><?php echo htmlspecialchars($supplier['nama_supplier']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['alamat']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['telepon']); ?></td>
                        <td>
                            <a href="supplier.php?action=edit&id=<?php echo $supplier['id']; ?>" class="btn btn-info btn-sm">Edit</a>
                            <a href="supplier.php?action=delete&id=<?php echo $supplier['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus supplier ini? Ini akan menghapus semua nilai terkait!');">Hapus</a>
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
