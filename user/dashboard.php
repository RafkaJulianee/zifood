<?php
session_start();
include '../koneksi.php';

// Cek login
if (!isset($_SESSION['id_user'])) {
    header("Location: index.php");
    exit;
}

// Ambil data user yang sedang login
$user_id = $_SESSION['id_user'];
$user_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama FROM users WHERE id_user='$user_id'"));

// ==========================================================
// FIX: LOGIKA MENGHITUNG NOTIFIKASI BELUM DIBACA
// ==========================================================
$unread_notif_query = "SELECT COUNT(*) AS total FROM notifikasi WHERE id_user='$user_id' AND status_baca='Belum'";
$unread_notif_count = mysqli_fetch_assoc(mysqli_query($conn, $unread_notif_query))['total'] ?? 0;
// ==========================================================

// Ambil kategori untuk filter dropdown
$kategori_result = mysqli_query($conn, "SELECT DISTINCT kategori FROM menu");

// Variabel untuk pencarian dan filter
$cari = $_GET['cari'] ?? '';
$filter = $_GET['kategori'] ?? '';

// Query menu berdasarkan pencarian dan filter
$query = "SELECT * FROM menu WHERE 1=1";
if (!empty($cari)) {
    $query .= " AND nama_menu LIKE '" . mysqli_real_escape_string($conn, $cari) . "%'";
}
if (!empty($filter)) {
    $query .= " AND kategori = '" . mysqli_real_escape_string($conn, $filter) . "'";
}
$menu = mysqli_query($conn, $query);


// Menu terpopuler berdasarkan FREKUENSI PEMBELIAN
$popular_query = "
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

$popular = mysqli_query($conn, $popular_query);
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
    <style>
       
    </style>
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
            <?php if ($unread_notif_count > 0): ?>
                <span class="notification-badge"><?= $unread_notif_count; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="logout.php" class="nav-link" onclick="return logoutConfirm()" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>

    <div class="main-content">
        
        <div class="header-search">
            <div class="search-container">
                <i class="fas fa-search"></i>
                <form method="GET" action="" style="display:inline;">
                    <input type="text" name="cari" placeholder="Cari menu..." value="<?= htmlspecialchars($cari); ?>">
                    <button type="submit" style="display:none;"></button>
                </form>
            </div>
            
            <div class="user-info">
                <span>Halo, <strong><?= htmlspecialchars($user_data['nama'] ?? $_SESSION['username']); ?></strong>!</span>
                <div class="user-avatar">
                    <?php
                        // Ambil nama, jika tidak ada, pakai 'U' (User) sebagai default
                        $nama = $user_data['nama'] ?? $_SESSION['username'] ?? 'U';
                        // Ambil huruf pertama dan ubah jadi Kapital
                        echo htmlspecialchars(strtoupper($nama[0]));
                    ?>
                </div>
            </div>
        </div>
        

        <h2>Pilih Kategori</h2>
        <div class="category-list">
             <form method="GET" action="" style="display:contents;">
                 <input type="hidden" name="cari" value="<?= htmlspecialchars($cari); ?>">
                 
                 <button type="submit" name="kategori" value="" class="category-item <?= empty($filter) ? 'active' : ''; ?>">
                     Semua
                 </button>
                 
                 <?php while ($k = mysqli_fetch_assoc($kategori_result)): ?>
                     <button type="submit" name="kategori" value="<?= $k['kategori']; ?>" 
                             class="category-item <?= ($filter == $k['kategori']) ? 'active' : ''; ?>">
                         <?= htmlspecialchars($k['kategori']); ?>
                     </button>
                 <?php endwhile; ?>
             </form>
        </div>

        <h2>Daftar Menu</h2>
        <?php if (mysqli_num_rows($menu) > 0): ?>
            <div class="menu-grid">
                <?php while ($row = mysqli_fetch_assoc($menu)): ?>
                    <div class="menu-item">
                        <img src="../assets/img/<?= htmlspecialchars($row['foto'] ?? 'default.jpg'); ?>" alt="<?= htmlspecialchars($row['nama_menu']); ?>" class="menu-img">
                        <div class="menu-details">
                            <h4><?= htmlspecialchars($row['nama_menu']); ?></h4>
                            <p>Rp<?= number_format((float)$row['harga'], 0, ',', '.'); ?></p>
                            <span class="menu-rating">⭐ <?= number_format($row['rating_rata'], 1); ?></span>
                            
                            <div class="menu-actions">
                                <form method="GET" action="order.php" style="display: inline-block; flex: 1;">
                                    <input type="hidden" name="id_menu" value="<?= $row['id_menu']; ?>">
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
            <?php if (mysqli_num_rows($popular) > 0): ?>
                <ul class="popular-list">
                    <?php while ($pop = mysqli_fetch_assoc($popular)): ?>
                        <li class="popular-item">
                            <img src="../assets/img/<?= htmlspecialchars($pop['foto'] ?? 'default.jpg'); ?>" alt="<?= htmlspecialchars($pop['nama_menu']); ?>" class="pop-img">
                            <div class="pop-details">
                                <strong><?= htmlspecialchars($pop['nama_menu']); ?></strong>
                                <span class="pop-rating">Total Terjual: <?= $pop['total_orders']; ?></span>
                                <div>Rp<?= number_format((float)$pop['harga'], 0, ',', '.'); ?></div>
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
    function logoutConfirm() {
        return confirm("Yakin mau logout?");
    }
</script>
</body>
</html>