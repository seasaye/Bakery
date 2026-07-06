<?php
/** @var mysqli $koneksi */
require_once '../../config/config.php';

$id = (int) $_GET['id'];

$stmt = mysqli_prepare($koneksi, "DELETE FROM produk WHERE id_produk = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

header('Location: index.php');
exit;