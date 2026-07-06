<?php
// MULAI SESSION SECARA GLOBAL
session_start();

// ==========================================
// BAGIAN 1: LOGIKA SERVER-SIDE (PHP & DATABASE)
// ==========================================
// Panggil file koneksi (Clean Code)
require_once '../config/config.php';

$errorPhp = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form
    $user = $_POST["username"];
    $pass = $_POST["password"];

    // LOGIKA TERSTRUKTUR: Keamanan dasar (Jangan biarkan kolom kosong)
    if ($user == "" || $pass == "") {
        $errorPhp = "Username dan Password tidak boleh kosong!";
    } else {
        // LOGIKA TERSTRUKTUR: Query ke Database
        // 1. Tulis perintah SQL (Cari baris di tabel user yang usernamenya sama, dan passwordnya sama)
        $sql = "SELECT * FROM user WHERE username='$user' AND password='$pass'";

        /** @var mysqli $koneksi */ // <--- extension VS Code akan langsung paham dan tanda problem merah akan hilang
        // VSCode akan otomatis memberikan autocomplete (saran kode) saat Anda mengetik $koneksi->

        // 2. Eksekusi Query
        $result = mysqli_query($koneksi, $sql);

        // 3. Cek apakah data ditemukan
        // mysqli_num_rows() menghitung jumlah baris yang dikembalikan database
        if (mysqli_num_rows($result) == 1) {
            // Jika hasilnya tepat 1 baris, berarti login benar
            // --- TAMBAHKAN KODE INI ---
            // Membuat Session Login
            $row = mysqli_fetch_assoc($result);

            $_SESSION['is_logged_in'] = true;
            $_SESSION['username'] = $row['username'];
            $_SESSION['nama'] = $row['nama']; // kolom nama di database

            // Jika berhasil, pindahkan halaman secara pakai oleh Server
            header("Location: dashboard.php");
            exit();
        } else {
            // Jika 0 baris, berarti data tidak cocok
            $errorPhp = "Login Gagal! Username atau Password salah di Database.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bakery Admin</title>
    <link rel="stylesheet" href="../assets/lib/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-body">
    <div class="login-box">
        <h2><i class="fas fa-bread-slice"></i> Bakery Admin</h2>
        
        <?php if($errorPhp): ?>
            <div class="alert alert-danger"><?= $errorPhp ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
            </div>
            <button type="submit" name="login" class="btn-login"><i class="fas fa-sign-in-alt"></i> LOGIN</button>
        </form>
    </div>
</body>
</html>