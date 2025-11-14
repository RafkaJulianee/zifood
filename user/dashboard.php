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
    <link rel="shortcut icon" href="img/zifood.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #FF5722; /* Merah-Oranye Tema Utama */
            --secondary-color: #4CAF50; /* Hijau untuk Aksi */
            --bg-light: #f7f7f7;
            --sidebar-width: 80px;
        }
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            min-height: 100vh;
        }

        /* LAYOUT UTAMA (3 KOLOM) */
        .dashboard-layout {
            display: grid;
            grid-template-columns: var(--sidebar-width) 1fr 300px;
            min-height: 100vh;
            background-color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }

        /* 1. SIDEBAR KIRI (ICON NAV) */
        .sidebar {
            background-color: white;
            border-right: 1px solid #eee;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .logo {
            color: var(--primary-color);
            font-size: 30px;
            margin-bottom: 40px;
        }
        .nav-link {
            display: block;
            padding: 15px 0;
            margin: 5px 0;
            text-align: center;
            color: #999;
            font-size: 20px;
            transition: color 0.2s, background-color 0.2s;
            width: 100%;
            position: relative; /* Penting untuk badge */
            text-decoration: none; /* Menghilangkan garis bawah dari <a> */
        }
        .nav-link:hover, .nav-link.active {
            color: var(--primary-color);
            background-color: #ffece6;
            border-left: 3px solid var(--primary-color);
        }
        
        /* FIX: STYLING UNTUK BADGE NOTIFIKASI */
        .notification-badge {
            position: absolute;
            top: 8px; /* Posisikan di kanan atas ikon */
            right: 15px;
            background-color: red;
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 5px;
            border-radius: 50%;
            line-height: 1;
            min-width: 15px; /* Lebar minimal untuk angka 1 */
            text-align: center;
        }
        /* END FIX: STYLING BADGE */

        /* 2. KONTEN TENGAH (SEARCH & MENU) */
        .main-content {
            padding: 30px;
            overflow-y: auto;
        }
        .header-search {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .search-container {
            position: relative;
            width: 40%;
        }
        .search-container input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
        }
        .search-container i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        .user-info {
            display: flex;
            align-items: center;
            font-size: 14px;
            color: #333;
        }
        .user-info span {
            margin-right: 10px;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            background-color: var(--primary-color); /* Diubah dari #ccc */
            border-radius: 50%;
            /* Properti tambahan untuk inisial */
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 18px;
        }
        
        /* KATEGORI */
        .category-list {
            display: flex;
            gap: 15px;
            margin-bottom: 40px;
            overflow-x: auto;
            padding: 10px 0;
        }
        .category-item {
            text-align: center;
            padding: 10px 15px;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.2s;
            border: 1px solid #f0f0f0;
            /* Pastikan button-like styling */
            background-color: white; 
            font-family: inherit;
            font-size: 14px;
        }
        .category-item.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        .category-item:hover:not(.active) {
            background-color: #f9f9f9;
        }
        .category-icon {
            font-size: 24px;
            margin-bottom: 5px;
        }

        /* MENU GRID */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 25px;
            padding-top: 15px;
        }
        .menu-item {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            text-align: center;
            padding-bottom: 15px;
            border: 1px solid #eee;
        }
        .menu-img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 12px 12px 0 0;
        }
        .menu-details {
            padding: 0 10px;
        }
        .menu-details h4 {
            margin: 10px 0 5px;
            font-weight: 600;
            font-size: 16px;
        }
        .menu-details p {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-color);
        }
        .menu-rating {
            display: block;
            font-size: 14px;
            color: #FFC107;
            margin-bottom: 10px;
        }
        
        .menu-actions {
            display: flex;
            justify-content: space-around;
            gap: 5px;
            margin-top: 15px;
            padding: 0 10px; /* Tambah padding agar tombol tidak mentok */
        }
        .action-btn {
            flex: 1;
            padding: 8px 5px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 13px;
            transition: all 0.2s;
            font-family: inherit; /* Pastikan font konsisten */
        }
        .cart-btn {
            background-color: white;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        .cart-btn:hover {
             background-color: var(--primary-color);
             color: white;
        }
        .order-btn {
            background-color: var(--primary-color);
            color: white;
            border: 1px solid var(--primary-color);
        }


        /* 3. KOLOM KANAN (MENU POPULER) */
        .right-sidebar {
            background-color: var(--bg-light);
            border-left: 1px solid #eee;
            padding: 30px 20px;
            overflow-y: auto;
        }
        .popular-section h3 {
            font-size: 18px;
            font-weight: 600;
            margin-top: 0;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .popular-list {
            list-style: none;
            padding: 0;
        }
        .popular-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #ddd;
        }
        .popular-item:last-child {
            border-bottom: none;
        }
        .pop-img {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 15px;
        }
        .pop-details {
            flex-grow: 1;
        }
        .pop-details strong {
            display: block;
            font-size: 14px;
            font-weight: 600;
        }
        .pop-rating {
            color: var(--primary-color);
            font-size: 12px;
        }
        .right-sidebar .add-btn {
            background-color: var(--primary-color); 
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 20px;
            font-weight: 500;
            font-family: inherit;
        }
    </style>
</head>
<body>

<div class="dashboard-layout">
    
    <div class="sidebar">
        <div class="logo"><i class="fas fa-utensils"></i></div>
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
                    <input type="text" name="cari" placeholder="Search menu..." value="<?= htmlspecialchars($cari); ?>">
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

        <h2>Choose Category</h2>
        <div class="category-list">
             <form method="GET" action="" style="display:contents;">
                 <input type="hidden" name="cari" value="<?= htmlspecialchars($cari); ?>">
                 
                 <button type="submit" name="kategori" value="" class="category-item <?= empty($filter) ? 'active' : ''; ?>">
                     <div class="category-icon"><i class="fas fa-star"></i></div>
                     Semua
                 </button>
                 
                 <?php while ($k = mysqli_fetch_assoc($kategori_result)): ?>
                     <button type="submit" name="kategori" value="<?= $k['kategori']; ?>" 
                             class="category-item <?= ($filter == $k['kategori']) ? 'active' : ''; ?>">
                         <div class="category-icon"><i class="fas fa-utensils"></i></div>
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
                                <form method="POST" action="tambah_keranjang.php" style="display: inline-block; flex: 1;">
                                    <input type="hidden" name="id_menu" value="<?= $row['id_menu']; ?>">
                                    <button type="submit" class="action-btn cart-btn"><i class="fas fa-shopping-basket"></i> Cart</button>
                                </form>
                                
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
            <h3>Menu Paling Laris 🔥</h3>
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
            
            <button class="add-btn" style="width: 100%; margin-top: 20px;" onclick="window.location.href='dashboard.php'">Lihat Semua Menu</button>
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