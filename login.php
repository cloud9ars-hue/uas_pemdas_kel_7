<?php
require_once 'koneksi.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$pesanError = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $pesanError = "Username dan password wajib diisi.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, nama_lengkap, password FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            header("Location: index.php");
            exit;
        } else {
            $pesanError = "Username atau password salah.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SiFlow</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-brand">SiFlow</div>
        <h1>Masuk ke Aplikasi</h1>
        <p class="subtitle">Terang Bulan & Martabak Mini Ibu Nur</p>

        <?php if ($pesanError): ?>
            <div class="alert alert-error"><?= htmlspecialchars($pesanError) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Masukkan username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="btn btn-navy" style="width: 100%;">Login</button>
        </form>

        <div class="auth-footer">
            Belum punya akun? <a href="register.php">Register di sini</a>
        </div>
    </div>
</div>
</body>
</html>