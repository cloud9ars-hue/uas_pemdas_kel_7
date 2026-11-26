<?php
$halamanAktif = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
    <div class="brand">
        Warung Terang Bulan Mini Ibu Nur
        <span>Jl. Watu Mujur II • Harga Rp5.000/pcs</span>
    </div>
    <div class="nav-links">
        <a href="index.php" class="<?= $halamanAktif == 'index.php' ? 'active' : '' ?>">Rekap Harian</a>
        <a href="transaksi.php" class="<?= $halamanAktif == 'transaksi.php' ? 'active' : '' ?>">Transaksi Penjualan</a>
        <a href="pengeluaran.php" class="<?= $halamanAktif == 'pengeluaran.php' ? 'active' : '' ?>">Pengeluaran Bahan</a>
        <a href="logout.php" class="logout" onclick="return confirm('Yakin ingin keluar?')">Keluar (<?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Admin') ?>)</a>
    </div>
</nav>