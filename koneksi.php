<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_warung_ibunur";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Konstanta acuan sesuai data mitra Ibu Nur
define('HARGA_SATUAN_DEFAULT', 5000);
define('MODAL_HARIAN_DEFAULT', 200000);

function formatRupiah($angka) {
    return "Rp" . number_format($angka, 0, ',', '.');
}

function cekLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}
?>