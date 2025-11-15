<?php
session_start();
include '../koneksi.php';

// Cek login
if (!isset($_SESSION['id_user'])) {
    header("Location: index.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// ==========================================================
// MENGHITUNG NOTIFIKASI (DARI DASHBOARD.PHP)
// ==========================================================
$unread_notif_query = "SELECT COUNT(*) AS total FROM notifikasi WHERE id_user='$id_user' AND status_baca='Belum'";
$unread_notif_count = mysqli_fetch_assoc(mysqli_query($conn, $unread_notif_query))['total'] ?? 0;
// ==========================================================


// ==========================================================
// LOGIKA SUBMIT RATING BARU (DARI NOTIFIKASI.PHP)
// ==========================================================
if (isset($_POST['submit_rating'])) {
    $id_pesanan_rated = (int)$_POST['id_pesanan_rated'];
    $id_menu_rated = (int)$_POST['id_menu_rated'];
    $nilai = (int)$_POST['rating_value'];

    // Validasi nilai rating
    if ($nilai >= 1 && $nilai <= 5) {
        // 1. Insert rating baru
        $insert_rating = "INSERT INTO rating (id_user, id_menu, nilai, komentar) 
                          VALUES ('$id_user', '$id_menu_rated', '$nilai', 'Diberikan melalui notifikasi')";
        mysqli_query($conn, $insert_rating);

        // 2. Recalculate average rating
        $avg_q = "UPDATE menu m 
                  SET rating_rata = (SELECT AVG(nilai) FROM rating r WHERE r.id_menu = m.id_menu) 
                  WHERE m.id_menu = '$id_menu_rated'";
        mysqli_query($conn, $avg_q);
        
        // 3. Hapus notifikasi setelah rating diberikan
        $delete_notif = "DELETE FROM notifikasi WHERE id_pesanan='$id_pesanan_rated' AND id_user='$id_user'";
        mysqli_query($conn, $delete_notif);
        
        echo "<script>alert('Terima kasih, rating Anda berhasil disimpan!'); window.location='notifikasi.php';</script>";
        exit;
    }
}

// 1. Tandai semua notifikasi sebagai SUDAH DIBACA ketika halaman diakses
// (Ini dijalankan setelah $unread_notif_count dihitung)
mysqli_query($conn, "UPDATE notifikasi SET status_baca='Sudah' WHERE id_user='$id_user' AND status_baca='Belum'");

// 2. Query untuk mengambil semua notifikasi pengguna
$notif_query = "
    SELECT 
        n.*,
        p.id_menu, 
        m.nama_menu,
        m.foto
    FROM 
        notifikasi n
    JOIN
        pesanan p ON n.id_pesanan = p.id_pesanan
    LEFT JOIN
        menu m ON p.id_menu = m.id_menu
    WHERE 
        n.id_user = '$id_user'
    ORDER BY 
        n.waktu DESC
";
$notifikasi_result = mysqli_query($conn, $notif_query);

// 3. Query untuk Menu Populer (Sidebar Kanan, dari DASHBOARD.PHP)
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


// 4. Logika menghitung waktu yang berlalu
function time_ago($timestamp) {
    $diff = time() - strtotime($timestamp);
    if ($diff < 60) return $diff . " detik lalu";
    if ($diff < 3600) return floor($diff / 60) . " menit lalu";
    if ($diff < 86400) return floor($diff / 3600) . " jam lalu";
    return date('d M Y', strtotime($timestamp));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Notifikasi - ZIFOOD</title>
    <link rel="shortcut icon" href="img/zifood.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* --- CSS GLOBAL DARI DASHBOARD.PHP --- */
        :root {
            --primary-color: #FF5722;
            --secondary-color: #4CAF50;
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
            position: relative;
            text-decoration: none;
        }
        .nav-link:hover, .nav-link.active {
            color: var(--primary-color);
            background-color: #ffece6;
            border-left: 3px solid var(--primary-color);
        }
        
        .notification-badge {
            position: absolute;
            top: 8px;
            right: 15px;
            background-color: red;
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 5px;
            border-radius: 50%;
            line-height: 1;
            min-width: 15px;
            text-align: center;
        }

        /* 2. KONTEN TENGAH (SEARCH & MENU) */
        .main-content {
            padding: 30px;
            overflow-y: auto;
            background-color: white; /* Beda dari dashboard yg #f7f7f7 */
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
        }

        /* --- CSS KHUSUS DARI NOTIFIKASI.PHP --- */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        h2 {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            color: #333;
        }
        
        .notif-list {
            list-style: none;
            padding: 0;
            margin: 0;
            max-width: 800px; /* Jaga agar tidak terlalu lebar */
            margin: 0 auto;
        }
        .notif-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 10px;
            margin-bottom: 10px;
            transition: all 0.2s;
            position: relative;
        }
        .notif-item.unread {
            background-color: #fff5f2; /* Latar oranye muda */
            border-color: var(--primary-color);
        }
        
        .notif-img {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .notif-content {
            flex-grow: 1;
        }
        .notif-message {
            font-size: 14px;
            font-weight: 500;
            color: #333;
            line-height: 1.4;
        }
        .notif-time {
            font-size: 11px;
            color: #999;
            margin-top: 3px;
        }

        /* RATING & ACTION FORM */
        .rating-action {
            padding: 10px 0 0;
            margin-top: 10px;
            border-top: 1px dashed #eee;
        }
        .rating-stars {
            display: inline-block;
            margin-right: 15px;
            font-size: 18px;
        }
        .rating-stars button {
            background: none;
            border: none;
            color: #ddd;
            cursor: pointer;
            transition: color 0.1s;
        }
        .rating-stars button:hover,
        .rating-stars button.rated {
            color: gold;
        }
        .btn-rate-submit {
            background-color: var(--primary-color);
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-rate-submit:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>

<div class="dashboard-layout">
    
    <div class="sidebar">
        <div class="logo"><i class="fas fa-utensils"></i></div>     
        <a href="dashboard.php" class="nav-link" title="Dashboard"><i class="fas fa-home"></i></a>  
        <a href="notifikasi.php" class="nav-link active" title="Notifikasi">
            <i class="fas fa-bell"></i>
            <?php if ($unread_notif_count > 0): ?>
                <span class="notification-badge"><?= $unread_notif_count; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="logout.php" class="nav-link" onclick="return logoutConfirm()" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>

    <div class="main-content">
        
        <div class="header">
            <h2>Notifications</h2>
            </div>

        <ul class="notif-list">
            <?php if (mysqli_num_rows($notifikasi_result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($notifikasi_result)): ?>
                    <?php 
                        // Variabel $is_unread di-set ulang di sini karena query update di atas
                        // Tapi kita bisa gunakan 'status_baca' langsung dari row
                        // $is_unread = $row['status_baca'] === 'Belum'; 
                        
                        $is_completed = strpos($row['pesan'], 'selesai diantar') !== false;
                        $default_img = '../assets/img/default.jpg';
                    ?>
                    
                    <li class="notif-item"> 
                        
                        <img src="../assets/img/<?= htmlspecialchars($row['foto'] ?? 'default.jpg'); ?>" 
                             alt="<?= htmlspecialchars($row['nama_menu'] ?? 'Item'); ?>" class="notif-img">
                        
                        <div class="notif-content">
                            <div class="notif-message">
                                Pesanan <strong>#<?= $row['id_pesanan']; ?></strong> 
                                (<?= htmlspecialchars($row['nama_menu'] ?? 'Item dihapus'); ?>): 
                                <?= htmlspecialchars(str_replace("Pesanan Anda (ID #{$row['id_pesanan']})", "", $row['pesan'])); ?>
                            </div>
                            <span class="notif-time"><?= time_ago($row['waktu']); ?></span>
                            
                            <?php if ($is_completed): ?>
                                <div class="rating-action" id="rate-container-<?= $row['id_pesanan']; ?>">
                                    <form method="POST" action="">
                                        <input type="hidden" name="id_pesanan_rated" value="<?= $row['id_pesanan']; ?>">
                                        <input type="hidden" name="id_menu_rated" value="<?= $row['id_menu']; ?>">
                                        <input type="hidden" name="rating_value" class="rating-value" value="0">
                                        
                                        <span style="font-weight: 600; font-size: 13px; color: #333;">Berikan Rating:</span>
                                        
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <button type="button" class="star-btn" onclick="selectRating(<?= $row['id_pesanan']; ?>, <?= $i; ?>)">
                                                    <i class="fas fa-star"></i>
                                                </button>
                                            <?php endfor; ?>
                                        </div>
                                        
                                        <button type="submit" name="submit_rating" class="btn-rate-submit" disabled>
                                            Konfirmasi & Nilai
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; color: #999;">Tidak ada notifikasi saat ini.</p>
            <?php endif; ?>
        </ul>

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
            
            <a href="dashboard.php" class="add-btn" style="width: 100%; margin-top: 20px; text-align:center; text-decoration:none;">
                Kembali ke Menu
            </a>
        </div>
    </div>

</div>

<script>
    // Fungsi dari notifikasi.php
    function selectRating(idPesanan, value) {
        const container = document.getElementById('rate-container-' + idPesanan);
        const stars = container.querySelectorAll('.star-btn');
        const hiddenInput = container.querySelector('.rating-value');
        const submitButton = container.querySelector('.btn-rate-submit');

        // Set nilai hidden input
        hiddenInput.value = value;
        submitButton.disabled = false;

        // Update visual stars
        stars.forEach((star, index) => {
            if (index < value) {
                star.classList.add('rated');
            } else {
                star.classList.remove('rated');
            }
        });
    }

    // Fungsi dari dashboard.php
    function logoutConfirm() {
        return confirm("Yakin mau logout?");
    }
</script>

</body>
</html>