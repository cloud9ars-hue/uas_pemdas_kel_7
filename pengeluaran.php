<?php
require_once 'koneksi.php';
cekLogin();

$pesan = "";
$tipePesan = "success";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keterangan = trim($_POST['keterangan'] ?? '');
    $nominal = (int)($_POST['nominal'] ?? 0);
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');

    if ($keterangan === '' || $nominal <= 0) {
        $pesan = "Keterangan harus diisi dan nominal pengeluaran harus lebih dari 0.";
        $tipePesan = "error";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO pengeluaran_bahan (keterangan, nominal, tanggal) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sis", $keterangan, $nominal, $tanggal);

        if (mysqli_stmt_execute($stmt)) {
            $pesan = "Pengeluaran dicatat: $keterangan sebesar " . formatRupiah($nominal);
        } else {
            $pesan = "Gagal menyimpan data pengeluaran.";
            $tipePesan = "error";
        }
    }
}

$daftarPengeluaran = mysqli_query($conn, "SELECT * FROM pengeluaran_bahan ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengeluaran Bahan Baku - Warung Ibu Nur</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <?php if ($pesan): ?>
        <div class="alert alert-<?= $tipePesan ?>"><?= htmlspecialchars($pesan) ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Catat Pengeluaran Bahan Baku</h2>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Keterangan Belanja</label>
                    <input type="text" name="keterangan" placeholder="Contoh: Beli tepung, telur, keju, kemasan" required>
                </div>
                <div class="form-group">
                    <label>Nominal Pengeluaran (Rp)</label>
                    <input type="number" name="nominal" min="1" placeholder="Contoh: 50000" required>
                </div>
                <div class="form-group">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            <button type="submit" class="btn">Simpan Pengeluaran</button>
        </form>
    </div>

    <div class="card">
        <h2>Riwayat Pengeluaran Bahan Baku</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                        <th>Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($daftarPengeluaran) === 0): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #64748b;">Belum ada pengeluaran yang tercatat.</td>
                        </tr>
                    <?php else: ?>
                        <?php while ($row = mysqli_fetch_assoc($daftarPengeluaran)): ?>
                            <tr>
                                <td>#<?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['tanggal']) ?></td>
                                <td><?= htmlspecialchars($row['keterangan']) ?></td>
                                <td><strong><?= formatRupiah($row['nominal']) ?></strong></td>
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