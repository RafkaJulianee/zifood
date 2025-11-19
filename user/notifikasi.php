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
// 1. LOGIKA SUBMIT RATING BARU
// ==========================================================
if (isset($_POST['submit_rating'])) {
    $id_pesanan_rated = (int)$_POST['id_pesanan_rated'];
    $id_menu_rated = (int)$_POST['id_menu_rated'];
    $nilai = (int)$_POST['rating_value'];

    // Validasi nilai rating (1 sampai 5)
    if ($nilai >= 1 && $nilai <= 5) {
        // A. Insert rating baru ke tabel 'rating'
        // Pastikan Anda sudah membuat tabel 'rating' di database!
        $insert_rating = "INSERT INTO rating (id_user, id_menu, nilai, komentar) 
                          VALUES ('$id_user', '$id_menu_rated', '$nilai', 'Rating via Notifikasi')";
        
        if (mysqli_query($conn, $insert_rating)) {
            
            // B. Hitung ulang rata-rata rating di tabel 'menu'
            $avg_q = "UPDATE menu m 
                      SET rating_rata = (SELECT IFNULL(AVG(nilai), 0) FROM rating r WHERE r.id_menu = m.id_menu) 
                      WHERE m.id_menu = '$id_menu_rated'";
            mysqli_query($conn, $avg_q);
            
            // C. Hapus notifikasi setelah rating diberikan (agar tidak bisa rating 2 kali)
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
// Ambil Notifikasi + Data Menu terkait
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

// Menu Populer (Sidebar Kanan)
$popular_query = "
    SELECT m.*, COUNT(p.id_menu) AS total_orders 
    FROM menu m JOIN pesanan p ON m.id_menu = p.id_menu
    GROUP BY m.id_menu ORDER BY total_orders DESC LIMIT 5
";
$popular = mysqli_query($conn, $popular_query);

// Fungsi helper waktu
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
        /* --- CSS GLOBAL --- */
        :root { --primary-color: #FF5722; --bg-light: #f7f7f7; --sidebar-width: 80px; }
        body { margin: 0; font-family: 'Poppins', sans-serif; background-color: var(--bg-light); min-height: 100vh; }

        .dashboard-layout { display: grid; grid-template-columns: var(--sidebar-width) 1fr 300px; min-height: 100vh; background-color: white; }

        /* SIDEBAR */
        .sidebar { background-color: white; border-right: 1px solid #eee; padding: 20px 0; display: flex; flex-direction: column; align-items: center; }
        .logo { color: var(--primary-color); font-size: 30px; margin-bottom: 40px; }
        .nav-link { display: block; padding: 15px 0; margin: 5px 0; text-align: center; color: #999; font-size: 20px; width: 100%; position: relative; text-decoration: none; }
        .nav-link:hover, .nav-link.active { color: var(--primary-color); background-color: #ffece6; border-left: 3px solid var(--primary-color); }
        .notification-badge { position: absolute; top: 8px; right: 15px; background-color: red; color: white; font-size: 10px; padding: 2px 5px; border-radius: 50%; }

        /* CONTENT */
        .main-content { padding: 30px; overflow-y: auto; background-color: white; }
        .header { border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
        h2 { font-size: 24px; font-weight: 700; margin: 0; color: #333; }

        /* NOTIF LIST */
        .notif-list { list-style: none; padding: 0; margin: 0; max-width: 800px; margin: 0 auto; }
        .notif-item { display: flex; align-items: flex-start; padding: 20px; border: 1px solid #eee; border-radius: 12px; margin-bottom: 15px; background-color: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        
        .notif-img { width: 60px; height: 60px; border-radius: 10px; object-fit: cover; margin-right: 20px; border: 1px solid #f0f0f0; }
        .notif-content { flex-grow: 1; }
        .notif-message { font-size: 15px; font-weight: 500; color: #333; line-height: 1.5; margin-bottom: 5px; }
        .notif-time { font-size: 12px; color: #999; }

        /* RATING BOX */
        .rating-box { 
            background-color: #fff8f5; 
            border: 1px dashed var(--primary-color); 
            padding: 15px; 
            border-radius: 8px; 
            margin-top: 15px; 
            text-align: center;
            animation: fadeIn 0.5s ease;
        }
        @keyframes fadeIn { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }

        .rating-label { display: block; font-weight: 600; font-size: 14px; color: var(--primary-color); margin-bottom: 10px; }
        
        .rating-stars { display: inline-block; margin-bottom: 10px; }
        .star-btn { background: none; border: none; color: #ddd; font-size: 28px; cursor: pointer; transition: 0.2s; padding: 0 5px; }
        .star-btn:hover, .star-btn.rated { color: #ffc107; transform: scale(1.1); }
        
        .btn-rate-submit { 
            background-color: var(--primary-color); color: white; padding: 10px 25px; 
            border: none; border-radius: 25px; font-weight: 600; cursor: pointer; 
            font-size: 14px; transition: 0.2s; box-shadow: 0 4px 10px rgba(255,87,34,0.2);
        }
        .btn-rate-submit:disabled { background-color: #ddd; cursor: not-allowed; box-shadow: none; color: #888; }
        .btn-rate-submit:hover:not(:disabled) { background-color: #e64a19; }

        /* RIGHT SIDEBAR */
        .right-sidebar { background-color: var(--bg-light); border-left: 1px solid #eee; padding: 30px 20px; overflow-y: auto; }
        .popular-item { display: flex; align-items: center; margin-bottom: 15px; border-bottom: 1px dashed #ddd; padding-bottom: 15px; }
        .pop-img { width: 50px; height: 50px; border-radius: 8px; object-fit: cover; margin-right: 10px; }
        .pop-details strong { display: block; font-size: 13px; }
        .pop-rating { font-size: 11px; color: var(--primary-color); }
        .add-btn { display: block; width: 100%; padding: 10px; background: var(--primary-color); color: white; text-align: center; border-radius: 6px; text-decoration: none; margin-top: 20px; font-weight: 500; }
    </style>
</head>
<body>

<div class="dashboard-layout">
    
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo"><i class="fas fa-utensils"></i></div>     
        <a href="dashboard.php" class="nav-link" title="Dashboard"><i class="fas fa-home"></i></a>  
        <a href="notifikasi.php" class="nav-link active" title="Notifikasi">
            <i class="fas fa-bell"></i>
            <?php if ($unread_notif_count > 0): ?>
                <span class="notification-badge"><?= $unread_notif_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="logout.php" class="nav-link" onclick="return confirm('Logout?')" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="header">
            <h2>Notifikasi & Penilaian</h2>
        </div>

        <ul class="notif-list">
            <?php if (mysqli_num_rows($notifikasi_result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($notifikasi_result)): ?>
                    <?php 
                        // PENTING: Deteksi kata 'selesai' agar form rating muncul
                        // Ini akan cocok dengan pesan admin: "Pesanan Anda... telah selesai"
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
                                <!-- FORMULIR RATING BINTANG 5 -->
                                <div class="rating-box" id="rate-container-<?= $row['id_pesanan']; ?>">
                                    <form method="POST" action="">
                                        <!-- Data tersembunyi untuk dikirim -->
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

    <!-- RIGHT SIDEBAR -->
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
    // JAVASCRIPT UNTUK RATING BINTANG
    function selectRating(idPesanan, value) {
        const container = document.getElementById('rate-container-' + idPesanan);
        const stars = container.querySelectorAll('.star-btn');
        const hiddenInput = container.querySelector('.rating-value');
        const submitButton = container.querySelector('.btn-rate-submit');

        // 1. Masukkan nilai bintang ke input hidden
        hiddenInput.value = value;
        
        // 2. Aktifkan tombol submit
        submitButton.disabled = false;
        submitButton.innerHTML = "Kirim " + value + " Bintang";

        // 3. Warnai bintang (Kuning jika <= nilai yang dipilih)
        stars.forEach((star, index) => {
            if (index < value) {
                star.classList.add('rated');
                star.style.color = '#ffc107'; // Emas
            } else {
                star.classList.remove('rated');
                star.style.color = '#ddd'; // Abu-abu
            }
        });
    }
</script>

</body>
</html>