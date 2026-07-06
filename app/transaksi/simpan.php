<?php
session_start();
if (!isset($_SESSION['admin'])) { 
    header('Location: ../../login.php'); 
    exit; 
}

$keranjangData = isset($_POST['keranjang_data']) ? $_POST['keranjang_data'] : '';

if($keranjangData) {
    $keranjang = json_decode($keranjangData, true);
    $tanggal = $_POST['tanggal'];
    
    if(!is_array($keranjang) || count($keranjang) == 0) {
        echo "<script>alert('Data keranjang kosong!'); window.history.back();</script>";
        exit;
    }
    
    // === SIMPAN TRANSAKSI ===
    $dataTransaksi = json_decode(file_get_contents('../../data/transaksi.json'), true);
    
    foreach($keranjang as $k) {
        $qty = intval($k['qty']);
        $harga = intval($k['harga']);
        $total = $qty * $harga;
        
        // Simpan ke transaksi
        $dataTransaksi[] = [
            'id' => time() + rand(1, 1000),
            'produk' => $k['nama'],
            'jumlah' => $qty,
            'total' => $total,
            'tanggal' => $tanggal
        ];
        
        // === KURANGIN STOK PRODUK ===
        $dataProduk = json_decode(file_get_contents('../../data/produk.json'), true);
        
        foreach($dataProduk as $key => $p) {
            if($p['nama'] == $k['nama']) {
                $stokBaru = intval($p['stok']) - $qty;
                if($stokBaru < 0) $stokBaru = 0;
                $dataProduk[$key]['stok'] = $stokBaru;
            }
        }
        
        file_put_contents('../../data/produk.json', json_encode($dataProduk, JSON_PRETTY_PRINT));
    }
    
    file_put_contents('../../data/transaksi.json', json_encode($dataTransaksi, JSON_PRETTY_PRINT));
    
    echo "<script>alert('Transaksi Berhasil! Stok otomatis dikurangi.'); window.location='index.php';</script>";
    exit;
}

// === FORM MANUAL ===
$data = json_decode(file_get_contents('../../data/transaksi.json'), true);

$data[] = [
    'id' => time(),
    'produk' => $_POST['produk'],
    'jumlah' => intval($_POST['jumlah']),
    'total' => intval($_POST['total']),
    'tanggal' => $_POST['tanggal']
];

file_put_contents('../../data/transaksi.json', json_encode($data, JSON_PRETTY_PRINT));

echo "<script>alert('Berhasil Disimpan'); window.location='index.php';</script>";
exit;