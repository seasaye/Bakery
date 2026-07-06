<?php
/** @var mysqli $koneksi */
require_once '../../config/config.php';

// === PROSES SIMPAN (JIKA FORM DIKIRIM) ===
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int) $_GET['id'];
    $nama = $_POST['nama'];
    $harga = (int) $_POST['harga'];
    $stok = (int) $_POST['stok'];
    $id_kategori = (int) $_POST['id_kategori'];

    $stmt = mysqli_prepare($koneksi, "UPDATE produk SET nama_produk = ?, harga = ?, stok = ?, id_kategori = ? WHERE id_produk = ?");
    mysqli_stmt_bind_param($stmt, "siiii", $nama, $harga, $stok, $id_kategori, $id);
    mysqli_stmt_execute($stmt);

    header('Location: index.php');
    exit;
}

// === TAMPILKAN FORM ===
$id = (int) $_GET['id'];

$stmt = mysqli_prepare($koneksi, "SELECT * FROM produk WHERE id_produk = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$produk = mysqli_fetch_assoc($result);

if (!$produk) {
    die("Produk tidak ditemukan!");
}

// Ambil daftar kategori untuk dropdown
$kategori_list = [];
$res_kat = mysqli_query($koneksi, "SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori");
while ($row = mysqli_fetch_assoc($res_kat)) {
    $kategori_list[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Produk - Bakery Admin</title>
    <link rel="stylesheet" href="../../assets/lib/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-bread-slice"></i>
            <span>Bakery</span>
        </div>
        <div class="sidebar-menu">
            <a href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="index.php" class="active"><i class="fas fa-box"></i> Data Produk</a>
            <a href="tambah.php"><i class="fas fa-plus-circle"></i> Tambah Produk</a>
            <a href="../kasir/index.php"><i class="fas fa-cash-register"></i> Kasir</a>
            <a href="../transaksi/index.php"><i class="fas fa-history"></i> Data Transaksi</a>
            <a href="../logout.php" class="logout"> <i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <nav class="navbar">
            <div class="menu-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </div>
            <span class="navbar-brand">Edit Produk</span>
            <div class="user-info">
                <span>Admin</span>
                <div class="user-avatar"><i class="fas fa-user"></i></div>
            </div>
        </nav>

        <div class="container-fluid mt-4">
            <div class="form-container">
                <h3><i class="fas fa-edit"></i> Edit Produk</h3>
                <form action="edit.php?id=<?= $id ?>" method="POST">
                    <div class="form-group">
                        <label>Kode Barang</label>
                        <input type="text" value="<?= htmlspecialchars($produk['kode_produk']) ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Nama Barang</label>
                        <input type="text" name="nama" value="<?= htmlspecialchars($produk['nama_produk']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="id_kategori" required>
                            <?php foreach($kategori_list as $kat): ?>
                            <option value="<?= $kat['id_kategori'] ?>" <?= $kat['id_kategori'] == $produk['id_kategori'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kat['nama_kategori']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Harga (Rp)</label>
                        <input type="number" name="harga" value="<?= $produk['harga'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Jumlah (Stok)</label>
                        <input type="number" name="stok" value="<?= $produk['stok'] ?>" required>
                    </div>
                    <div class="form-btn">
                        <button type="submit" class="btn-simpan"><i class="fas fa-save"></i> Update</button>
                        <a href="index.php" class="btn-reset" style="text-decoration: none; padding: 12px 25px; display: inline-block;">Batal</a>
                    </div>
                </form>
            </div>
        </div>
        <footer class="footer">
        © 2026 Bakery Management System | Developed by kelompok 1
        </footer>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>