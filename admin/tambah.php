<?php
session_start();
include '../koneksi.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
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
    // Redirect ke halaman list menu (menu.php) setelah berhasil
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
        :root {
            --primary-color: #FF5722;
            --bg-light: #f7f7f7;
            --text-dark: #333;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        .main-wrapper {
            width: 100%;
            min-height: 100vh;
            display: flex;
            background: white;
            border-radius: 0;
            box-shadow: none;
        }
        
        /* KOLOM KIRI: FORMULIR INPUT */
        .form-section {
            flex: 2;
            padding: 40px;
        }
        .form-header {
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .form-header h2 {
            color: var(--text-dark);
            font-size: 24px;
            margin: 0;
        }
        .input-group {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .form-control {
            flex: 1;
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
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        .btn-submit {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 20px;
            width: 100%;
        }
        
        /* KOLOM KANAN: PREVIEW DINAMIS */
        .summary-section {
            flex: 1;
            background-color: var(--bg-light);
            padding: 40px 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .menu-preview {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            width: 90%;
            overflow: hidden;
        }
        .preview-img-placeholder {
            width: 100%;
            height: 150px;
            background-color: #ddd;
            border-radius: 8px;
            margin-bottom: 15px;
            /* Style untuk gambar preview yang di-load oleh JS */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #666;
            font-size: 14px;
        }
        .menu-info {
            text-align: left;
        }
        #display_nama {
            font-size: 18px;
            font-weight: 700;
            display: block;
            margin-bottom: 5px;
        }
        #display_harga {
            color: var(--primary-color);
            font-size: 20px;
            font-weight: 700;
            display: block;
            margin-top: 5px;
        }
        #display_deskripsi {
            font-size: 12px;
            color: #666;
            margin-top: 10px;
            display: block;
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

        // =========================================================
        // JAVASCRIPT UNTUK PREVIEW DINAMIS
        // =========================================================
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

            // FIX: Fungsi formatRupiah diperbarui untuk mengatasi masalah pemisah ribuan
            function formatRupiah(number) {
                const num = Number(number);
                if (isNaN(num) || num === 0) return 'Rp 0';
            
                // Menggunakan toLocaleString dengan opsi eksplisit untuk memastikan format ribuan IDR
                return 'Rp ' + num.toLocaleString('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                });
            }

            function updateSummary() {
                // Update Nama
                previewNama.innerHTML = (namaInput.value || 'Nama Menu') + 
                                        ' <span style="font-weight: 400; font-size: 14px; color: #999;">(' + (kategoriInput.value || 'Kategori') + ')</span>';

                // Update Harga
                const hargaValue = hargaInput.value || 0;
                previewHarga.innerText = formatRupiah(hargaValue);

                // Update Deskripsi
                previewDeskripsi.innerText = (deskripsiInput.value || 'Deskripsi singkat menu akan muncul di sini...');
            }

            function previewImage(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.style.backgroundImage = `url('${e.target.result}')`;
                        previewImg.innerHTML = ''; // Hapus teks placeholder
                    };
                    reader.readAsDataURL(file);
                } else {
                    previewImg.style.backgroundImage = 'none';
                    previewImg.innerHTML = 'Foto Menu';
                }
            }

            // Tambahkan event listeners
            namaInput.addEventListener('input', updateSummary);
            kategoriInput.addEventListener('input', updateSummary);
            hargaInput.addEventListener('input', updateSummary);
            deskripsiInput.addEventListener('input', updateSummary);
            fotoInput.addEventListener('change', previewImage);

            // Inisialisasi tampilan awal
            updateSummary();
        });
    </script>
</head>
<body>

<a href="menu.php" style="color: var(--primary-color); text-decoration: none; font-weight: 600; margin-bottom: 15px; display: block; padding-left: 30px;">← Kembali ke Daftar Menu</a>

<div class="main-wrapper">
    
    <div class="form-section">
        <div class="form-header">
            <h2>Step 01: Input Menu Baru</h2>
            <p style="color: #666; font-size: 14px;">Masukkan detail lengkap menu baru untuk ZIFOOD.</p>
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

    <div class="summary-section">
        <div class="menu-preview">
            <h3>Pratinjau Tampilan User</h3>
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