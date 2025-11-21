<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../index.php");
    exit;
}

// ========== LOGIKA CREATE (Menambahkan Menu Baru) ==========
if (isset($_POST['tambah'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_menu']); 
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $harga = mysqli_real_escape_string($conn, $_POST['harga']); 
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']); 
    
    // Proses upload foto
    $foto = "";
    if (!empty($_FILES['foto']['name'])) {
        $foto = time() . "_" . $_FILES['foto']['name'];
        move_uploaded_file($_FILES['foto']['tmp_name'], "../assets/img/" . $foto);
    }

    // Query INSERT data menu
    $query_insert = "INSERT INTO menu (nama_menu, kategori, harga, deskripsi, foto) 
                     VALUES ('$nama', '$kategori', '$harga', '$deskripsi', '$foto')";
                     
    mysqli_query($conn, $query_insert);
    echo "<script>alert('Menu berhasil ditambahkan!'); window.location='menu.php';</script>";
}

// ==========================================================
// HITUNG NOTIFIKASI PESANAN (Untuk Badge Sidebar)
// ==========================================================
$q_badge = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pesanan WHERE status='Menunggu'");
$totalMenunggu = mysqli_fetch_assoc($q_badge)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Menu Baru - Admin ZIFOOD</title>
    <link rel="stylesheet" href="CSS/tambah.css">
    <link rel="shortcut icon" href="img/zifood.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
      
    </style>
    <script>
        function validateForm() {
            const harga = document.forms["menuForm"]["harga"].value;
            if (isNaN(harga) || harga <= 0) {
                alert("Harga harus angka positif!");
                return false;
            }
            return true;
        }

        // JAVASCRIPT UNTUK PREVIEW DINAMIS
        document.addEventListener('DOMContentLoaded', function() {
            const namaInput = document.getElementById('nama_menu_input');
            const kategoriInput = document.getElementById('kategori_input');
            const hargaInput = document.getElementById('harga_input');
            const deskripsiInput = document.getElementById('deskripsi_input');
            const fotoInput = document.getElementById('foto_input');
            
            const previewNama = document.getElementById('display_nama');
            const previewHarga = document.getElementById('display_harga');
            const previewDeskripsi = document.getElementById('display_deskripsi');
            const previewImg = document.getElementById('image_preview');

            function formatRupiah(number) {
                const num = Number(number);
                if (isNaN(num) || num === 0) return 'Rp 0';
                return 'Rp ' + num.toLocaleString('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                });
            }

            function updateSummary() {
                previewNama.innerHTML = (namaInput.value || 'Nama Menu') + 
                                        ' <span style="font-weight: 400; font-size: 14px; color: #999;">(' + (kategoriInput.value || 'Kategori') + ')</span>';
                
                const hargaValue = hargaInput.value || 0;
                previewHarga.innerText = formatRupiah(hargaValue);
                previewDeskripsi.innerText = (deskripsiInput.value || 'Deskripsi singkat menu akan muncul di sini...');
            }

            function previewImage(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.style.backgroundImage = `url('${e.target.result}')`;
                        previewImg.innerHTML = ''; 
                        previewImg.style.border = 'none';
                    };
                    reader.readAsDataURL(file);
                } else {
                    previewImg.style.backgroundImage = 'none';
                    previewImg.innerHTML = 'Foto Menu';
                    previewImg.style.border = '2px dashed #ddd';
                }
            }

            namaInput.addEventListener('input', updateSummary);
            kategoriInput.addEventListener('input', updateSummary);
            hargaInput.addEventListener('input', updateSummary);
            deskripsiInput.addEventListener('input', updateSummary);
            fotoInput.addEventListener('change', previewImage);

            updateSummary();
        });

        function logoutConfirm() {
            return confirm("Yakin mau logout dari akun admin?");
        }
    </script>
</head>
<body>

<div class="dashboard-wrapper">
    
    <div class="sidebar">
        <a href="dashboard.admin.php" class="nav-link" title="Dashboard"><i class="fas fa-chart-line"></i></a>
        <a href="menu.php" class="nav-link" title="Kelola Menu"><i class="fas fa-utensils"></i></a>
        
        <a href="tambah.php" class="nav-link active" title="Tambah Menu"><i class="fas fa-plus"></i></a>
        
        <!-- LINK PESANAN DENGAN BADGE MERAH -->
        <a href="pesanan.php" class="nav-link" title="Kelola Pesanan">
            <i class="fas fa-receipt"></i>
            <?php if ($totalMenunggu > 0): ?>
                <span class="notification-badge"><?= $totalMenunggu; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="logout.php" class="nav-link" onclick="return logoutConfirm()" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>

    <div class="form-content">
        
        <div class="form-card">
            <div class="header-action">
                <h2>Input Menu Baru</h2>
            </div>

            <form name="menuForm" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
                <div class="input-group">
                    <div class="form-control">
                        <label>Nama Menu</label>
                        <input type="text" name="nama_menu" id="nama_menu_input" placeholder="Contoh: Nasi Goreng Spesial" required>
                    </div>
                    <div class="form-control">
                        <label>Kategori</label>
                        <input type="text" name="kategori" id="kategori_input" placeholder="Contoh: Rice Bowl / Dessert" required>
                    </div>
                </div>

                <div class="input-group">
                    <div class="form-control">
                        <label>Harga (Rp)</label>
                        <input type="number" name="harga" id="harga_input" placeholder="Contoh: 15000" required>
                    </div>
                    <div class="form-control">
                        <label>Foto Menu</label>
                        <input type="file" name="foto" id="foto_input" required>
                    </div>
                </div>

                <div class="form-control">
                    <label>Deskripsi Menu</label>
                    <textarea name="deskripsi" id="deskripsi_input" placeholder="Jelaskan secara singkat menu ini..." required></textarea>
                </div>
                
                <button type="submit" name="tambah" class="btn-submit">
                    <i class="fas fa-plus"></i> Tambah & Simpan Menu
                </button>
            </form>
        </div>

        <div class="preview-card">
            <h3>Pratinjau User</h3>
            <div id="image_preview" class="preview-img-placeholder">Foto Menu</div>
            
            <div class="menu-info">
                <strong id="display_nama">Nama Menu</strong>
                <span id="display_harga">Rp 0</span>
                <span id="display_deskripsi">Deskripsi singkat menu akan muncul di sini...</span>
            </div>
        </div>

    </div>
</div>

</body>
</html>