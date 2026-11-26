<?php
require_once 'koneksi.php';
cekLogin();

$tanggalFilter = $_GET['tanggal'] ?? date('Y-m-d');

// Hitung rekap transaksi penjualan pada tanggal terpilih
$stmtTrx = mysqli_prepare($conn, "
    SELECT 
        COALESCE(SUM(jumlah), 0) AS total_pcs,
        COALESCE(SUM(subtotal), 0) AS total_pemasukan,
        COALESCE(SUM(CASE WHEN metode_bayar = 'TUNAI' THEN subtotal ELSE 0 END), 0) AS pemasukan_tunai,
        COALESCE(SUM(CASE WHEN metode_bayar = 'QRIS' THEN subtotal ELSE 0 END), 0) AS pemasukan_qris
    FROM transaksi_penjualan
    WHERE tanggal = ?
");
mysqli_stmt_bind_param($stmtTrx, "s", $tanggalFilter);
mysqli_stmt_execute($stmtTrx);
$rekapTrx = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtTrx));

// Hitung total pengeluaran bahan baku pada tanggal terpilih
$stmtKeluar = mysqli_prepare($conn, "
    SELECT COALESCE(SUM(nominal), 0) AS total_pengeluaran
    FROM pengeluaran_bahan
    WHERE tanggal = ?
");
mysqli_stmt_bind_param($stmtKeluar, "s", $tanggalFilter);
mysqli_stmt_execute($stmtKeluar);
$rekapKeluar = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtKeluar));

$totalPcs = (int)$rekapTrx['total_pcs'];
$totalPemasukan = (int)$rekapTrx['total_pemasukan'];
$pemasukanTunai = (int)$rekapTrx['pemasukan_tunai'];
$pemasukanQris = (int)$rekapTrx['pemasukan_qris'];
$totalPengeluaran = (int)$rekapKeluar['total_pengeluaran'];
$selisihModal = $totalPemasukan - (MODAL_HARIAN_DEFAULT + $totalPengeluaran);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Rekap Harian - Warung Ibu Nur</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <div class="card">
        <h2>Rekap Harian Usaha Ibu Nur</h2>
        <form method="GET" style="display: flex; gap: 10px; align-items: end; flex-wrap: wrap;">
            <div class="form-group" style="margin-bottom: 0;">
                <label>Pilih Tanggal Laporan</label>
                <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggalFilter) ?>">
            </div>
            <button type="submit" class="btn">Tampilkan Rekap</button>
        </form>
    </div>

    <div class="grid-stats">
        <div class="stat-box">
            <div class="label">Total Terjual</div>
            <div class="value"><?= $totalPcs ?> pcs</div>
            <div class="sub">Harga satuan: <?= formatRupiah(HARGA_SATUAN_DEFAULT) ?></div>
        </div>
        <div class="stat-box">
            <div class="label">Total Pemasukan</div>
            <div class="value"><?= formatRupiah($totalPemasukan) ?></div>
            <div class="sub">Tunai: <?= formatRupiah($pemasukanTunai) ?> | QRIS: <?= formatRupiah($pemasukanQris) ?></div>
        </div>
        <div class="stat-box">
            <div class="label">Pengeluaran Tercatat</div>
            <div class="value"><?= formatRupiah($totalPengeluaran) ?></div>
            <div class="sub">Acuan Modal Harian: <?= formatRupiah(MODAL_HARIAN_DEFAULT) ?></div>
        </div>
        <div class="stat-box">
            <div class="label">Selisih (Pemasukan - [Modal + Keluar])</div>
            <div class="value"><?= formatRupiah($selisihModal) ?></div>
            <div class="sub"><?= $selisihModal >= 0 ? 'Surplus dari acuan modal' : 'Belum melampaui acuan modal' ?></div>
        </div>
    </div>

    <div class="card">
        <h2>Aksi Cepat</h2>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="transaksi.php" class="btn">+ Catat Transaksi Penjualan</a>
            <a href="pengeluaran.php" class="btn btn-outline">+ Catat Pengeluaran Bahan</a>
        </div>
    </div>
</div>

</body>
</html>