<?php
session_start();
include '../koneksi.php';

// Cek login
if (!isset($_SESSION['id_user'])) {
    header("Location: index.php");
    exit;
}

// Ambil kategori untuk filter dropdown
$kategori_result = mysqli_query($conn, "SELECT DISTINCT kategori FROM menu");

// Variabel untuk pencarian dan filter
$cari = $_GET['cari'] ?? '';
$filter = $_GET['kategori'] ?? '';

// Query menu berdasarkan pencarian dan filter
$query = "SELECT * FROM menu WHERE 1=1";
if (!empty($cari)) {
    $query .= " AND nama_menu LIKE '%" . mysqli_real_escape_string($conn, $cari) . "%'";
}
if (!empty($filter)) {
    $query .= " AND kategori = '" . mysqli_real_escape_string($conn, $filter) . "'";
}
$menu = mysqli_query($conn, $query);

// Menu terpopuler
$popular = mysqli_query($conn, "SELECT * FROM menu ORDER BY rating_rata DESC LIMIT 3");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - ZIFOOD</title>
    <script>
        function logoutConfirm() {
            return confirm("Yakin mau logout?");
        }
    </script>
</head>
<body>

    <h1>ZIFOOD</h1>
    <h3>Selamat datang, <?= $_SESSION['username']; ?>!</h3>

    <!-- Navbar -->
    <nav>
        <a href="dashboard.php">Dashboard</a> |
        <a href="keranjang.php">Keranjang</a> |
        <a href="order.php">Order</a> |
        <a href="akun_user.php">Akun</a> |
        <a href="notifikasi.php">Notifikasi</a> |
        <a href="logout.php" onclick="return logoutConfirm()">Logout</a>
    </nav>

    <hr>

    <!-- Search & Filter -->
    <form method="GET" action="">
        <input type="text" name="cari" placeholder="Cari makanan..." value="<?= htmlspecialchars($cari); ?>">
        <select name="kategori">
            <option value="">Semua Kategori</option>
            <?php while ($k = mysqli_fetch_assoc($kategori_result)): ?>
                <option value="<?= $k['kategori']; ?>" <?= ($filter == $k['kategori']) ? 'selected' : ''; ?>>
                    <?= $k['kategori']; ?>
                </option>
            <?php endwhile; ?>
        </select>
        <button type="submit">Filter</button>
    </form>

    <hr>
    <h2>Daftar Menu</h2>

    <?php if (mysqli_num_rows($menu) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($menu)): ?>
            <div>
                <h3><?= htmlspecialchars($row['nama_menu']); ?></h3>
                <p>Kategori: <?= htmlspecialchars($row['kategori']); ?></p>
                <p>Harga: Rp<?= number_format($row['harga'], 0, ',', '.'); ?></p>
                <p>Rating: <?= number_format($row['rating_rata'], 1); ?> ⭐</p>

                <form method="POST" action="tambah_keranjang.php">
                    <input type="hidden" name="id_menu" value="<?= $row['id_menu']; ?>">
                    <button type="submit">Tambah ke Keranjang</button>
                </form>

                <form method="GET" action="order.php">
                    <input type="hidden" name="id_menu" value="<?= $row['id_menu']; ?>">
                    <button type="submit">Pesan Sekarang</button>
                </form>
            </div>
            <hr>
        <?php endwhile; ?>
    <?php else: ?>
        <p>Tidak ada menu ditemukan.</p>
    <?php endif; ?>

    <h2>Menu Terpopuler</h2>
    <?php if (mysqli_num_rows($popular) > 0): ?>
        <ul>
            <?php while ($pop = mysqli_fetch_assoc($popular)): ?>
                <li><?= htmlspecialchars($pop['nama_menu']); ?> - ⭐ <?= number_format($pop['rating_rata'], 1); ?></li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>Belum ada menu populer.</p>
    <?php endif; ?>

</body>
</html>
