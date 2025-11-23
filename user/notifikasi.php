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
// 1. LOGIKA SUBMIT RATING BARU (DIPERBARUI)
// ==========================================================
if (isset($_POST['submit_rating'])) {
    $id_pesanan_rated = (int)$_POST['id_pesanan_rated'];
    $id_menu_rated = (int)$_POST['id_menu_rated'];
    $nilai = (int)$_POST['rating_value'];

    // Validasi nilai rating (1 sampai 5)
    if ($nilai >= 1 && $nilai <= 5) {
        
        // A. Insert rating baru ke tabel 'rating'
        // PERUBAHAN: Kolom 'komentar' dan isinya sudah dihapus di sini
        $insert_rating = "INSERT INTO rating (id_user, id_menu, nilai) 
                          VALUES ('$id_user', '$id_menu_rated', '$nilai')";
        
        if (mysqli_query($conn, $insert_rating)) {
            
            // B. Hitung ulang rata-rata rating di tabel 'menu'
            $avg_q = "UPDATE menu m 
                      SET rating_rata = (SELECT IFNULL(AVG(nilai), 0) FROM rating r WHERE r.id_menu = m.id_menu) 
                      WHERE m.id_menu = '$id_menu_rated'";
            mysqli_query($conn, $avg_q);
            
            // C. Hapus notifikasi setelah rating diberikan
            $delete_notif = "DELETE FROM notifikasi WHERE id_pesanan='$id_pesanan_rated' AND id_user='$id_user'";
            mysqli_query($conn, $delete_notif);
            
            echo "<script>alert('Terima kasih! Rating bintang $nilai berhasil disimpan.'); window.location='notifikasi.php';</script>";
            exit;
        } else {
            echo "<script>alert('Gagal menyimpan rating. Error: " . mysqli_error($conn) . "');</script>";
        }
    } else {
        echo "<script>alert('Silakan pilih jumlah bintang terlebih dahulu!'); window.location='notifikasi.php';</script>";
    }
}

// ==========================================================
// 2. TANDAI NOTIFIKASI SUDAH DIBACA
// ==========================================================
mysqli_query($conn, "UPDATE notifikasi SET status_baca='Sudah' WHERE id_user='$id_user' AND status_baca='Belum'");

// Hitung ulang notifikasi belum dibaca untuk Badge Sidebar
$unread_notif_query = "SELECT COUNT(*) AS total FROM notifikasi WHERE id_user='$id_user' AND status_baca='Belum'";
$unread_notif_count = mysqli_fetch_assoc(mysqli_query($conn, $unread_notif_query))['total'] ?? 0;

// ==========================================================
// 3. QUERY DATA NOTIFIKASI & MENU POPULER
// ==========================================================
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

// Menu Populer
$popular_query = "
    SELECT m.*, COUNT(p.id_menu) AS total_orders 
    FROM menu m JOIN pesanan p ON m.id_menu = p.id_menu
    GROUP BY m.id_menu ORDER BY total_orders DESC LIMIT 5
";
$popular = mysqli_query($conn, $popular_query);

function time_ago($timestamp) {
    $diff = time() - strtotime($timestamp);
    if ($diff < 60) return "Baru saja";
    if ($diff < 3600) return floor($diff / 60) . " menit lalu";
    if ($diff < 86400) return floor($diff / 3600) . " jam lalu";
    return date('d M Y H:i', strtotime($timestamp));
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
    
    </style>
</head>
<body>

<div class="dashboard-layout">
       <div class="sidebar">
        <div class="logo">
    <img src="img/zifood.png" alt="Logo ZIFOOD">
</div>    
        <a href="dashboard.php" class="nav-link" title="Dashboard"><i class="fas fa-home"></i></a>  
        <a href="notifikasi.php" class="nav-link active" title="Notifikasi">
            <i class="fas fa-bell"></i>
            <?php if ($unread_notif_count > 0): ?>
                <span class="notification-badge"><?= $unread_notif_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="logout.php" class="nav-link" onclick="return confirm('Logout?')" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>Notifikasi & Penilaian</h2>
        </div>

        <ul class="notif-list">
            <?php if (mysqli_num_rows($notifikasi_result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($notifikasi_result)): ?>
                    <?php 
                        $is_completed = stripos($row['pesan'], 'selesai') !== false;
                    ?>
                    
                    <li class="notif-item"> 
                        <img src="../assets/img/<?= htmlspecialchars($row['foto'] ?? 'default.jpg'); ?>" class="notif-img">
                        
                        <div class="notif-content">
                            <div class="notif-message">
                                <?= htmlspecialchars($row['pesan']); ?>
                            </div>
                            <div class="notif-time"><i class="far fa-clock"></i> <?= time_ago($row['waktu']); ?></div>
                            
                            <?php if ($is_completed): ?>
                                <div class="rating-box" id="rate-container-<?= $row['id_pesanan']; ?>">
                                    <form method="POST" action="">
                                        <input type="hidden" name="id_pesanan_rated" value="<?= $row['id_pesanan']; ?>">
                                        <input type="hidden" name="id_menu_rated" value="<?= $row['id_menu']; ?>">
                                        <input type="hidden" name="rating_value" class="rating-value" value="0">
                                        
                                        <span class="rating-label">Bagaimana rasa menu ini?</span>
                                        
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <button type="button" class="star-btn" onclick="selectRating(<?= $row['id_pesanan']; ?>, <?= $i; ?>)">
                                                    <i class="fas fa-star"></i>
                                                </button>
                                            <?php endfor; ?>
                                        </div>
                                        <br>
                                        <button type="submit" name="submit_rating" class="btn-rate-submit" disabled>
                                            Pilih Bintang Dulu
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 50px; color: #999;">
                    <i class="fas fa-bell-slash" style="font-size: 40px; margin-bottom: 10px;"></i><br>
                    Belum ada notifikasi. Pesan makanan yuk!
                </div>
            <?php endif; ?>
        </ul>
    </div>

    <div class="right-sidebar">
        <h3 style="margin-top: 0; border-bottom: 2px solid var(--primary-color); padding-bottom: 10px;">Menu Terlaris 🔥</h3>
        <?php if (mysqli_num_rows($popular) > 0): ?>
            <ul class="popular-list" style="list-style: none; padding: 0;">
                <?php while ($pop = mysqli_fetch_assoc($popular)): ?>
                    <li class="popular-item">
                        <img src="../assets/img/<?= htmlspecialchars($pop['foto'] ?? 'default.jpg'); ?>" class="pop-img">
                        <div class="pop-details">
                            <strong><?= htmlspecialchars($pop['nama_menu']); ?></strong>
                            <span class="pop-rating">Terjual: <?= $pop['total_orders']; ?></span>
                            <div>Rp<?= number_format((float)$pop['harga'], 0, ',', '.'); ?></div>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php endif; ?>
        <a href="dashboard.php" class="add-btn">Kembali ke Menu</a>
    </div>
</div>

<script>
    function selectRating(idPesanan, value) {
        const container = document.getElementById('rate-container-' + idPesanan);
        const stars = container.querySelectorAll('.star-btn');
        const hiddenInput = container.querySelector('.rating-value');
        const submitButton = container.querySelector('.btn-rate-submit');

        hiddenInput.value = value;
        submitButton.disabled = false;
        submitButton.innerHTML = "Kirim " + value + " Bintang";

        stars.forEach((star, index) => {
            if (index < value) {
                star.classList.add('rated');
                star.style.color = '#ffc107';
            } else {
                star.classList.remove('rated');
                star.style.color = '#ddd';
            }
        });
    }
</script>

</body>
</html>