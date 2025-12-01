<?php
session_start();
include '../koneksi.php';


if (isset($_GET['logout'])) {
    session_destroy();    
    unset($_SESSION);     
    header("Location: ../index.php"); 
    exit;
}

// Cek apakah user sudah login
if (!isset($_SESSION['id_user'])) {
    header("Location: ../index.php");
    exit;
}

$id_pengguna = $_SESSION['id_user'];

// ==========================================================
// 2. LOGIKA KIRIM (SUBMIT) RATING BARU
// ==========================================================
if (isset($_POST['submit_rating'])) {
    // Ambil data dari form (casting ke integer biar aman)
    $id_pesanan_dinilai = (int)$_POST['id_pesanan_rated'];
    $id_menu_dinilai    = (int)$_POST['id_menu_rated'];
    $jumlah_bintang     = (int)$_POST['rating_value'];

    // Validasi: Bintang harus antara 1 sampai 5
    if ($jumlah_bintang >= 1 && $jumlah_bintang <= 5) {
        
        // Siapkan query untuk simpan rating ke tabel 'rating'
        $query_tambah_rating = "INSERT INTO rating (id_user, id_menu, nilai) 
                                VALUES ('$id_pengguna', '$id_menu_dinilai', '$jumlah_bintang')";
        
        // Jalankan query simpan rating
        if (mysqli_query($conn, $query_tambah_rating)) {
            
            // . Update rata-rata rating di tabel 'menu' secara otomatis
            // Kita pakai sub-query SQL biar database yang hitung rata-ratanya
            $query_update_rata2 = "UPDATE menu m 
                                   SET rating_rata = (SELECT IFNULL(AVG(nilai), 0) FROM rating r WHERE r.id_menu = m.id_menu) 
                                   WHERE m.id_menu = '$id_menu_dinilai'";
            mysqli_query($conn, $query_update_rata2);
            
            // Hapus notifikasi karena pesanan sudah selesai dinilai
            $query_hapus_notif = "DELETE FROM notifikasi WHERE id_pesanan='$id_pesanan_dinilai' AND id_user='$id_pengguna'";
            mysqli_query($conn, $query_hapus_notif);
            
            // Redirect (segarkan halaman) setelah berhasil
            echo "<script>alert('Terima kasih! Rating bintang $jumlah_bintang berhasil disimpan.'); window.location='notifikasi.php';</script>";
            exit;

        } else {
            echo "<script>alert('Gagal menyimpan rating. Error: " . mysqli_error($conn) . "');</script>";
        }
    } else {
        echo "<script>alert('Silakan pilih jumlah bintang terlebih dahulu!'); window.location='notifikasi.php';</script>";
    }
}

// ==========================================================
// TANDAI SEMUA NOTIFIKASI JADI "SUDAH DIBACA"
// ==========================================================
// Setiap kali user buka halaman ini, semua notifikasi 'Belum' diubah jadi 'Sudah'
mysqli_query($conn, "UPDATE notifikasi SET status_baca='Sudah' WHERE id_user='$id_pengguna' AND status_baca='Belum'");

// Hitung ulang sisa notifikasi yang belum dibaca (Harusnya jadi 0 setelah kode di atas jalan, tapi buat jaga-jaga)
$query_cek_unread = "SELECT COUNT(*) AS total FROM notifikasi WHERE id_user='$id_pengguna' AND status_baca='Belum'";
$total_belum_baca = mysqli_fetch_assoc(mysqli_query($conn, $query_cek_unread))['total'] ?? 0;

// ==========================================================
// AMBIL DATA NOTIFIKASI & MENU POPULER
// ==========================================================
// Query untuk menampilkan daftar notifikasi user
$query_daftar_notif = "
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
        n.id_user = '$id_pengguna'
    ORDER BY 
        n.waktu DESC
";
$hasil_notifikasi = mysqli_query($conn, $query_daftar_notif);

// Query untuk menu populer (Top 5) di sidebar kanan
$query_populer = "
    SELECT m.*, COUNT(p.id_menu) AS total_orders 
    FROM menu m JOIN pesanan p ON m.id_menu = p.id_menu
    GROUP BY m.id_menu ORDER BY total_orders DESC LIMIT 5
";
$hasil_populer = mysqli_query($conn, $query_populer);

// Fungsi bantu untuk format waktu (Contoh: "5 menit lalu")
function hitung_waktu_lalu($timestamp) {
    $selisih = time() - strtotime($timestamp);
    
    if ($selisih < 60) return "Baru saja";
    if ($selisih < 3600) return floor($selisih / 60) . " menit lalu";
    if ($selisih < 86400) return floor($selisih / 3600) . " jam lalu";
    
    return date('d M Y H:i', strtotime($timestamp));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Notifikasi - ZIFOOD</title>
    <link rel="stylesheet" href="CSS/notifikasi.css">
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
        <a href="dashboard.php" class="nav-link" title="Dashboard"><i class="fas fa-home"></i></a>  
        <a href="notifikasi.php" class="nav-link active" title="Notifikasi">
            <i class="fas fa-bell"></i>
            <?php if ($total_belum_baca > 0): ?>
                <span class="notification-badge"><?= $total_belum_baca; ?></span>
            <?php endif; ?>
        </a>
        <a href="?logout=true" class="nav-link" onclick="return confirm('Yakin mau logout?')" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>Notifikasi & Penilaian</h2>
        </div>

        <ul class="notif-list">
            <?php if (mysqli_num_rows($hasil_notifikasi) > 0): ?>
                <?php while ($data_notif = mysqli_fetch_assoc($hasil_notifikasi)): ?>
                    <?php 
                        // Cek apakah pesan mengandung kata 'selesai' untuk memunculkan tombol rating
                        $status_selesai = stripos($data_notif['pesan'], 'selesai') !== false;
                    ?>
                    
                    <li class="notif-item"> 
                        <img src="../assets/img/<?= htmlspecialchars($data_notif['foto'] ?? 'default.jpg'); ?>" class="notif-img">
                        
                        <div class="notif-content">
                            <div class="notif-message">
                                <?= htmlspecialchars($data_notif['pesan']); ?>
                            </div>
                            <div class="notif-time">
                                <i class="far fa-clock"></i> <?= hitung_waktu_lalu($data_notif['waktu']); ?>
                            </div>
                            
                            <?php if ($status_selesai): ?>
                                <div class="rating-box" id="wadah-rating-<?= $data_notif['id_pesanan']; ?>">
                                    <form method="POST" action="">
                                        <input type="hidden" name="id_pesanan_rated" value="<?= $data_notif['id_pesanan']; ?>">
                                        <input type="hidden" name="id_menu_rated" value="<?= $data_notif['id_menu']; ?>">
                                        <input type="hidden" name="rating_value" class="input-nilai-bintang" value="0">
                                        
                                        <span class="rating-label">Bagaimana rasa menu ini?</span>
                                        
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <button type="button" class="star-btn" onclick="pilihBintang(<?= $data_notif['id_pesanan']; ?>, <?= $i; ?>)">
                                                    <i class="fas fa-star"></i>
                                                </button>
                                            <?php endfor; ?>
                                        </div>
                                        <br>
                                        <button type="submit" name="submit_rating" class="btn-kirim-rating" disabled>
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
        <?php if (mysqli_num_rows($hasil_populer) > 0): ?>
            <ul class="popular-list" style="list-style: none; padding: 0;">
                <?php while ($data_populer = mysqli_fetch_assoc($hasil_populer)): ?>
                    <li class="popular-item">
                        <img src="../assets/img/<?= htmlspecialchars($data_populer['foto'] ?? 'default.jpg'); ?>" class="pop-img">
                        <div class="pop-details">
                            <strong><?= htmlspecialchars($data_populer['nama_menu']); ?></strong>
                            <span class="pop-rating">Terjual: <?= $data_populer['total_orders']; ?></span>
                            <div>Rp<?= number_format((float)$data_populer['harga'], 0, ',', '.'); ?></div>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php endif; ?>
        <a href="dashboard.php" class="add-btn">Kembali ke Menu</a>
    </div>
</div>

<script>
    function pilihBintang(idPesanan, nilai) {
        // Mengambil elemen berdasarkan ID unik per pesanan
        const wadah = document.getElementById('wadah-rating-' + idPesanan);
        const tombolBintang = wadah.querySelectorAll('.star-btn');
        const inputTersembunyi = wadah.querySelector('.input-nilai-bintang');
        const tombolKirim = wadah.querySelector('.btn-kirim-rating');

        // Update nilai pada input hidden
        inputTersembunyi.value = nilai;
        
        // Aktifkan tombol kirim
        tombolKirim.disabled = false;
        tombolKirim.innerHTML = "Kirim " + nilai + " Bintang";

        // Warnai bintang sesuai pilihan
        tombolBintang.forEach((bintang, index) => {
            if (index < nilai) {
                bintang.classList.add('rated');
                bintang.style.color = '#ffc107'; // Kuning emas
            } else {
                bintang.classList.remove('rated');
                bintang.style.color = '#ddd'; // Abu-abu
            }
        });
    }
</script>

</body>
</html>