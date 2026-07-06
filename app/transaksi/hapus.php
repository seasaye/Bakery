<?php
/** @var mysqli $koneksi */
require_once '../../config/config.php';

$id_transaksi = (int) $_GET['id'];

// 1. Ambil semua item dalam transaksi ini (untuk kembalikan stok)
$stmt = mysqli_prepare($koneksi, "SELECT * FROM detail_transaksi WHERE id_transaksi = ?");
mysqli_stmt_bind_param($stmt, "i", $id_transaksi);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
}

// 2. Kembalikan stok untuk setiap item
foreach ($items as $item) {
    $stmt2 = mysqli_prepare($koneksi, "UPDATE produk SET stok = stok + ? WHERE id_produk = ?");
    mysqli_stmt_bind_param($stmt2, "ii", $item['jumlah'], $item['id_produk']);
    mysqli_stmt_execute($stmt2);
}

// 3. Hapus semua detail_transaksi milik transaksi ini
$stmt3 = mysqli_prepare($koneksi, "DELETE FROM detail_transaksi WHERE id_transaksi = ?");
mysqli_stmt_bind_param($stmt3, "i", $id_transaksi);
mysqli_stmt_execute($stmt3);

// 4. Hapus header transaksi
$stmt4 = mysqli_prepare($koneksi, "DELETE FROM transaksi WHERE id_transaksi = ?");
mysqli_stmt_bind_param($stmt4, "i", $id_transaksi);
mysqli_stmt_execute($stmt4);

header('Location: index.php');
exit;
?>