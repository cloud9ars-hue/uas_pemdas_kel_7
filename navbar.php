<?php
$halamanAktif = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
    <div class="brand-wrap">
        <span class="brand-name">SiFlow</span>
        <span class="brand-sub">Terang Bulan Mini Ibu Nur • Jl. Watu Mujur II</span>
    </div>
    <div class="nav-links">
        <a href="index.php" class="<?= $halamanAktif == 'index.php' ? 'active' : '' ?>">Rekap Harian</a>
        <a href="transaksi.php" class="<?= $halamanAktif == 'transaksi.php' ? 'active' : '' ?>">Transaksi Penjualan</a>
        <a href="pengeluaran.php" class="<?= $halamanAktif == 'pengeluaran.php' ? 'active' : '' ?>">Pengeluaran Bahan</a>
        <a href="logout.php" class="logout" onclick="return confirm('Yakin ingin keluar dari SiFlow?')">Keluar (<?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User') ?>)</a>
    </div>
</nav>