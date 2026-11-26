<?php
require_once 'koneksi.php';
cekLogin();

$pesan = "";
$tipePesan = "success";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $varian = trim($_POST['varian'] ?? '');
    $jumlah = (int)($_POST['jumlah'] ?? 0);
    $metodeBayar = $_POST['metode_bayar'] ?? '';
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');

    if (!in_array($varian, DAFTAR_TOPPING, true) || $jumlah <= 0 || !in_array($metodeBayar, ['TUNAI', 'QRIS'], true)) {
        $pesan = "Input tidak valid. Pastikan topping, jumlah pcs, dan metode bayar dipilih dengan benar.";
        $tipePesan = "error";
    } else {
        $hargaSatuan = HARGA_SATUAN_DEFAULT;
        $subtotal = $jumlah * $hargaSatuan;

        $stmt = mysqli_prepare($conn, "INSERT INTO transaksi_penjualan (varian, jumlah, harga_satuan, subtotal, metode_bayar, tanggal) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "siiiss", $varian, $jumlah, $hargaSatuan, $subtotal, $metodeBayar, $tanggal);

        if (mysqli_stmt_execute($stmt)) {
            $pesan = "Transaksi tersimpan: $varian ($jumlah pcs) = " . formatRupiah($subtotal) . " [$metodeBayar]";
        } else {
            $pesan = "Gagal menyimpan transaksi.";
            $tipePesan = "error";
        }
    }
}

// Filter: tanggal, metode pembayaran (dropdown), dan topping (dropdown)
$filterTanggal = $_GET['tanggal'] ?? '';
$filterMetode = $_GET['metode'] ?? '';
$filterTopping = $_GET['topping'] ?? '';

$where = [];
$params = [];
$types = "";

if ($filterTanggal !== '') {
    $where[] = "tanggal = ?";
    $params[] = $filterTanggal;
    $types .= "s";
}
if ($filterMetode === 'TUNAI' || $filterMetode === 'QRIS') {
    $where[] = "metode_bayar = ?";
    $params[] = $filterMetode;
    $types .= "s";
}
if ($filterTopping !== '' && in_array($filterTopping, DAFTAR_TOPPING, true)) {
    $where[] = "varian = ?";
    $params[] = $filterTopping;
    $types .= "s";
}

$sql = "SELECT * FROM transaksi_penjualan";
if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY id DESC";

$stmtList = mysqli_prepare($conn, $sql);
if (count($params) > 0) {
    mysqli_stmt_bind_param($stmtList, $types, ...$params);
}
mysqli_stmt_execute($stmtList);
$daftarTransaksi = mysqli_stmt_get_result($stmtList);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Penjualan - SiFlow</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <?php if ($pesan): ?>
        <div class="alert alert-<?= $tipePesan ?>"><?= htmlspecialchars($pesan) ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Input Transaksi Penjualan</h2>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Pilihan Topping</label>
                    <select name="varian" required>
                        <option value="">-- Pilih Topping --</option>
                        <?php foreach (DAFTAR_TOPPING as $top): ?>
                            <option value="<?= $top ?>"><?= $top ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jumlah (pcs) @ <?= formatRupiah(HARGA_SATUAN_DEFAULT) ?></label>
                    <input type="number" name="jumlah" min="1" placeholder="Contoh: 4" required>
                </div>
                <div class="form-group">
                    <label>Metode Pembayaran</label>
                    <select name="metode_bayar" required>
                        <option value="TUNAI">Tunai</option>
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
        <form method="GET" style="margin-bottom: 14px;">
            <div class="form-row">
                <div class="form-group">
                    <label>Filter Tanggal</label>
                    <input type="date" name="tanggal" value="<?= htmlspecialchars($filterTanggal) ?>">
                </div>
                <div class="form-group">
                    <label>Filter Metode Pembayaran</label>
                    <select name="metode">
                        <option value="">Semua Metode</option>
                        <option value="TUNAI" <?= $filterMetode === 'TUNAI' ? 'selected' : '' ?>>Tunai</option>
                        <option value="QRIS" <?= $filterMetode === 'QRIS' ? 'selected' : '' ?>>QRIS</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Filter Topping</label>
                    <select name="topping">
                        <option value="">Semua Topping</option>
                        <?php foreach (DAFTAR_TOPPING as $top): ?>
                            <option value="<?= $top ?>" <?= $filterTopping === $top ? 'selected' : '' ?>><?= $top ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="submit" class="btn btn-navy">Terapkan Filter</button>
                <a href="transaksi.php" class="btn btn-outline">Reset Filter</a>
            </div>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tanggal</th>
                        <th>Topping</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                        <th>Metode Pembayaran</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($daftarTransaksi) === 0): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--teks-muted);">Tidak ada data transaksi yang sesuai filter.</td>
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
                                    <span class="badge <?= $row['metode_bayar'] === 'TUNAI' ? 'badge-tunai' : 'badge-qris' ?>">
                                        <?= $row['metode_bayar'] === 'TUNAI' ? 'Tunai' : 'QRIS' ?>
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