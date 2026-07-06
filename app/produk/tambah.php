<?php
/** @var mysqli $koneksi */
require_once '../../config/config.php';

// Ambil daftar kategori dari database untuk dropdown
$kategori_list = [];
$res_kat = mysqli_query($koneksi, "SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori");
while ($row = mysqli_fetch_assoc($res_kat)) {
    $kategori_list[] = $row;
}
$judul = "Tambah Produk";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Produk - Bakery Admin</title>
    <link rel="stylesheet" href="../../assets/lib/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <?php include '../../components/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <?php include '../../components/header.php'; ?>

        <!-- DROPDOWN MENU -->
        <div class="dropdown-menu" id="dropdownMenu">
            <a href="index.php"><i class="fas fa-box"></i> Data Produk</a>
            <a href="../kasir/index.php"><i class="fas fa-cash-register"></i> Kasir</a>
            <a href="../transaksi/index.php"><i class="fas fa-history"></i> Data Transaksi</a>
        </div>

        <div class="container-fluid mt-4">
            <div class="form-container">
                <h3><i class="fas fa-plus-circle"></i> Tambah Produk Baru</h3>
                <form action="simpan.php" method="POST">
                    <!-- KODE PRODUK -->
                    <div class="form-group">
                        <label>Kode Produk</label>
                        <input type="text" name="kode" placeholder="Masukan Kode Produk" required>
                    </div>
                    
                    <!-- NAMA PRODUK -->
                    <div class="form-group">
                        <label>Nama Produk</label>
                        <input type="text" name="nama" placeholder="Masukan Nama Produk" required>
                    </div>
                    
                    <!-- KATEGORI - diambil dari tabel kategori -->
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="id_kategori" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach($kategori_list as $kat): ?>
                            <option value="<?= $kat['id_kategori'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- HARGA -->
                    <div class="form-group">
                        <label>Harga (Rp)</label>
                        <input type="number" name="harga" placeholder="Masukan Harga" required>
                    </div>
                    
                    <!-- STOK -->
                    <div class="form-group">
                        <label>Jumlah (Stok)</label>
                        <input type="number" name="stok" placeholder="Masukan Jumlah" required>
                    </div>
                    
                    <!-- BUTTON -->
                    <div class="form-btn">
                        <button type="submit" class="btn-simpan"><i class="fas fa-save"></i> Simpan</button>
                        <button type="reset" class="btn-reset"><i class="fas fa-undo"></i> Reset</button>
                    </div>
                </form>
            </div>
        </div>
        <?php include '../../components/footer.php'; ?>
    </div>

    <script>
        // Cukup sekali aja, tidak perlu duplicated
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