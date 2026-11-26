<?php
require_once 'koneksi.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$pesanError = "";
$pesanSukses = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';

    if ($nama === '' || $username === '' || $password === '') {
        $pesanError = "Semua kolom wajib diisi.";
    } elseif ($password !== $konfirmasi) {
        $pesanError = "Konfirmasi password tidak cocok.";
    } else {
        $stmtCek = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmtCek, "s", $username);
        mysqli_stmt_execute($stmtCek);
        mysqli_stmt_store_result($stmtCek);

        if (mysqli_stmt_num_rows($stmtCek) > 0) {
            $pesanError = "Username sudah terdaftar.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmtInsert = mysqli_prepare($conn, "INSERT INTO users (nama_lengkap, username, password) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmtInsert, "sss", $nama, $username, $hash);

            if (mysqli_stmt_execute($stmtInsert)) {
                $pesanSukses = "Registrasi berhasil! Silakan login ke SiFlow.";
            } else {
                $pesanError = "Gagal menyimpan akun.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SiFlow</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-brand">SiFlow</div>
        <h1>Buat Akun Baru</h1>
        <p class="subtitle">Terang Bulan & Martabak Mini Ibu Nur</p>

        <?php if ($pesanError): ?>
            <div class="alert alert-error"><?= htmlspecialchars($pesanError) ?></div>
        <?php endif; ?>

        <?php if ($pesanSukses): ?>
            <div class="alert alert-success"><?= htmlspecialchars($pesanSukses) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama_lengkap" placeholder="Contoh: Ibu Nur" required>
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Masukkan username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Masukkan password" required>
            </div>
            <div class="form-group">
                <label>Konfirmasi Password</label>
                <input type="password" name="konfirmasi_password" placeholder="Ulangi password" required>
            </div>
            <button type="submit" class="btn btn-navy" style="width: 100%;">Daftar</button>
        </form>

        <div class="auth-footer">
            Sudah punya akun? <a href="login.php">Login di sini</a>
        </div>
    </div>
</div>
</body>
</html>