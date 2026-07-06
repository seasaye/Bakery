<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: ../../login.php'); exit; }

$keranjang = json_decode($_POST['keranjang_data'], true);

if (empty($keranjang)) { die("Keranjang kosong!"); }

// Load data
$transaksi_data = json_decode(file_get_contents('../../data/transaksi.json'), true);
$produk_data = json_decode(file_get_contents('../../data/produk.json'), true);

// Generate ID
$id_baru = 'TRX-' . date('YmdHis') . rand(100, 999);

// Proses
$total_bayar = 0;
foreach ($keranjang as $item) {
    $total_item = $item['harga'] * $item['qty'];
    $total_bayar += $total_item;
    
    $transaksi_data[] = [
        'id' => $id_baru,
        'produk' => $item['nama'],
        'harga' => $item['harga'],
        'jumlah' => $item['qty'],
        'total' => $total_item,
        'tanggal' => date('Y-m-d')
    ];
    
    foreach ($produk_data as &$p) {
        if ($p['nama'] == $item['nama']) { $p['stok'] -= $item['qty']; }
    }
}

// Simpan
file_put_contents('../../data/transaksi.json', json_encode($transaksi_data, JSON_PRETTY_PRINT));
file_put_contents('../../data/produk.json', json_encode($produk_data, JSON_PRETTY_PRINT));

// Set session untuk popup (nanti dihapus setelah显示)
$_SESSION['popup_skses'] = [
    'total' => $total_bayar,
    'uang' => $total_bayar, // Diasumsikan lunas, bisa diganti sesuai input
    'kembalian' => 0,
    'keranjang' => json_encode($keranjang)
];

header('Location: index.php');