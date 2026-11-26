<?php
require_once 'koneksi.php';
cekLogin();

$pesan = "";
$tipePesan = "success";

// Proses tambah transaksi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $varian = trim($_POST['varian'] ?? '');
    $jumlah = (int)($_POST['jumlah'] ?? 0);
    $metodeBayar = $_POST['metode_bayar'] ?? '';
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');

    if ($varian === '' || $jumlah <= 0 || !in_array($metodeBayar, ['TUNAI', 'QRIS'])) {
        $pesan = "Input tidak valid. Pastikan varian terisi, jumlah > 0, dan metode bayar benar.";
        $tipePesan = "error";
    } else {
        $hargaSatuan = HARGA_SATUAN_DEFAULT;
        $subtotal = $jumlah * $hargaSatuan;

        $stmt = mysqli_prepare($conn, "INSERT INTO transaksi_penjualan (varian, jumlah, harga_satuan, subtotal, metode_bayar, tanggal) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "siiiss", $varian, $jumlah, $hargaSatuan, $subtotal, $metodeBayar, $tanggal);

        if (mysqli_stmt_execute($stmt)) {
            $pesan = "Transaksi berhasil disimpan: $varian ($jumlah pcs) = " . formatRupiah($subtotal) . " [$metodeBayar]";
        } else {
            $pesan = "Gagal menyimpan transaksi.";
            $tipePesan = "error";
        }
    }
}

// Filter metode bayar (SEMUA / TUNAI / QRIS)
$filterMetode = $_GET['metode'] ?? 'SEMUA';
if ($filterMetode === 'TUNAI' || $filterMetode === 'QRIS') {
    $stmtList = mysqli_prepare($conn, "SELECT * FROM transaksi_penjualan WHERE metode_bayar = ? ORDER BY id DESC");
    mysqli_stmt_bind_param($stmtList, "s", $filterMetode);
    mysqli_stmt_execute($stmtList);
    $daftarTransaksi = mysqli_stmt_get_result($stmtList);
} else {
    $daftarTransaksi = mysqli_query($conn, "SELECT * FROM transaksi_penjualan ORDER BY id DESC");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Penjualan - Warung Ibu Nur</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <?php if ($pesan): ?>
        <div class="alert alert-<?= $tipePesan ?>"><?= htmlspecialchars($pesan) ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Catat Transaksi Penjualan Baru</h2>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Varian / Topping</label>
                    <input type="text" name="varian" placeholder="Contoh: Keju, Kacang, Cokelat" required>
                </div>
                <div class="form-group">
                    <label>Jumlah (pcs) @ Rp5.000</label>
                    <input type="number" name="jumlah" min="1" placeholder="Contoh: 5" required>
                </div>
                <div class="form-group">
                    <label>Metode Pembayaran</label>
                    <select name="metode_bayar" required>
                        <option value="TUNAI">TUNAI</option>
                        <option value="QRIS">QRIS</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            <button type="submit" class="btn">Simpan Transaksi</button>
        </form>
    </div>

    <div class="card">
        <h2>Daftar & Filter Transaksi Penjualan</h2>
        <div style="margin-bottom: 14px; display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="transaksi.php?metode=SEMUA" class="btn btn-outline <?= $filterMetode == 'SEMUA' ? 'active' : '' ?>">Semua</a>
            <a href="transaksi.php?metode=TUNAI" class="btn btn-outline <?= $filterMetode == 'TUNAI' ? 'active' : '' ?>">Filter TUNAI</a>
            <a href="transaksi.php?metode=QRIS" class="btn btn-outline <?= $filterMetode == 'QRIS' ? 'active' : '' ?>">Filter QRIS</a>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tanggal</th>
                        <th>Varian / Topping</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                        <th>Metode</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($daftarTransaksi) === 0): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #64748b;">Belum ada data transaksi ditemukan.</td>
                        </tr>
                    <?php else: ?>
                        <?php while ($row = mysqli_fetch_assoc($daftarTransaksi)): ?>
                            <tr>
                                <td>#<?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['tanggal']) ?></td>
                                <td><?= htmlspecialchars($row['varian']) ?></td>
                                <td><?= $row['jumlah'] ?> pcs</td>
                                <td><strong><?= formatRupiah($row['subtotal']) ?></strong></td>
                                <td>
                                    <span class="badge <?= $row['metode_bayar'] == 'TUNAI' ? 'badge-tunai' : 'badge-qris' ?>">
                                        <?= $row['metode_bayar'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>