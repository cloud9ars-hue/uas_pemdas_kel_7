<?php
require_once 'koneksi.php';
cekLogin();

$tanggalFilter = $_GET['tanggal'] ?? date('Y-m-d');
$jumlahHariBulan = jumlahHariDalamBulan($tanggalFilter);
$awalBulan = date('Y-m-01', strtotime($tanggalFilter));
$akhirBulan = date('Y-m-t', strtotime($tanggalFilter));

// Rekap transaksi penjualan pada tanggal terpilih
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

// Pengeluaran Modal Harian pada tanggal terpilih
$stmtHarian = mysqli_prepare($conn, "
    SELECT COALESCE(SUM(nominal), 0) AS total_harian
    FROM pengeluaran_bahan
    WHERE tanggal = ? AND jenis_modal = 'MODAL_HARIAN'
");
mysqli_stmt_bind_param($stmtHarian, "s", $tanggalFilter);
mysqli_stmt_execute($stmtHarian);
$pengeluaranHarianLangsung = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($stmtHarian))['total_harian'];

// Total belanja Modal Bulanan pada bulan yang sama, lalu dibagi jumlah hari bulan tersebut
$stmtBulanan = mysqli_prepare($conn, "
    SELECT COALESCE(SUM(nominal), 0) AS total_bulanan
    FROM pengeluaran_bahan
    WHERE tanggal BETWEEN ? AND ? AND jenis_modal = 'MODAL_BULANAN'
");
mysqli_stmt_bind_param($stmtBulanan, "ss", $awalBulan, $akhirBulan);
mysqli_stmt_execute($stmtBulanan);
$totalBulananBulanIni = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($stmtBulanan))['total_bulanan'];
$porsiHarianDariBulanan = hitungPorsiHarianBulanan($totalBulananBulanIni, $tanggalFilter);

$totalPcs = (int)$rekapTrx['total_pcs'];
$totalPemasukan = (int)$rekapTrx['total_pemasukan'];
$pemasukanTunai = (int)$rekapTrx['pemasukan_tunai'];
$pemasukanQris = (int)$rekapTrx['pemasukan_qris'];

// Total modal harian efektif = pengeluaran modal harian + porsi harian dari modal bulanan
$totalModalHarianEfektif = $pengeluaranHarianLangsung + $porsiHarianDariBulanan;
$selisih = $totalPemasukan - $totalModalHarianEfektif;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Harian - SiFlow</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <div class="card">
        <h2>Rekap Harian (<?= htmlspecialchars($tanggalFilter) ?>)</h2>
        <form method="GET" style="display: flex; gap: 10px; align-items: end; flex-wrap: wrap;">
            <div class="form-group" style="margin-bottom: 0;">
                <label>Filter Tanggal Rekap</label>
                <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggalFilter) ?>">
            </div>
            <button type="submit" class="btn btn-navy">Tampilkan</button>
        </form>
    </div>

    <div class="grid-stats">
        <div class="stat-box">
            <div class="label">Total Terjual</div>
            <div class="value"><?= $totalPcs ?> pcs</div>
            <div class="sub">@ <?= formatRupiah(HARGA_SATUAN_DEFAULT) ?> / pcs</div>
        </div>
        <div class="stat-box">
            <div class="label">Total Pemasukan</div>
            <div class="value"><?= formatRupiah($totalPemasukan) ?></div>
            <div class="sub">Tunai: <?= formatRupiah($pemasukanTunai) ?> • QRIS: <?= formatRupiah($pemasukanQris) ?></div>
        </div>
        <div class="stat-box">
            <div class="label">Beban Modal Harian Efektif</div>
            <div class="value"><?= formatRupiah($totalModalHarianEfektif) ?></div>
            <div class="sub">Harian: <?= formatRupiah($pengeluaranHarianLangsung) ?> + Porsi Bulanan: <?= formatRupiah($porsiHarianDariBulanan) ?></div>
        </div>
        <div class="stat-box">
            <div class="label">Selisih (Pemasukan - Modal Harian)</div>
            <div class="value"><?= formatRupiah($selisih) ?></div>
            <div class="sub">Bulan ini terdeteksi <?= $jumlahHariBulan ?> hari</div>
        </div>
    </div>

    <div class="card">
        <h2>Rincian Porsi Modal Bulanan</h2>
        <p style="font-size: 0.92rem; line-height: 1.6;">
            Total pengeluaran <strong>Modal Bulanan</strong> pada bulan <?= htmlspecialchars(date('F Y', strtotime($tanggalFilter))) ?>:
            <strong><?= formatRupiah($totalBulananBulanIni) ?></strong>.
            Dibagi otomatis <strong><?= $jumlahHariBulan ?> hari</strong> =
            <strong><?= formatRupiah($porsiHarianDariBulanan) ?> / hari</strong> sebagai komponen Modal Harian.
        </p>
    </div>
</div>

</body>
</html>