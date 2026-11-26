<?php
require_once 'koneksi.php';
cekLogin();

$pesan = "";
$tipePesan = "success";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keterangan = trim($_POST['keterangan'] ?? '');
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $nominal = (int)($_POST['nominal'] ?? 0);
    $jenisModal = $_POST['jenis_modal'] ?? 'MODAL_HARIAN';

    if ($keterangan === '' || $nominal <= 0 || !in_array($jenisModal, ['MODAL_HARIAN', 'MODAL_BULANAN'], true)) {
        $pesan = "Input tidak valid. Lengkapi keterangan bahan, harga (> 0), tanggal, dan jenis modal.";
        $tipePesan = "error";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO pengeluaran_bahan (keterangan, nominal, jenis_modal, tanggal) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "siss", $keterangan, $nominal, $jenisModal, $tanggal);

        if (mysqli_stmt_execute($stmt)) {
            if ($jenisModal === 'MODAL_BULANAN') {
                $hariBulan = jumlahHariDalamBulan($tanggal);
                $porsiHarian = hitungPorsiHarianBulanan($nominal, $tanggal);
                $pesan = "Pengeluaran Modal Bulanan tersimpan: $keterangan (" . formatRupiah($nominal) . ") • Setara Modal Harian: " . formatRupiah($porsiHarian) . "/hari (dibagi $hariBulan hari).";
            } else {
                $pesan = "Pengeluaran Modal Harian tersimpan: $keterangan sebesar " . formatRupiah($nominal) . ".";
            }
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
    <title>Pengeluaran Bahan - SiFlow</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <?php if ($pesan): ?>
        <div class="alert alert-<?= $tipePesan ?>"><?= htmlspecialchars($pesan) ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Input Pengeluaran Bahan</h2>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Keterangan Bahan yang Dibeli</label>
                    <input type="text" name="keterangan" id="keterangan" placeholder="Contoh: Tepung terigu, keju, gas, kemasan" required>
                </div>
                <div class="form-group">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" id="tanggal" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Harga / Nominal (Rp)</label>
                    <input type="number" name="nominal" id="nominal" min="1" placeholder="Contoh: 150000" required>
                </div>
                <div class="form-group">
                    <label>Masuk ke Kategori</label>
                    <select name="jenis_modal" id="jenis_modal" required>
                        <option value="MODAL_HARIAN">Modal Harian</option>
                        <option value="MODAL_BULANAN">Modal Bulanan</option>
                    </select>
                </div>
            </div>

            <!-- Info otomatis konversi Modal Bulanan ke Modal Harian -->
            <div id="info-modal-bulanan" style="display: none; background: var(--burgundy-soft); border-left: 4px solid var(--navy); padding: 12px 14px; border-radius: 8px; margin-bottom: 14px; font-size: 0.9rem;">
                <strong>Konversi ke Modal Harian:</strong> <span id="teks-konversi">-</span>
            </div>

            <button type="submit" class="btn">Simpan Pengeluaran</button>
        </form>
    </div>

    <div class="card">
        <h2>Daftar Pengeluaran Bahan</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tanggal</th>
                        <th>Keterangan Bahan</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Modal Harian (Otomatis)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($daftarPengeluaran) === 0): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--teks-muted);">Belum ada data pengeluaran bahan.</td>
                        </tr>
                    <?php else: ?>
                        <?php while ($row = mysqli_fetch_assoc($daftarPengeluaran)): ?>
                            <?php
                                $isBulanan = ($row['jenis_modal'] === 'MODAL_BULANAN');
                                $hariBulan = jumlahHariDalamBulan($row['tanggal']);
                                $modalHarianRow = $isBulanan
                                    ? hitungPorsiHarianBulanan((int)$row['nominal'], $row['tanggal'])
                                    : (int)$row['nominal'];
                            ?>
                            <tr>
                                <td>#<?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['tanggal']) ?></td>
                                <td><?= htmlspecialchars($row['keterangan']) ?></td>
                                <td>
                                    <span class="badge <?= $isBulanan ? 'badge-bulanan' : 'badge-harian' ?>">
                                        <?= $isBulanan ? 'Modal Bulanan' : 'Modal Harian' ?>
                                    </span>
                                </td>
                                <td><strong><?= formatRupiah($row['nominal']) ?></strong></td>
                                <td>
                                    <strong><?= formatRupiah($modalHarianRow) ?></strong>
                                    <?php if ($isBulanan): ?>
                                        <small style="color: var(--teks-muted);"> (<?= formatRupiah($row['nominal']) ?> / <?= $hariBulan ?> hari)</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const selectModal = document.getElementById('jenis_modal');
const inputNominal = document.getElementById('nominal');
const inputTanggal = document.getElementById('tanggal');
const boxInfo = document.getElementById('info-modal-bulanan');
const teksKonversi = document.getElementById('teks-konversi');

function getJumlahHari(tglStr) {
    if (!tglStr) return 30;
    const parts = tglStr.split('-');
    if (parts.length !== 3) return 30;
    const tahun = parseInt(parts[0], 10);
    const bulan = parseInt(parts[1], 10);
    return new Date(tahun, bulan, 0).getDate();
}

function updateKonversiModalBulanan() {
    if (selectModal.value !== 'MODAL_BULANAN') {
        boxInfo.style.display = 'none';
        return;
    }
    boxInfo.style.display = 'block';
    const nominal = parseFloat(inputNominal.value) || 0;
    const hari = getJumlahHari(inputTanggal.value);
    const porsiHarian = hari > 0 ? Math.round(nominal / hari) : 0;
    teksKonversi.textContent =
        "Rp" + porsiHarian.toLocaleString('id-ID') + " / hari (Rp" + nominal.toLocaleString('id-ID') + " ÷ " + hari + " hari)";
}

selectModal.addEventListener('change', updateKonversiModalBulanan);
inputNominal.addEventListener('input', updateKonversiModalBulanan);
inputTanggal.addEventListener('change', updateKonversiModalBulanan);
</script>

</body>
</html>