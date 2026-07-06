<?php

$server = "localhost";
$user = "root";
$pass = ""; // Default Laragon/XAMPP kosong
$db = "bakery";

$koneksi = mysqli_connect($server, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($koneksi, "utf8mb4");

function redirect($url) {
    header("Location: $url");
    exit;
}
?>