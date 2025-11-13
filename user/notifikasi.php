<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header("Location: index.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// 1. Logika untuk menandai semua notifikasi SEBAGAI SUDAH DIBACA ketika halaman diakses
mysqli_query($conn, "UPDATE notifikasi SET status_baca='Sudah' WHERE id_user='$id_user' AND status_baca='Belum'");

// 2. Query untuk mengambil semua notifikasi pengguna, diurutkan dari yang terbaru
$notif_query = "
    SELECT 
        n.*,
        p.id_pesanan 
    FROM 
        notifikasi n
    LEFT JOIN
        pesanan p ON n.id_pesanan = p.id_pesanan
    WHERE 
        n.id_user = '$id_user'
    ORDER BY 
        n.waktu DESC
";

$notifikasi_result = mysqli_query($conn, $notif_query);

// Tambahan: Logika untuk menghapus notifikasi (opsional, tapi disiapkan)
if (isset($_GET['hapus'])) {
    $id_notif = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM notifikasi WHERE id_notif='$id_notif' AND id_user='$id_user'");
    header("Location: notifikasi.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Notifikasi - ZIFOOD</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #FF5722;
            --bg-light: #f7f7f7;
            --text-dark: #333;
            --unread-bg: #ffece6;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: white; /* Latar belakang body diubah ke putih */
            margin: 0;
            padding: 0; /* FIX: Menghilangkan padding body */
            min-height: 100vh;
        }
        .notif-container {
            /* FIX: Menggunakan lebar dan tinggi penuh */
            width: 100%;
            min-height: 100vh;
            margin: 0;
            background: white;
            padding: 30px; /* Padding untuk konten di dalam */
            border-radius: 0; /* Menghilangkan border radius */
            box-shadow: none; /* Menghilangkan shadow */
            box-sizing: border-box; 
        }
        .header-content {
            max-width: 800px;
            margin: 0 auto;
        }
        h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }
        
        /* TABS (Meniru Desain) */
        .tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .tab-button {
            padding: 8px 15px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 20px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .tab-button.active {
            background-color: var(--text-dark);
            color: white;
            border-color: var(--text-dark);
        }

        /* DAFTAR NOTIFIKASI */
        .notif-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .notif-item {
            display: flex;
            align-items: flex-start;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            position: relative;
            max-width: 800px; /* Batasi lebar item notifikasi agar tidak terlalu lebar */
            margin: 0 auto; /* Pusatkan item */
        }
        .notif-item:last-child {
            border-bottom: none;
        }
        .notif-item.unread {
            background-color: var(--unread-bg);
            padding: 15px 20px;
            margin: 5px auto;
            border-radius: 8px;
            border: 1px solid rgba(255, 87, 34, 0.2);
        }
        .notif-icon {
            font-size: 20px;
            color: var(--primary-color);
            margin-right: 15px;
            flex-shrink: 0;
        }
        .notif-content {
            flex-grow: 1;
        }
        .notif-time {
            font-size: 11px;
            color: #999;
            margin-top: 5px;
            display: block;
        }
        .delete-btn {
            color: #ccc;
            text-decoration: none;
            margin-left: 10px;
            font-size: 14px;
            transition: color 0.2s;
            flex-shrink: 0;
        }
        .delete-btn:hover {
            color: var(--primary-color);
        }
    </style>
    <script>
        // Fungsi untuk menghitung waktu yang berlalu (e.g., 5 hours ago)
        function timeAgo(dateString) {
            const now = new Date();
            const past = new Date(dateString);
            const seconds = Math.floor((now - past) / 1000);
            
            let interval = seconds / 31536000;
            if (interval > 1) return Math.floor(interval) + " tahun lalu";
            
            interval = seconds / 2592000;
            if (interval > 1) return Math.floor(interval) + " bulan lalu";
            
            interval = seconds / 86400;
            if (interval > 1) return Math.floor(interval) + " hari lalu";
            
            interval = seconds / 3600;
            if (interval > 1) return Math.floor(interval) + " jam lalu";
            
            interval = seconds / 60;
            if (interval > 1) return Math.floor(interval) + " menit lalu";
            
            return Math.floor(seconds) + " detik lalu";
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Memperbarui tampilan waktu setelah DOM dimuat
            document.querySelectorAll('.notif-time').forEach(function(element) {
                const dateString = element.getAttribute('data-time');
                element.innerText = timeAgo(dateString);
            });
        });
    </script>
</head>
<body>

<div class="notif-container">
    <div class="header-content">
        <a href="dashboard.php" style="color: var(--primary-color); text-decoration: none; font-weight: 600;"><i class="fas fa-arrow-left"></i> Dashboard</a>

        <h2>Notifications</h2>

        <div class="tabs">
            <button class="tab-button active">All (<?= mysqli_num_rows($notifikasi_result); ?>)</button>
            </div>
    </div>

    <ul class="notif-list">
        <?php if (mysqli_num_rows($notifikasi_result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($notifikasi_result)): ?>
                <?php 
                    $is_unread = $row['status_baca'] === 'Belum';
                    $icon = 'fa-bell';
                    if (strpos($row['pesan'], 'dikonfirmasi') !== false) {
                        $icon = 'fa-check-circle';
                    } elseif (strpos($row['pesan'], 'ditolak') !== false) {
                        $icon = 'fa-times-circle';
                    } elseif (strpos($row['pesan'], 'selesai') !== false) {
                        $icon = 'fa-truck';
                    }
                ?>
                <li class="notif-item <?= $is_unread ? 'unread' : ''; ?>">
                    <div class="notif-icon">
                        <i class="fas <?= $icon; ?>"></i>
                    </div>
                    <div class="notif-content">
                        <?= htmlspecialchars($row['pesan']); ?>
                        
                        <span class="notif-time" data-time="<?= $row['waktu']; ?>"></span>
                    </div>
                    
                    <a href="notifikasi.php?hapus=<?= $row['id_notif']; ?>" class="delete-btn" title="Hapus Notifikasi">
                        <i class="fas fa-trash"></i>
                    </a>
                </li>
            <?php endwhile; ?>
        <?php else: ?>
            <li class="notif-item" style="justify-content: center;">
                <p style="text-align: center; color: #999; margin: 0;">Tidak ada notifikasi saat ini.</p>
            </li>
        <?php endif; ?>
    </ul>

</div>

</body>
</html>