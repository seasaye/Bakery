<?php
session_start();
/** @var mysqli $koneksi */
require_once '../../config/config.php';

// Nama toko & kasir (statis, bisa diganti aja)
$nama_toko = 'HONEY BUTTER';
$kasir = 'Admin';

// === PROSES SIMPAN TRANSAKSI ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['keranjang_data'])) {
    $keranjang = json_decode($_POST['keranjang_data'], true);

    if (!empty($keranjang)) {
        $uang_dibayar = (int) $_POST['uang_dibayar'];
        $tanggal = date('Y-m-d');

        // Hitung total dulu sebelum insert header
        $total_bayar = 0;
        foreach ($keranjang as $item) {
            $total_bayar += (int) $item['harga'] * (int) $item['qty'];
        }

        // 1. Insert header transaksi, dapat id_transaksi baru
        $stmt = mysqli_prepare($koneksi, "INSERT INTO transaksi (tanggal, total) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "si", $tanggal, $total_bayar);
        mysqli_stmt_execute($stmt);
        $id_transaksi = mysqli_insert_id($koneksi);

        // 2. Insert tiap item ke detail_transaksi + kurangi stok produk
        foreach ($keranjang as $item) {
            $nama_item = $item['nama'];
            $harga_item = (int) $item['harga'];
            $qty_item = (int) $item['qty'];
            $subtotal_item = $harga_item * $qty_item;

            // Cari id_produk berdasarkan nama (karena keranjang JS cuma simpan nama)
            $stmt_cari = mysqli_prepare($koneksi, "SELECT id_produk FROM produk WHERE nama_produk = ?");
            mysqli_stmt_bind_param($stmt_cari, "s", $nama_item);
            mysqli_stmt_execute($stmt_cari);
            $row_produk = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cari));
            $id_produk_item = $row_produk['id_produk'] ?? null;

            // Insert ke detail_transaksi
            $stmt2 = mysqli_prepare($koneksi, "INSERT INTO detail_transaksi (id_transaksi, id_produk, nama_produk, harga, jumlah, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt2, "iisiii", $id_transaksi, $id_produk_item, $nama_item, $harga_item, $qty_item, $subtotal_item);
            mysqli_stmt_execute($stmt2);

            // Kurangi stok produk
            if ($id_produk_item) {
                $stmt3 = mysqli_prepare($koneksi, "UPDATE produk SET stok = stok - ? WHERE id_produk = ?");
                mysqli_stmt_bind_param($stmt3, "ii", $qty_item, $id_produk_item);
                mysqli_stmt_execute($stmt3);
            }
        }

        $kembalian = $uang_dibayar - $total_bayar;

        // Simpan session untuk popup struk
        $_SESSION['popup_sukes'] = [
            'total' => $total_bayar,
            'uang' => $uang_dibayar,
            'kembalian' => $kembalian,
            'keranjang' => json_encode($keranjang),
            'no_trx' => $id_transaksi
        ];

        header('Location: index.php');
        exit;
    }
}

// === SEARCH & FILTER KATEGORI ===
$cari = $_GET['cari'] ?? '';
$kategori = $_GET['kategori'] ?? 'semua';

// Ambil daftar kategori dari database (untuk tombol filter)
$kategori_list = ['semua'];
$res_kat = mysqli_query($koneksi, "SELECT nama_kategori FROM kategori ORDER BY nama_kategori");
while ($row = mysqli_fetch_assoc($res_kat)) {
    $kategori_list[] = $row['nama_kategori'];
}

// Ambil data produk dari database (dengan join kategori untuk filter)
$sql_produk = "SELECT p.*, k.nama_kategori
                FROM produk p
                LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
                WHERE 1=1";

if ($cari !== '') {
    $cari_escaped = mysqli_real_escape_string($koneksi, $cari);
    $sql_produk .= " AND (p.nama_produk LIKE '%$cari_escaped%' OR p.kode_produk LIKE '%$cari_escaped%')";
}
if ($kategori !== 'semua') {
    $kategori_escaped = mysqli_real_escape_string($koneksi, $kategori);
    $sql_produk .= " AND k.nama_kategori = '$kategori_escaped'";
}
$sql_produk .= " ORDER BY p.nama_produk";

$produk = [];
$result = mysqli_query($koneksi, $sql_produk);
while ($row = mysqli_fetch_assoc($result)) {
    $produk[] = $row;
}

$popup_sukes = $_SESSION['popup_sukes'] ?? null;
if ($popup_sukes) { unset($_SESSION['popup_sukes']); }
$judul = "Data Produk";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kasir - Bakery</title>
    <link rel="stylesheet" href="../../assets/lib/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .btn-qty { width: 20px; height: 20px; padding: 0; }
    </style>
</head>
<body>
    <?php include '../../components/sidebar.php'; ?>

    <div class="main-content">
        <?php include '../../components/header.php'; ?>

        <div class="container-fluid mt-4">
            <div class="row">
                <div class="col-md-7">
                    <div class="table-container">
                        <div class="table-header"><h3><i class="fas fa-box"></i> Pilih Produk</h3></div>

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
                            <?php foreach($kategori_list as $kat): ?>
                            <a href="?kategori=<?= htmlspecialchars($kat) ?>&cari=<?= htmlspecialchars($cari) ?>"
                            class="kategori-btn <?= $kategori == $kat ? 'active' : '' ?>">
                                <?= ucfirst($kat) ?>
                            </a>
                            <?php endforeach; ?>
                        </div>

                        <table>
                            <thead><tr><th>Kode</th><th>Nama</th><th>Harga</th><th>Stok</th><th>Aksi</th></tr></thead>
                            <tbody>
                                <?php if(!empty($produk)): ?>
                                <?php foreach($produk as $p): ?>
                                <?php if($p['stok'] > 0): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['kode_produk']) ?></td>
                                    <td><?= htmlspecialchars($p['nama_produk']) ?></td>
                                    <td>Rp <?= number_format($p['harga']) ?></td>
                                    <td><?= $p['stok'] ?></td>
                                    <td><button class="btn btn-primary btn-sm" onclick="tambahKeKeranjang('<?= addslashes($p['nama_produk']) ?>', <?= $p['harga'] ?>, <?= $p['stok'] ?>)"><i class="fas fa-plus"></i> Pilih</button></td>
                                </tr>
                                <?php endif; ?>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr><td colspan="5" class="text-center text-muted">Produk tidak ditemukan</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="table-container">
                        <div class="table-header">
                            <h3><i class="fas fa-shopping-cart"></i> Keranjang</h3>
                            <button class="btn btn-hapus" onclick="clearKeranjang()">Kosongkan</button>
                        </div>
                        
                        <form method="POST" id="formKasir">
                            <input type="hidden" name="keranjang_data" id="keranjangData">
                            <input type="hidden" name="uang_dibayar" id="uangDibayarInput">
                            
                            <div style="padding: 15px;">
                                <table style="width: 100%;">
                                    <thead><tr><th>Produk</th><th>Harga</th><th>Qty</th><th>Total</th><th></th></tr></thead>
                                    <tbody id="isiKeranjang">
                                        <tr><td colspan="5" class="text-center text-muted">Keranjang kosong</td></tr>
                                    </tbody>
                                </table>
                                <hr>
                                <div class="d-flex justify-content-between mb-2">
                                    <strong>Total Bayar:</strong>
                                    <strong id="totalBayarTampil" style="color: green; font-size: 18px;">Rp 0</strong>
                                </div>
                                <div class="form-group mb-2">
                                    <label>Tanggal</label>
                                    <input type="text" class="form-control" value="<?= date('d-m-Y') ?>" readonly>
                                </div>
                                <div class="form-group mb-2">
                                    <label>Uang Dibayar</label>
                                    <input type="number" id="uangDibayar" class="form-control" placeholder="Masukkan uang" onkeyup="hitungKembalian()">
                                </div>
                                <div class="form-group mb-3">
                                    <label>Kembalian</label>
                                    <input type="text" id="kembalian" class="form-control" readonly style="background: #e8f5e9; color: green; font-weight: bold;">
                                </div>
                                <button type="submit" class="btn-simpan w-100" id="btnBayar" disabled>
                                    <i class="fas fa-money-bill-wave"></i> BAYAR & CETAK
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php include '../../components/footer.php'; ?>
    </div>

<!-- POPUP SUKSES -->
<?php if($popup_sukes): ?>
<script>
localStorage.removeItem('keranjang');
</script>

<div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 9999;">
    <div style="background: white; width: 350px; font-family: 'Courier New', monospace; font-size: 13px;">
        <!-- HEADER -->
        <div style="background: #28a745; color: white; padding: 10px; text-align: center;">
            <strong>PEMBAYARAN BERHASIL</strong>
        </div>
        
        <div style="padding: 15px;">
            <div style="text-align: center; border-bottom: 1px dashed #333; padding-bottom: 10px; margin-bottom: 10px;">
                <strong style="font-size: 16px;"><?= strtoupper($nama_toko) ?></strong>
            </div>
            
            <!-- INFO -->
            <div style="margin-bottom: 10px;">
                <div style="display: flex; justify-content: space-between;">
                    <span>Invoice :</span><span>TRX-<?= $popup_sukes['no_trx'] ?></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Tanggal :</span><span><?= date('d M Y') ?></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Kasir   :</span><span><?= htmlspecialchars($kasir) ?></span>
                </div>
            </div>
            
            <!-- HEADER TABLE -->
            <div style="border-top: 1px dashed #333; border-bottom: 1px dashed #333; padding: 8px 0; margin: 10px 0;">
                <div style="display: flex; font-weight: bold;">
                    <span style="width: 50%;">Produk</span>
                    <span style="width: 15%; text-align: center;">Qty</span>
                    <span style="width: 35%; text-align: right;">Total</span>
                </div>
            </div>
            
            <!-- ITEM TABLE -->
            <div style="border-bottom: 1px dashed #333; padding: 8px 0; margin: 0;">
                <?php foreach(json_decode($popup_sukes['keranjang'], true) as $item): ?>
                <div style="display: flex; margin-bottom: 5px;">
                    <span style="width: 50%;"><?= htmlspecialchars($item['nama']) ?></span>
                    <span style="width: 15%; text-align: center;"><?= $item['qty'] ?></span>
                    <span style="width: 35%; text-align: right;">Rp <?= number_format($item['total']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- TOTAL SECTION -->
            <div style="margin-top: 10px;">
                <div style="display: flex; justify-content: space-between;">
                    <strong>Total       :</strong><strong>Rp <?= number_format($popup_sukes['total']) ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Bayar       :</span><span>Rp <?= number_format($popup_sukes['uang']) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Kembalian   :</span><span>Rp <?= number_format($popup_sukes['kembalian']) ?></span>
                </div>
            </div>
            
            <!-- FOOTER -->
            <div style="text-align: center; border-top: 1px dashed #333; padding-top: 10px; margin-top: 10px;">
                <strong>TERIMA KASIH SUDAH BELANJA</strong>
            </div>
        </div>
        
        <!-- TOMBOL -->
        <div style="padding: 15px; display: flex; gap: 10px; justify-content: center; border-top: 1px solid #ddd;">
            <button type="button" class="btn btn-primary" onclick="cetakStruk()">
                <i class="fas fa-print"></i> Cetak
            </button>
            <button type="button" class="btn btn-secondary" onclick="location.reload()">Selesai</button>
        </div>
    </div>
</div>
<?php endif; ?>

    <script>
        let keranjang = JSON.parse(localStorage.getItem('keranjang')) || [];
        let dataPopup = <?= json_encode($popup_sukes ?? ['keranjang' => '[]']) ?>;
        
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('active'); }
        
        function tambahKeKeranjang(nama, harga, stokMax) {
            let ada = keranjang.findIndex(k => k.nama === nama);
            if (ada >= 0) {
                if (keranjang[ada].qty < stokMax) { keranjang[ada].qty++; keranjang[ada].total = keranjang[ada].qty * harga; }
            } else { keranjang.push({ nama: nama, harga: parseInt(harga), qty: 1, total: parseInt(harga), stok: parseInt(stokMax) }); }
            renderKeranjang();
        }
        
        function renderKeranjang() {
            let tbody = document.getElementById('isiKeranjang');
            let totalBayar = keranjang.reduce((sum, k) => sum + k.total, 0);
            if (keranjang.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Keranjang kosong</td></tr>';
                document.getElementById('totalBayarTampil').innerText = 'Rp 0';
                document.getElementById('keranjangData').value = '';
                document.getElementById('btnBayar').disabled = true;
                return;
            }
            let html = '';
            keranjang.forEach((item, i) => {
                html += '<tr>';
                html += '<td>' + item.nama + '</td>';
                html += '<td>Rp ' + item.harga.toLocaleString() + '</td>';
                html += '<td><button class="btn btn-warning btn-sm btn-qty" onclick="qtyMinus(' + i + ')">-</button> ' + item.qty + ' <button class="btn btn-primary btn-sm btn-qty" onclick="qtyPlus(' + i + ')">+</button></td>';
                html += '<td>Rp ' + item.total.toLocaleString() + '</td>';
                html += '<td><button class="btn btn-hapus btn-sm" onclick="hapusItem(' + i + ')">×</button></td>';
                html += '</tr>';
            });
            tbody.innerHTML = html;
            document.getElementById('totalBayarTampil').innerText = 'Rp ' + totalBayar.toLocaleString();
            document.getElementById('keranjangData').value = JSON.stringify(keranjang);
            localStorage.setItem('keranjang', JSON.stringify(keranjang));
            checkBayar();
        }
        
        function qtyPlus(index) { if (keranjang[index].qty < keranjang[index].stok) { keranjang[index].qty++; keranjang[index].total = keranjang[index].qty * keranjang[index].harga; renderKeranjang(); } }
        function qtyMinus(index) { if (keranjang[index].qty > 1) { keranjang[index].qty--; keranjang[index].total = keranjang[index].qty * keranjang[index].harga; } else { keranjang.splice(index, 1); } renderKeranjang(); }
        function hapusItem(index) {
            keranjang.splice(index, 1);
            
            if (keranjang.length === 0) {
                localStorage.removeItem('keranjang');
            }

            renderKeranjang(); 
        }
        
        function hitungKembalian() {
            let total = keranjang.reduce((sum, k) => sum + k.total, 0);
            let uang = parseInt(document.getElementById('uangDibayar').value) || 0;
            let kembalian = uang - total;
            document.getElementById('kembalian').value = kembalian >= 0 ? 'Rp ' + kembalian.toLocaleString() : 'Kurang Rp ' + Math.abs(kembalian).toLocaleString();
            checkBayar();
        }
        
        function checkBayar() {
            let total = keranjang.reduce((sum, k) => sum + k.total, 0);
            let uang = parseInt(document.getElementById('uangDibayar').value) || 0;
            document.getElementById('btnBayar').disabled = !(keranjang.length > 0 && uang >= total && total > 0);
        }
        
        function clearKeranjang() {
            keranjang = [];
            localStorage.removeItem('keranjang');

            document.getElementById('uangDibayar').value = '';
            document.getElementById('kembalian').value = '';
            renderKeranjang(); }
        
        function cetakStruk() {
            let url = 'cetak.php?total=' + dataPopup.total + '&uang=' + dataPopup.uang + '&kembalian=' + dataPopup.kembalian + '&no_trx=' + dataPopup.no_trx + '&keranjang=' + encodeURIComponent(dataPopup.keranjang);
            window.open(url, '_blank');
        }
        
        document.getElementById('formKasir').addEventListener('submit', function(e) {
            if (keranjang.length === 0) { e.preventDefault(); alert('Keranjang masih kosong!'); }
            else { document.getElementById('uangDibayarInput').value = document.getElementById('uangDibayar').value; }
        });

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

    // Tampilkan kembali keranjang setelah reload/search
    renderKeranjang();
    </script>
</body>
</html>