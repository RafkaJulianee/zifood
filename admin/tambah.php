<?php
session_start();
include '../koneksi.php';

// Cek apakah admin sudah login
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Menu Baru - Admin ZIFOOD</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* --- CSS GLOBAL (Sama dengan Dashboard) --- */
        :root {
            --theme-primary: #FF5722;
            --bg-light: #f4f6f9;
            --text-dark: #1f2937;
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
        }

        /* Layout Wrapper Utama */
        .dashboard-wrapper {
            padding: 20px;
            display: flex;
            gap: 20px;
            min-height: 100vh;
            box-sizing: border-box;
        }

        /* --- SIDEBAR (Sama persis dengan Dashboard) --- */
        .sidebar {
            width: 70px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            padding: 20px 0;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: fit-content;
            min-height: 80vh;
        }
        .nav-link {
            padding: 10px;
            margin: 5px 0;
            color: #9ca3af;
            font-size: 20px;
            transition: color 0.2s, background-color 0.2s;
            border-radius: 8px;
            text-decoration: none;
        }
        .nav-link:hover, .nav-link.active {
            color: var(--theme-primary);
            background-color: #fcebeb;
        }

        /* --- KONTEN FORMULIR (Area Kanan) --- */
        .form-content {
            flex-grow: 1;
            display: flex; /* Flex untuk membagi Form dan Preview */
            gap: 20px;
        }

        /* Bagian Kiri: Input */
        .form-card {
            flex: 2;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .header-action {
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .header-action h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }
        .back-link {
            text-decoration: none;
            color: #999;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 5px;
            transition: color 0.2s;
        }
        .back-link:hover { color: var(--theme-primary); }

        /* Styling Input */
        .input-group {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .form-control {
            flex: 1;
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 5px;
            color: #555;
        }
        input[type="text"],
        input[type="number"],
        input[type="file"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-family: inherit;
            font-size: 14px;
            background-color: #fff;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        .btn-submit {
            background-color: var(--theme-primary);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 10px;
            width: 100%;
            transition: background 0.2s;
        }
        .btn-submit:hover {
            background-color: #e64a19;
        }

        /* Bagian Kanan: Preview */
        .preview-card {
            flex: 1;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
            height: fit-content;
        }
        .preview-card h3 {
            font-size: 16px;
            color: #999;
            margin-top: 0;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .preview-img-placeholder {
            width: 100%;
            height: 200px;
            background-color: #f4f6f9;
            border-radius: 12px;
            margin-bottom: 20px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #999;
            font-size: 13px;
            border: 2px dashed #ddd;
        }
        .menu-info {
            text-align: left;
        }
        #display_nama {
            font-size: 20px;
            font-weight: 700;
            display: block;
            margin-bottom: 5px;
            color: var(--text-dark);
        }
        #display_harga {
            color: var(--theme-primary);
            font-size: 22px;
            font-weight: 700;
            display: block;
            margin-top: 5px;
        }
        #display_deskripsi {
            font-size: 13px;
            color: #666;
            margin-top: 10px;
            display: block;
            line-height: 1.6;
        }
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
        
        <a href="pesanan.php" class="nav-link" title="Kelola Pesanan"><i class="fas fa-receipt"></i></a>
        <a href="logout.php" class="nav-link" onclick="return logoutConfirm()" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>

    <div class="form-content">
        
        <div class="form-card">
            <div class="header-action">
                <a href="menu.php" class="back-link"><i class="fas fa-arrow-left"></i> Daftar Menu</a>
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