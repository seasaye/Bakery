<?php
/** @var mysqli $koneksi */
require_once '../../config/config.php';

$cari = $_GET['cari'] ?? '';
$kategori = $_GET['kategori'] ?? 'semua';

$kategori_list = ['semua'];
$res_kat = mysqli_query($koneksi, "SELECT nama_kategori FROM kategori ORDER BY nama_kategori");
while ($row = mysqli_fetch_assoc($res_kat)) {
    $kategori_list[] = $row['nama_kategori'];
}

$sql = "SELECT p.id_produk, p.kode_produk, p.nama_produk, p.harga, p.stok, k.nama_kategori
        FROM produk p
        LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
        WHERE 1=1";

if ($cari !== '') {
    $cari_escaped = mysqli_real_escape_string($koneksi, $cari);
    $sql .= " AND (p.nama_produk LIKE '%$cari_escaped%' OR p.kode_produk LIKE '%$cari_escaped%')";
}
if ($kategori !== 'semua') {
    $kategori_escaped = mysqli_real_escape_string($koneksi, $kategori);
    $sql .= " AND k.nama_kategori = '$kategori_escaped'";
}
$sql .= " ORDER BY p.id_produk DESC";

$result = mysqli_query($koneksi, $sql);
$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}
$judul = "Data Produk";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Produk - Bakery Admin</title>
    <link rel="stylesheet" href="../../assets/lib/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php include '../../components/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <?php include '../../components/header.php'; ?>

        <div class="container-fluid mt-4">
            <div class="table-container">
                <div class="table-header">
                    <h3><i class="fas fa-box"></i> Data Produk</h3>
                    <a href="tambah.php" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah</a>
                </div>

                <!-- FORM CARI -->
                <form method="GET" class="form-cari">
                            <div class="form-cari" style="padding: 10px;">
                                <input type="text" name="cari" placeholder="Cari produk..." value="<?= htmlspecialchars($cari) ?>">
                                <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori) ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Cari
                                </button>
                                <?php if($cari): ?>
                                <a href="?kategori=<?= htmlspecialchars($kategori) ?>" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Reset
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>

                <!-- KATEGORI BUTTONS -->
                <div class="kategori-filter">
                    <?php foreach ($kategori_list as $kat): ?>
                    <a href="?kategori=<?= htmlspecialchars($kat) ?>&cari=<?= htmlspecialchars($cari) ?>"
                        class="kategori-btn <?= $kategori == $kat ? 'active' : '' ?>">
                        <?= ucfirst($kat) ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($data)): ?>
                            <?php $no = 1; foreach ($data as $p): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($p['kode_produk']) ?></td>
                                <td><?= htmlspecialchars($p['nama_produk']) ?></td>
                                <td><span class="badge"><?= htmlspecialchars($p['nama_kategori'] ?? '-') ?></span></td>
                                <td>Rp <?= number_format($p['harga']) ?></td>
                                <td>
                                    <span class="<?= ($p['stok'] ?? 0) <= 3 ? 'stok-sedikit' : '' ?>">
                                        <?= $p['stok'] ?? 0 ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit.php?id=<?= $p['id_produk'] ?>" class="btn btn-edit"><i class="fas fa-edit"></i></a>
                                    <a href="hapus.php?id=<?= $p['id_produk'] ?>" class="btn btn-hapus" onclick="return confirm('Yakin hapus?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr><td colspan="7" class="text-center text-muted">Produk tidak ditemukan</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php include '../../components/footer.php'; ?>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

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