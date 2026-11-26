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

define('HARGA_SATUAN_DEFAULT', 5000);
define('MODAL_HARIAN_DEFAULT', 200000);
define('DAFTAR_TOPPING', ['Keju', 'Cokelat', 'Kacang', 'Matcha', 'Tiramisu']);

function formatRupiah($angka) {
    return "Rp" . number_format((float)$angka, 0, ',', '.');
}

function cekLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

// Mendeteksi otomatis jumlah hari dalam bulan dari tanggal (format YYYY-MM-DD)
function jumlahHariDalamBulan($tanggal) {
    $ts = strtotime($tanggal);
    if ($ts === false) {
        $ts = time();
    }
    return (int)date('t', $ts);
}

// Menghitung porsi harian dari nominal modal bulanan berdasarkan jumlah hari di bulan tsb
function hitungPorsiHarianBulanan($nominalBulanan, $tanggal) {
    $hari = jumlahHariDalamBulan($tanggal);
    if ($hari <= 0) {
        return 0;
    }
    return (int)round($nominalBulanan / $hari);
}
?>