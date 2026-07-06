<?php
/** @var mysqli $koneksi */
require_once '../../config/config.php';

$id_transaksi = (int) ($_GET['id'] ?? $_POST['id_transaksi'] ?? 0);

// === PROSES: HAPUS SATU ITEM DARI TRANSAKSI ===
if (isset($_GET['hapus_item'])) {
    $id_detail = (int) $_GET['hapus_item'];

    // Ambil data item yang mau dihapus (untuk kembalikan stok)
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM detail_transaksi WHERE id_detail = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_detail);
    mysqli_stmt_execute($stmt);
    $item = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($item) {
        // Kembalikan stok produk
        $stmt2 = mysqli_prepare($koneksi, "UPDATE produk SET stok = stok + ? WHERE id_produk = ?");
        mysqli_stmt_bind_param($stmt2, "ii", $item['jumlah'], $item['id_produk']);
        mysqli_stmt_execute($stmt2);

        // Hapus item
        $stmt3 = mysqli_prepare($koneksi, "DELETE FROM detail_transaksi WHERE id_detail = ?");
        mysqli_stmt_bind_param($stmt3, "i", $id_detail);
        mysqli_stmt_execute($stmt3);

        // Hitung ulang total transaksi
        hitungUlangTotal($koneksi, $id_transaksi);
    }

    header('Location: edit.php?id=' . $id_transaksi);
    exit;
}

// === PROSES: SIMPAN PERUBAHAN JUMLAH ITEM ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['jumlah'])) {
    foreach ($_POST['jumlah'] as $id_detail => $jumlah_baru) {
        $id_detail = (int) $id_detail;
        $jumlah_baru = (int) $jumlah_baru;

        // Ambil data item saat ini
        $stmt = mysqli_prepare($koneksi, "SELECT * FROM detail_transaksi WHERE id_detail = ?");
        mysqli_stmt_bind_param($stmt, "i", $id_detail);
        mysqli_stmt_execute($stmt);
        $item = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($item && $jumlah_baru != $item['jumlah']) {
            $selisih = $jumlah_baru - $item['jumlah']; // positif = nambah, negatif = kurang
            $subtotal_baru = $jumlah_baru * $item['harga'];

            // Update item
            $stmt2 = mysqli_prepare($koneksi, "UPDATE detail_transaksi SET jumlah = ?, subtotal = ? WHERE id_detail = ?");
            mysqli_stmt_bind_param($stmt2, "iii", $jumlah_baru, $subtotal_baru, $id_detail);
            mysqli_stmt_execute($stmt2);

            // Sesuaikan stok produk (kalau jumlah nambah, stok berkurang; kalau jumlah berkurang, stok bertambah)
            $stmt3 = mysqli_prepare($koneksi, "UPDATE produk SET stok = stok - ? WHERE id_produk = ?");
            mysqli_stmt_bind_param($stmt3, "ii", $selisih, $item['id_produk']);
            mysqli_stmt_execute($stmt3);
        }
    }

    // Hitung ulang total transaksi
    hitungUlangTotal($koneksi, $id_transaksi);

    header('Location: index.php');
    exit;
}

// Fungsi bantu: hitung ulang total transaksi dari semua detail_transaksi-nya
function hitungUlangTotal($koneksi, $id_transaksi) {
    $stmt = mysqli_prepare($koneksi, "SELECT SUM(subtotal) AS total_baru FROM detail_transaksi WHERE id_transaksi = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_transaksi);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $total_baru = $row['total_baru'] ?? 0;

    $stmt2 = mysqli_prepare($koneksi, "UPDATE transaksi SET total = ? WHERE id_transaksi = ?");
    mysqli_stmt_bind_param($stmt2, "ii", $total_baru, $id_transaksi);
    mysqli_stmt_execute($stmt2);
}

// === TAMPILKAN FORM ===
$stmt = mysqli_prepare($koneksi, "SELECT * FROM transaksi WHERE id_transaksi = ?");
mysqli_stmt_bind_param($stmt, "i", $id_transaksi);
mysqli_stmt_execute($stmt);
$transaksi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$transaksi) {
    die("Transaksi tidak ditemukan!");
}

$items = [];
$stmt2 = mysqli_prepare($koneksi, "SELECT * FROM detail_transaksi WHERE id_transaksi = ?");
mysqli_stmt_bind_param($stmt2, "i", $id_transaksi);
mysqli_stmt_execute($stmt2);
$result2 = mysqli_stmt_get_result($stmt2);
while ($row = mysqli_fetch_assoc($result2)) {
    $items[] = $row;
}
$judul = "Data Transaksi";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Transaksi - Bakery Admin</title>
    <link rel="stylesheet" href="../../assets/lib/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../../components/sidebar.php'; ?>

    <div class="main-content">
        <?php include '../../components/header.php'; ?>

        <div class="container-fluid mt-4">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-edit"></i> Edit Item Transaksi</h3>
                    <p class="text-muted mb-0">Tanggal: <?= $transaksi['tanggal'] ?></p>
                </div>
                <div class="card-body">
                    <?php if (empty($items)): ?>
                        <p class="text-center text-muted">Tidak ada item dalam transaksi ini.</p>
                    <?php else: ?>
                    <form method="POST" action="edit.php?id=<?= $id_transaksi ?>">
                        <input type="hidden" name="id_transaksi" value="<?= $id_transaksi ?>">
                        <table style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Harga</th>
                                    <th>Jumlah</th>
                                    <th>Subtotal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['nama_produk']) ?></td>
                                    <td>Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                                    <td>
                                        <input type="number" name="jumlah[<?= $item['id_detail'] ?>]" value="<?= $item['jumlah'] ?>" min="1" class="form-control" style="width: 90px;">
                                    </td>
                                    <td>Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                                    <td>
                                        <a href="edit.php?id=<?= $id_transaksi ?>&hapus_item=<?= $item['id_detail'] ?>" 
                                            class="btn btn-hapus btn-sm" 
                                            onclick="return confirm('Hapus item ini dari transaksi? Stok akan dikembalikan.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>Total saat ini: Rp <?= number_format($transaksi['total'], 0, ',', '.') ?></strong>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Simpan Perubahan Jumlah
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </div>
                        <p class="text-muted mt-2" style="font-size: 13px;">
                            * Mengubah jumlah otomatis menyesuaikan stok dan total transaksi.
                        </p>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php include '../../components/footer.php'; ?>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>