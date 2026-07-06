<?php
$keranjang = json_decode($_GET['keranjang'] ?? '[]', true);
$total = (int)($_GET['total'] ?? 0);
$uang = (int)($_GET['uang'] ?? 0);
$kembalian = (int)($_GET['kembalian'] ?? 0);
$no_trx = isset($_GET['no_trx']) ? 'TRX-' . $_GET['no_trx'] : 'INV-' . date('Ymd');
$nama_toko = 'HONEY BUTTER';
$kasir = $_GET['kasir'] ?? 'Admin';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Struk Transaksi - <?= $nama_toko ?></title>
    <style>
        * { font-family: 'Courier New', monospace; margin: 0; padding: 0; }
        body { padding: 20px; font-size: 14px; }
        .struk { max-width: 320px; margin: 0 auto; padding: 15px; background: white; }
        .text-center { text-align: center; }
        hr { border: 0; border-top: 1px dashed #333; margin: 10px 0; }
    </style>
</head>
<body onload="autoPrint()">
    <div class="struk">
        <div class="text-center">
            <strong style="font-size: 18px;"><?= $nama_toko ?></strong>
        </div>
        
        <hr>
        
        <div>
            <div style="display: flex; justify-content: space-between;">
                <span>Invoice :</span><span><?= $no_trx ?></span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Tanggal :</span><span><?= date('d M Y') ?></span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Kasir   :</span><span><?= $kasir ?></span>
            </div>
        </div>
        
        <hr>
        
        <div style="border-bottom: 1px dashed #333; padding-bottom: 5px; margin-bottom: 5px; font-weight: bold;">
            <span style="width: 45%;">Produk</span>
            <span style="width: 15%; text-align: center;">Qty</span>
            <span style="width: 40%; text-align: right;">Total</span>
        </div>
        
        <div>
            <?php if(!empty($keranjang)): ?>
            <?php foreach($keranjang as $item): ?>
            <div style="display: flex; margin-bottom: 3px;">
                <span style="width: 45%;"><?= $item['nama'] ?></span>
                <span style="width: 15%; text-align: center;"><?= $item['qty'] ?></span>
                <span style="width: 40%; text-align: right;">Rp <?= number_format($item['total']) ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <hr>
        
        <div>
            <div style="display: flex; justify-content: space-between; font-weight: bold;">
                <span>Total    :</span><span>Rp <?= number_format($total) ?></span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Bayar    :</span><span>Rp <?= number_format($uang) ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; font-weight: bold;">
                <span>Kembali  :</span><span>Rp <?= number_format($kembalian) ?></span>
            </div>
        </div>
        
        <hr>
        
        <div class="text-center">
            <strong>TERIMA KASIH SUDAH BELANJA</strong>
        </div>
    </div>

    <script>
        function autoPrint() {
            // Langsung munculin print dialog
            window.print();
            
            // Après print/cancel, langsung balik ke kasir
            window.onafterprint = function() {
                window.location.href = '../kasir/index.php';
            };
            
            // Fallback: kalo onafterprint gawork, pake setTimeout
            setTimeout(function() {
                window.location.href = '../kasir/index.php';
            }, 1000);
        }
    </script>
</body>
</html>