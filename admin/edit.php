<?php
session_start();
include '../koneksi.php';

// 1. Cek Login
if (!isset($_SESSION['id_admin'])) {
    header("Location: ../index.php");
    exit;
}

// 2. Cek ID di URL
if (!isset($_GET['id'])) {
    header("Location: menu.php");
    exit;
}

$id = $_GET['id'];

// 3. Ambil Data Lama
$query = mysqli_query($conn, "SELECT * FROM menu WHERE id_menu = '$id'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    echo "<script>alert('Data tidak ditemukan!'); window.location='menu.php';</script>";
    exit;
}

// 4. Proses Update Data
if (isset($_POST['update'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_menu']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    
    // Logika Foto: Cek jika ada upload baru
    $fotoQuery = ""; 
    if (!empty($_FILES['foto']['name'])) {
        $foto = time() . "_" . $_FILES['foto']['name'];
        $tmp = $_FILES['foto']['tmp_name'];
        move_uploaded_file($tmp, "../assets/img/" . $foto);
        $fotoQuery = ", foto='$foto'"; // Tambahkan ke query update hanya jika ada file baru
    }

    $update = mysqli_query($conn, "UPDATE menu SET nama_menu='$nama', kategori='$kategori', harga='$harga', deskripsi='$deskripsi' $fotoQuery WHERE id_menu='$id'");

    if ($update) {
        echo "<script>alert('Menu berhasil diperbarui!'); window.location='menu.php';</script>";
    } else {
        echo "<script>alert('Gagal mengupdate data!');</script>";
    }
}

// 5. Hitung Badge Notifikasi
$q_badge = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pesanan WHERE status='Menunggu'");
$totalMenunggu = mysqli_fetch_assoc($q_badge)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Menu - Admin ZIFOOD</title>
    <link rel="stylesheet" href="CSS/edit.css">
    <link rel="shortcut icon" href="img/zifood.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
       
    </style>

    <script>
        // Validasi Harga (Sama seperti tambah.php)
        function validateForm() {
            const harga = document.forms["menuForm"]["harga"].value;
            if (isNaN(harga) || harga <= 0) {
                alert("Harga harus angka positif!");
                return false;
            }
            return true;
        }

        function logoutConfirm() { return confirm("Yakin mau logout?"); }

        // --- LOGIKA PREVIEW (Sedikit berbeda agar support data lama) ---
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

            // Helper Format Rupiah
            function formatRupiah(number) {
                const num = Number(number);
                if (isNaN(num) || num === 0) return 'Rp 0';
                return 'Rp ' + num.toLocaleString('id-ID', { minimumFractionDigits: 0 });
            }

            function updateSummary() {
                const valNama = namaInput.value || 'Nama Menu';
                const valKat = kategoriInput.value || 'Kategori';
                const valHarga = hargaInput.value || 0;
                const valDesk = deskripsiInput.value || 'Deskripsi...';

                previewNama.innerHTML = valNama + 
                                        ' <span style="font-weight: 400; font-size: 14px; color: #999;">(' + valKat + ')</span>';
                previewHarga.innerText = formatRupiah(valHarga);
                previewDeskripsi.innerText = valDesk;
            }

            // Fungsi Preview Gambar (Mendukung file baru upload)
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
                }
            }

            // Event Listener
            namaInput.addEventListener('input', updateSummary);
            kategoriInput.addEventListener('input', updateSummary);
            hargaInput.addEventListener('input', updateSummary);
            deskripsiInput.addEventListener('input', updateSummary);
            fotoInput.addEventListener('change', previewImage);

            // INIT: Jalankan sekali saat loading agar preview terisi data database
            // Jika foto dari DB ada, set sebagai background preview
            const fotoLama = "<?= $data['foto']; ?>";
            if (fotoLama && fotoLama !== "") {
                previewImg.style.backgroundImage = "url('../assets/img/" + fotoLama + "')";
                previewImg.innerHTML = '';
                previewImg.style.border = 'none';
            }
            updateSummary(); // Panggil manual agar format rupiah tampil benar di awal
        });
    </script>
</head>
<body>

<div class="dashboard-wrapper">
    
    <div class="sidebar">
        <a href="dashboard.admin.php" class="nav-link"><i class="fas fa-chart-line"></i></a>
        <a href="menu.php" class="nav-link active"><i class="fas fa-utensils"></i></a>
        <a href="tambah.php" class="nav-link"><i class="fas fa-plus"></i></a>
        
        <a href="pesanan.php" class="nav-link">
            <i class="fas fa-receipt"></i>
            <?php if ($totalMenunggu > 0): ?>
                <span class="notification-badge"><?= $totalMenunggu; ?></span>
            <?php endif; ?>
        </a>
        <a href="logout.php" class="nav-link" onclick="return logoutConfirm()"><i class="fas fa-sign-out-alt"></i></a>
    </div>

    <div class="form-content">
        
        <div class="form-card">
            <div class="header-action">
                <h2>Edit Menu Makanan</h2>
                <a href="menu.php" class="btn-cancel"><i class="fas fa-arrow-left"></i> Batal</a>
            </div>

            <form name="menuForm" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
                <input type="hidden" name="id_menu" value="<?= $data['id_menu']; ?>">

                <div class="input-group">
                    <div class="form-control">
                        <label>Nama Menu</label>
                        <input type="text" name="nama_menu" id="nama_menu_input" 
                               value="<?= htmlspecialchars($data['nama_menu']); ?>" required>
                    </div>
                    <div class="form-control">
                        <label>Kategori</label>
                        <input type="text" name="kategori" id="kategori_input" 
                               value="<?= htmlspecialchars($data['kategori']); ?>" required>
                    </div>
                </div>

                <div class="input-group">
                    <div class="form-control">
                        <label>Harga (Rp)</label>
                        <input type="number" name="harga" id="harga_input" 
                               value="<?= htmlspecialchars($data['harga']); ?>" required>
                    </div>
                    <div class="form-control">
                        <label>Ganti Foto (Opsional)</label>
                        <input type="file" name="foto" id="foto_input">
                    </div>
                </div>

                <div class="form-control">
                    <label>Deskripsi Menu</label>
                    <textarea name="deskripsi" id="deskripsi_input" required><?= htmlspecialchars($data['deskripsi']); ?></textarea>
                </div>
                
                <button type="submit" name="update" class="btn-submit">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </form>
        </div>

        <div class="preview-card">
            <h3>Pratinjau User</h3>
            
            <div id="image_preview" class="preview-img-placeholder">
                Foto Belum Ada
            </div>
            
            <div class="menu-info">
                <strong id="display_nama"><?= htmlspecialchars($data['nama_menu']); ?></strong>
                <span id="display_harga">Rp <?= number_format($data['harga'], 0, ',', '.'); ?></span>
                <span id="display_deskripsi"><?= htmlspecialchars($data['deskripsi']); ?></span>
            </div>
        </div>

    </div>
</div>

</body>
</html>