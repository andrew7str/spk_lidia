<?php
// admin/dashboard.php
require_once 'includes/header.php';
require_once '../config/database.php';

// Ambil statistik untuk dashboard
$stats = [];

// 1. Total Kriteria
$result_kriteria = $conn->query("SELECT COUNT(*) as total FROM kriteria");
$stats['total_kriteria'] = $result_kriteria ? $result_kriteria->fetch_assoc()['total'] : 0;

// 2. Total Supplier
$result_supplier = $conn->query("SELECT COUNT(*) as total FROM supplier");
$stats['total_supplier'] = $result_supplier ? $result_supplier->fetch_assoc()['total'] : 0;

// 3. Seleksi Terakhir (tanggal terakhir perhitungan TOPSIS)
$result_seleksi_terakhir = $conn->query("SELECT MAX(tanggal_seleksi) as tanggal_terakhir FROM hasil_seleksi");
$seleksi_terakhir = $result_seleksi_terakhir ? $result_seleksi_terakhir->fetch_assoc()['tanggal_terakhir'] : null;

// 4. Peringkat Supplier Terakhir (untuk diagram)
$peringkat_supplier = [];
if ($seleksi_terakhir) {
    $result_peringkat = $conn->query("
        SELECT hs.ranking, s.nama_supplier, hs.nilai_preferensi 
        FROM hasil_seleksi hs 
        JOIN supplier s ON hs.id_supplier = s.id 
        WHERE hs.tanggal_seleksi = '$seleksi_terakhir' 
        ORDER BY hs.ranking ASC
    ");
    
    if ($result_peringkat && $result_peringkat->num_rows > 0) {
        while ($row = $result_peringkat->fetch_assoc()) {
            $peringkat_supplier[] = $row;
        }
    }
}
?>

<h2>Dashboard - SPK Lidia Fashion</h2>

<div class="dashboard-stats">
    <div class="stat-card">
        <h3>Total Kriteria</h3>
        <div class="stat-number"><?php echo $stats['total_kriteria']; ?></div>
        <p>Kriteria penilaian tersedia</p>
    </div>
    
    <div class="stat-card">
        <h3>Total Supplier</h3>
        <div class="stat-number"><?php echo $stats['total_supplier']; ?></div>
        <p>Supplier terdaftar</p>
    </div>
    
    <div class="stat-card">
        <h3>Seleksi Terakhir</h3>
        <div class="stat-number">
            <?php echo $seleksi_terakhir ? date('d/m/Y', strtotime($seleksi_terakhir)) : '-'; ?>
        </div>
        <p>Tanggal perhitungan terakhir</p>
    </div>
    
    <div class="stat-card">
        <h3>Supplier Terbaik</h3>
        <div class="stat-number">
            <?php 
            if (!empty($peringkat_supplier)) {
                echo htmlspecialchars($peringkat_supplier[0]['nama_supplier']);
            } else {
                echo '-';
            }
            ?>
        </div>
        <p>Peringkat #1 terakhir</p>
    </div>
</div>

<?php if (!empty($peringkat_supplier)): ?>
<div class="dashboard-content">
    <div class="content-section">
        <h3>Peringkat Supplier Terakhir</h3>
        <div class="chart-container">
            <canvas id="supplierChart" width="400" height="200"></canvas>
        </div>
        
        <div class="ranking-table">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Ranking</th>
                        <th>Nama Supplier</th>
                        <th>Nilai Preferensi</th>
                        <th>Persentase</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($peringkat_supplier as $supplier): ?>
                    <tr>
                        <td>
                            <span class="ranking-badge ranking-<?php echo $supplier['ranking']; ?>">
                                #<?php echo $supplier['ranking']; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($supplier['nama_supplier']); ?></td>
                        <td><?php echo number_format($supplier['nilai_preferensi'], 4); ?></td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo ($supplier['nilai_preferensi'] * 100); ?>%"></div>
                                <span class="progress-text"><?php echo number_format($supplier['nilai_preferensi'] * 100, 1); ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Data untuk chart
const supplierData = {
    labels: [
        <?php foreach ($peringkat_supplier as $supplier): ?>
        '<?php echo addslashes($supplier['nama_supplier']); ?>',
        <?php endforeach; ?>
    ],
    datasets: [{
        label: 'Nilai Preferensi',
        data: [
            <?php foreach ($peringkat_supplier as $supplier): ?>
            <?php echo $supplier['nilai_preferensi']; ?>,
            <?php endforeach; ?>
        ],
        backgroundColor: [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
            '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
        ],
        borderColor: [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
            '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
        ],
        borderWidth: 2
    }]
};

// Konfigurasi chart
const config = {
    type: 'bar',
    data: supplierData,
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Peringkat Supplier Berdasarkan Nilai Preferensi TOPSIS'
            },
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 1,
                title: {
                    display: true,
                    text: 'Nilai Preferensi'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Supplier'
                }
            }
        }
    }
};

// Render chart
const ctx = document.getElementById('supplierChart').getContext('2d');
const supplierChart = new Chart(ctx, config);
</script>

<?php else: ?>
<div class="dashboard-content">
    <div class="content-section">
        <h3>Peringkat Supplier</h3>
        <div class="no-data">
            <p>Belum ada hasil perhitungan TOPSIS.</p>
            <p>Silakan lakukan perhitungan di halaman <a href="perhitungan_topsis.php">Perhitungan TOPSIS</a>.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-card h3 {
    margin: 0 0 15px 0;
    font-size: 16px;
    opacity: 0.9;
}

.stat-number {
    font-size: 32px;
    font-weight: bold;
    margin: 15px 0;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.stat-card p {
    margin: 0;
    opacity: 0.8;
    font-size: 14px;
}

.dashboard-content {
    margin-top: 30px;
}

.content-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.content-section h3 {
    margin-top: 0;
    color: #333;
    border-bottom: 3px solid #667eea;
    padding-bottom: 10px;
    margin-bottom: 25px;
}

.chart-container {
    margin-bottom: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.ranking-table .table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 0;
}

.ranking-table .table th,
.ranking-table .table td {
    padding: 15px;
    text-align: left;
    border: 1px solid #dee2e6;
}

.ranking-table .table th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: bold;
}

.ranking-table .table tbody tr:nth-child(even) {
    background-color: #f8f9fa;
}

.ranking-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    color: white;
    font-weight: bold;
    font-size: 14px;
}

.ranking-1 { background: #FFD700; color: #333; }
.ranking-2 { background: #C0C0C0; color: #333; }
.ranking-3 { background: #CD7F32; color: white; }
.ranking-badge:not(.ranking-1):not(.ranking-2):not(.ranking-3) { 
    background: #6c757d; 
}

.progress-bar {
    position: relative;
    width: 100%;
    height: 25px;
    background-color: #e9ecef;
    border-radius: 12px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    transition: width 0.3s ease;
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-weight: bold;
    font-size: 12px;
    color: #333;
}

.no-data {
    text-align: center;
    padding: 40px;
    color: #6c757d;
}

.no-data p {
    margin: 10px 0;
}

.no-data a {
    color: #667eea;
    text-decoration: none;
    font-weight: bold;
}

.no-data a:hover {
    text-decoration: underline;
}
</style>

<?php
require_once 'includes/footer.php';
$conn->close();
?>
