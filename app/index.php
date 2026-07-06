<?php
/** @var mysqli $koneksi */
require_once '../../config/config.php';

// === SEARCH & FILTER PERIODE ===
$cari = $_GET['cari'] ?? '';

// Bangun query transaksi
if ($cari !== '') {
    // Cari transaksi yang mengandung produk dengan nama yang cocok di detail_transaksi
    $cari_escaped = mysqli_real_escape_string($koneksi, $cari);
    $sql = "SELECT DISTINCT t.* FROM transaksi t
            JOIN detail_transaksi d ON t.id_transaksi = d.id_transaksi
            WHERE d.nama_produk LIKE '%$cari_escaped%'";
    $sql .= " ORDER BY t.id_transaksi DESC";
} else {
    $sql = "SELECT * FROM transaksi WHERE 1=1";
    $sql .= " ORDER BY id_transaksi DESC";
}

// Ambil semua transaksi (header)
$data = [];
$result = mysqli_query($koneksi, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

// Ambil semua detail transaksi, dikelompokkan per id_transaksi
$detail_per_transaksi = [];
$result2 = mysqli_query($koneksi, "SELECT * FROM detail_transaksi ORDER BY id_detail ASC");
while ($row = mysqli_fetch_assoc($result2)) {
    $detail_per_transaksi[$row['id_transaksi']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Transaksi - Bakery Admin</title>
    <link rel="stylesheet" href="../../assets/lib/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="../../assets/img/logo.png" alt="logo" width="40">
            <span>Bakery</span>
        </div>
        <div class="sidebar-menu">
            <a href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="../kasir/index.php"><i class="fas fa-cash-register"></i> Kasir</a>
            <a href="../produk/tambah.php"><i class="fas fa-plus-circle"></i> Tambah Produk</a>
            <a href="../produk/index.php"><i class="fas fa-box"></i> Data Produk</a>
            <a href="index.php" class="active"><i class="fas fa-history"></i> Data Transaksi</a>
            <a href="../logout.php" class="logout"> <i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
        <div class="main-content">
        <nav class="navbar">
            <div class="menu-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </div>
            <span class="navbar-brand">Transaksi</span>
            <div class="user-info">
                <span class="jam-digital" id="jam"></span>
                <span><?= date('d M Y') ?></span>
                <img src="../../assets/img/user2.jpg" class="rounded-circle" alt="Admin" width="35">
            </div>
        </nav>

        <div class="container-fluid mt-4">
            <div class="table-container">
                <div class="table-header">
                    <h3><i class="fas fa-history"></i> Data Transaksi</h3>
                </div>

                <!-- FORM CARI & FILTER TANGGAL -->
                <form method="GET" class="form-cari">
                            <div class="form-cari" style="padding: 10px;">
                                <input type="text" name="cari" placeholder="Cari produk..." value="<?= htmlspecialchars($cari) ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Cari
                                </button>
                            </div>
                        </form>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Invoice</th>
                                <th>Tanggal</th>
                                <th>Jumlah Item</th>
                                <th>Total Harga</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($data) > 0): ?>
                            <?php $no = 1; foreach($data as $t): ?>
                            <?php $items = $detail_per_transaksi[$t['id_transaksi']] ?? []; ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>TRX-<?= $t['id_transaksi'] ?></td>
                                <td><?= $t['tanggal'] ?></td>
                                <td><?= count($items) ?> produk</td>
                                <td>Rp <?= number_format($t['total'], 0, ',', '.') ?></td>
                                <td>
                                    <button type="button" class="btn btn-primary" onclick="lihatDetail(<?= $t['id_transaksi'] ?>)">
                                        <i class="fas fa-eye"></i> Lihat
                                    </button>
                                    <a href="edit.php?id=<?= $t['id_transaksi'] ?>" class="btn btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="hapus.php?id=<?= $t['id_transaksi'] ?>" class="btn btn-hapus" onclick="return confirm('Yakin hapus transaksi ini? Stok akan dikembalikan.')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DETAIL ITEM -->
    <div id="modalDetail" style="display:none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;">
        <div style="background: white; width: 400px; max-height: 80vh; overflow-y: auto; border-radius: 8px;">
            <div style="background: #6c5ce7; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; border-radius: 8px 8px 0 0;">
                <strong id="modalTitle">Detail Transaksi</strong>
                <span style="cursor: pointer; font-size: 20px;" onclick="tutupModal()">&times;</span>
            </div>
            <div style="padding: 15px;">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Qty</th>
                            <th>Harga</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody id="modalBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Data detail transaksi per id, disiapkan dari PHP untuk dipakai modal
        const semuaDetail = <?= json_encode($detail_per_transaksi) ?>;

        function lihatDetail(idTransaksi) {
            const items = semuaDetail[idTransaksi] || [];
            const tbody = document.getElementById('modalBody');
            document.getElementById('modalTitle').innerText = 'Detail Transaksi TRX-' + idTransaksi;

            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Tidak ada item</td></tr>';
            } else {
                let html = '';
                items.forEach(item => {
                    html += '<tr>';
                    html += '<td>' + item.nama_produk + '</td>';
                    html += '<td>' + item.jumlah + '</td>';
                    html += '<td>Rp ' + Number(item.harga).toLocaleString('id-ID') + '</td>';
                    html += '<td>Rp ' + Number(item.subtotal).toLocaleString('id-ID') + '</td>';
                    html += '</tr>';
                });
                tbody.innerHTML = html;
            }

            document.getElementById('modalDetail').style.display = 'flex';
        }

        function tutupModal() {
            document.getElementById('modalDetail').style.display = 'none';
        }

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
    </script>
</body>
</html>