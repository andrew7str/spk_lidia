<?php
// admin/riwayat.php
require_once 'includes/header.php';
require_once '../config/database.php';

$riwayat_seleksi = [];
$result = $conn->query("
    SELECT DISTINCT tanggal_seleksi
    FROM hasil_seleksi
    ORDER BY tanggal_seleksi DESC
");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $riwayat_seleksi[] = $row;
    }
}

// Logika untuk menampilkan detail riwayat tertentu
$detail_riwayat = null;
if (isset($_GET['tanggal'])) {
    $tanggal_detail = $_GET['tanggal'];
    $stmt = $conn->prepare("
        SELECT hs.ranking, s.nama_supplier, hs.nilai_preferensi
        FROM hasil_seleksi hs
        JOIN supplier s ON hs.id_supplier = s.id
        WHERE hs.tanggal_seleksi = ?
        ORDER BY hs.ranking ASC
    ");
    $stmt->bind_param("s", $tanggal_detail);
    $stmt->execute();
    $result_detail = $stmt->get_result();
    if ($result_detail->num_rows > 0) {
        $detail_riwayat = [];
        while ($row = $result_detail->fetch_assoc()) {
            $detail_riwayat[] = $row;
        }
    }
    $stmt->close();
}
?>

<h2>Riwayat Seleksi Supplier</h2>

<div class="table-section">
    <h3>Daftar Tanggal Seleksi</h3>
    <?php if (empty($riwayat_seleksi)): ?>
        <p>Belum ada riwayat seleksi yang tersimpan.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Tanggal Seleksi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($riwayat_seleksi as $riwayat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($riwayat['tanggal_seleksi']); ?></td>
                        <td>
                            <a href="riwayat.php?tanggal=<?php echo $riwayat['tanggal_seleksi']; ?>" class="btn btn-info btn-sm">Lihat Detail</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php if ($detail_riwayat): ?>
    <div class="table-section mt-20">
        <h3>Detail Hasil Seleksi Tanggal: <?php echo htmlspecialchars($tanggal_detail); ?></h3>
        <table>
            <thead>
                <tr>
                    <th>Peringkat</th>
                    <th>Nama Supplier</th>
                    <th>Nilai Preferensi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detail_riwayat as $detail): ?>
                    <tr>
                        <td><?php echo $detail['ranking']; ?></td>
                        <td><?php echo htmlspecialchars($detail['nama_supplier']); ?></td>
                        <td><?php echo number_format($detail['nilai_preferensi'], 4); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
require_once 'includes/footer.php';
$conn->close();
?>
