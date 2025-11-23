<?php
session_start();
include '../koneksi.php';

// Cek login
if (!isset($_SESSION['id_user'])) {
    header("Location: index.php");
    exit;
}

// Ambil data user yang sedang login
$id_pengguna = $_SESSION['id_user'];
// Menggunakan 'data_pengguna' agar lebih jelas isinya
$data_pengguna = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama FROM users WHERE id_user='$id_pengguna'"));

// ==========================================================
// FIX: LOGIKA MENGHITUNG NOTIFIKASI BELUM DIBACA
// ==========================================================
// Kita pakai awalan 'query_' agar tahu ini adalah teks perintah SQL
$query_notif = "SELECT COUNT(*) AS total FROM notifikasi WHERE id_user='$id_pengguna' AND status_baca='Belum'";
// Kita pakai awalan 'total_' untuk hasil hitungan
$total_notif = mysqli_fetch_assoc(mysqli_query($conn, $query_notif))['total'] ?? 0;
// ==========================================================

// Ambil kategori untuk filter dropdown
$hasil_kategori = mysqli_query($conn, "SELECT DISTINCT kategori FROM menu");

// Variabel untuk pencarian dan filter
$cari_menu = $_GET['cari'] ?? '';
$filter_kategori = $_GET['kategori'] ?? '';

// Query menu berdasarkan pencarian dan filter
// Kita namakan 'query_menu' supaya spesifik, bukan cuma 'query'
$query_menu = "SELECT * FROM menu WHERE 1=1";

if (!empty($cari_menu)) {
    $query_menu .= " AND nama_menu LIKE '" . mysqli_real_escape_string($conn, $cari_menu) . "%'";
}
if (!empty($filter_kategori)) {
    $query_menu .= " AND kategori = '" . mysqli_real_escape_string($conn, $filter_kategori) . "'";
}

$hasil_menu = mysqli_query($conn, $query_menu);


// Menu terpopuler berdasarkan FREKUENSI PEMBELIAN
$query_populer = "
    SELECT 
        m.*, 
        COUNT(p.id_menu) AS total_orders 
    FROM 
        menu m
    JOIN 
        pesanan p ON m.id_menu = p.id_menu
    GROUP BY 
        m.id_menu
    ORDER BY 
        total_orders DESC 
    LIMIT 5
";

$hasil_populer = mysqli_query($conn, $query_populer);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - ZIFOOD</title>
    <link rel="stylesheet" href="CSS/dashboard.css">
    <link rel="shortcut icon" href="img/zifood.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<div class="dashboard-layout">
    
    <div class="sidebar">
        <div class="logo">
            <img src="img/zifood.png" alt="Logo ZIFOOD">
        </div>
        <a href="dashboard.php" class="nav-link active" title="Dashboard"><i class="fas fa-home"></i></a>
        <a href="notifikasi.php" class="nav-link" title="Notifikasi">
            <i class="fas fa-bell"></i>
            <?php if ($total_notif > 0): ?>
                <span class="notification-badge"><?= $total_notif; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="logout.php" class="nav-link" onclick="return konfirmasiLogout()" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>

    <div class="main-content">
        
        <div class="header-search">
            <div class="search-container">
                <i class="fas fa-search"></i>
                <form method="GET" action="" style="display:inline;">
                    <input type="text" name="cari" placeholder="Cari menu..." value="<?= htmlspecialchars($cari_menu); ?>">
                    <button type="submit" style="display:none;"></button>
                </form>
            </div>
            
            <div class="user-info">
                <span>Halo, <strong><?= htmlspecialchars($data_pengguna['nama'] ?? $_SESSION['username']); ?></strong>!</span>
                <div class="user-avatar">
                    <?php
                        // Ambil nama, jika tidak ada, pakai 'U' (User) sebagai default
                        $nama = $data_pengguna['nama'] ?? $_SESSION['username'] ?? 'U';
                        // Ambil huruf pertama dan ubah jadi Kapital
                        echo htmlspecialchars(strtoupper($nama[0]));
                    ?>
                </div>
            </div>
        </div>
        

        <h2>Pilih Kategori</h2>
        <div class="category-list">
             <form method="GET" action="" style="display:contents;">
                 <input type="hidden" name="cari" value="<?= htmlspecialchars($cari_menu); ?>">
                 
                 <button type="submit" name="kategori" value="" class="category-item <?= empty($filter_kategori) ? 'active' : ''; ?>">
                     Semua
                 </button>
                 
                 <?php while ($data_kategori = mysqli_fetch_assoc($hasil_kategori)): ?>
                     <button type="submit" name="kategori" value="<?= $data_kategori['kategori']; ?>" 
                             class="category-item <?= ($filter_kategori == $data_kategori['kategori']) ? 'active' : ''; ?>">
                         <?= htmlspecialchars($data_kategori['kategori']); ?>
                     </button>
                 <?php endwhile; ?>
             </form>
        </div>

        <h2>Daftar Menu</h2>
        <?php if (mysqli_num_rows($hasil_menu) > 0): ?>
            <div class="menu-grid">
                <?php while ($data_menu = mysqli_fetch_assoc($hasil_menu)): ?>
                    <div class="menu-item">
                        <img src="../assets/img/<?= htmlspecialchars($data_menu['foto'] ?? 'default.jpg'); ?>" alt="<?= htmlspecialchars($data_menu['nama_menu']); ?>" class="menu-img">
                        <div class="menu-details">
                            <h4><?= htmlspecialchars($data_menu['nama_menu']); ?></h4>
                            <p>Rp<?= number_format((float)$data_menu['harga'], 0, ',', '.'); ?></p>
                            <span class="menu-rating">⭐ <?= number_format($data_menu['rating_rata'], 1); ?></span>
                            
                            <div class="menu-actions">
                                <form method="GET" action="order.php" style="display: inline-block; flex: 1;">
                                    <input type="hidden" name="id_menu" value="<?= $data_menu['id_menu']; ?>">
                                    <button type="submit" class="action-btn order-btn"><i class="fas fa-receipt"></i> Pesan</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>Tidak ada menu ditemukan di kategori ini.</p>
        <?php endif; ?>

    </div>

    <div class="right-sidebar">
        <div class="popular-section">
            <h3>Menu Paling Laris </h3>
            <?php if (mysqli_num_rows($hasil_populer) > 0): ?>
                <ul class="popular-list">
                    <?php while ($data_populer = mysqli_fetch_assoc($hasil_populer)): ?>
                        <li class="popular-item">
                            <img src="../assets/img/<?= htmlspecialchars($data_populer['foto'] ?? 'default.jpg'); ?>" alt="<?= htmlspecialchars($data_populer['nama_menu']); ?>" class="pop-img">
                            <div class="pop-details">
                                <strong><?= htmlspecialchars($data_populer['nama_menu']); ?></strong>
                                <span class="pop-rating">Total Terjual: <?= $data_populer['total_orders']; ?></span>
                                <div>Rp<?= number_format((float)$data_populer['harga'], 0, ',', '.'); ?></div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>Belum ada data menu populer (terjual).</p>
            <?php endif; ?>
            
           
        </div>
    </div>

</div>

<script>
    function konfirmasiLogout() {
        return confirm("Yakin mau logout?");
    }
</script>
</body>
</html>