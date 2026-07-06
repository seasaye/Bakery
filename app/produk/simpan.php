<?php
/** @var mysqli $koneksi */
require_once '../../config/config.php';

$kode = $_POST['kode'];
$nama = $_POST['nama'];
$harga = (int) $_POST['harga'];
$stok = (int) $_POST['stok'];
$id_kategori = (int) $_POST['id_kategori'];

// Cek duplikat kode produk
$kode_escaped = mysqli_real_escape_string($koneksi, $kode);
$cek = mysqli_query($koneksi, "SELECT id_produk FROM produk WHERE kode_produk = '$kode_escaped'");

if (mysqli_num_rows($cek) > 0) {
    echo "<script>alert('Kode barang sudah ada!'); window.history.back();</script>";
    exit;
}

// Simpan produk baru pakai prepared statement (aman dari SQL injection)
$stmt = mysqli_prepare($koneksi, "INSERT INTO produk (kode_produk, nama_produk, harga, stok, id_kategori) VALUES (?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "ssiii", $kode, $nama, $harga, $stok, $id_kategori);
mysqli_stmt_execute($stmt);

echo "<script>alert('Berhasil Disimpan'); window.location='index.php';</script>";
exit;