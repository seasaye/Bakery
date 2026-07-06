<?php
session_start();

if (!isset($_SESSION['is_logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/config.php';
// Ambil data produk
/** @var mysqli $koneksi */
$produk = [];
$result = mysqli_query($koneksi, "SELECT * FROM produk");
while ($row = mysqli_fetch_assoc($result)) {
    $produk[] = $row;
}

// Ambil data transaksi (header saja: id_transaksi, tanggal, total)
$transaksi = [];
$result2 = mysqli_query($koneksi, "SELECT * FROM transaksi ORDER BY id_transaksi ASC");
while ($row = mysqli_fetch_assoc($result2)) {
    $transaksi[] = $row;
}

$total_produk = count($produk);
$total_transaksi = count($transaksi);
$total_stok = array_sum(array_column($produk, 'stok'));

// === FITUR BARU ===
// Total Pendapatan
$total_pendapatan = array_sum(array_column($transaksi, 'total'));

// Transaksi Hari Ini
$hari_ini = date('Y-m-d');
$transaksi_hari_ini = array_filter($transaksi, function($t) use ($hari_ini) {
    return $t['tanggal'] == $hari_ini;
});
$jumlah_transaksi_hari_ini = count($transaksi_hari_ini);
$pendapatan_hari_ini = array_sum(array_column($transaksi_hari_ini, 'total'));

// Stok Menipis (<=3)
$stok_menipis = array_filter($produk, function($p) {
    return $p['stok'] <= 3;
});

// Transaksi Terbaru (5 terakhir)
$transaksi_terbaru = array_slice($transaksi, -5);
?>

<?php
$judul = "Dashboard";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Bakery Admin</title>
    <link rel="stylesheet" href="../assets/lib/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php include '../components/sidebar.php'; ?>

<div class="main-content">

    <?php include '../components/header.php'; ?>

        <!-- CONTENT -->
        <div class="container-fluid mt-4">
            <h4 class="mb-4">Selamat Datang, <?= htmlspecialchars($_SESSION['nama']) ?>!</h4>
            
            <!-- STATS CARDS (4 card = 3+1 kolom kosong) -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="icon green"><i class="fas fa-money-bill-wave"></i></div>
                        <div>
                            <h4>Rp <?= number_format($total_pendapatan) ?></h4>
                            <p>Total Pendapatan</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="icon blue"><i class="fas fa-calendar-check"></i></div>
                            <div>
                                <h3><?= $jumlah_transaksi_hari_ini ?></h3>
                                <p>Transaksi Hari Ini</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <div class="icon blue"><i class="fas fa-box"></i></div>
                            <div>
                                <h3><?= $total_produk ?></h3>
                                <p>Total Produk</p>
                            </div>
                        </div>
                    </div>
                    <!-- Kolom kosong agar sejajar -->
                    <div class="col-md-4"></div>
                </div>

            <!-- ROW 2: STOK MENIPIS & TRANSAKSI TERBARU -->
<div class="row mb-4">
    <!-- Stok Menipis -->
    <div class="col-md-4">
        <div class="table-container" style="min-height: 250px;">
            <div class="table-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Stok Menipis</h3>
            </div>
            <div style="padding: 15px;">
                <?php if(count($stok_menipis) > 0): ?>
                <?php foreach($stok_menipis as $p): ?>
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee;">
                    <span><?= htmlspecialchars($p['nama_produk']) ?></span>
                    <span style="color: red; font-weight: bold;"><?= $p['stok'] ?></span>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <h2 style="color: green; text-align:center"><i class="fas fa-check"></i> Stok aman</h2>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pendapatan Hari Ini -->
    <div class="col-md-4">
        <div class="table-container" style="min-height: 250px;">
            <div class="table-header">
                <h3><i class="fas fa-calendar-day"></i> Hari Ini</h3>
            </div>
            <div style="padding: 20px; text-align: center;">
                <h2 style="color: green;">Rp <?= number_format($pendapatan_hari_ini) ?></h2>
                <p><?= $jumlah_transaksi_hari_ini ?> Transaksi</p>
            </div>
        </div>
    </div>

    <!-- Transaksi Terbaru -->
    <div class="col-md-4">
        <div class="table-container" style="min-height: 250px;">
            <div class="table-header">
                <h3><i class="fas fa-clock"></i> Transaksi Terbaru</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($transaksi_terbaru) > 0): ?>
                    <?php foreach($transaksi_terbaru as $t): ?>
                    <tr>
                        <td><?= $t['id_transaksi'] ?? '-' ?></td>
                        <td>Rp <?= number_format($t['total'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr><td colspan="2" class="text-center">Belum ada</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

            <!-- CHART -->
            <div class="chart-container">
                <h3><i class="fas fa-chart-bar"></i> Grafik Penjualan Bulan Ini</h3>
                <canvas id="grafikPenjualan" style="max-height: 300px;"></canvas>
            </div>
        </div>
        <?php include '../components/footer.php'; ?>
    </div>

    <!-- CHART.JS -->
    <script src="../assets/lib/js/chart.umd.min.js"></script>
    
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // Jam Digital
        function updateJam() {
            var now = new Date();
            var jam = String(now.getHours()).padStart(2, '0');
            var menit = String(now.getMinutes()).padStart(2, '0');
            var detik = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('jam').innerText = jam + ':' + menit + ':' + detik;
        }
        setInterval(updateJam, 1000);
        updateJam();

        // Chart
        const ctx = document.getElementById('grafikPenjualan').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4'],
                datasets: [{
                    label: 'Penjualan (Ribu Rupiah)',
                    data: [150, 230, 180, 300],
                    backgroundColor: 'rgba(147, 107, 61, 0.6)',
                    borderColor: 'rgb(142, 100, 47)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    </script>
</body>
</html>