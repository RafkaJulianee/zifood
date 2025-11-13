<?php
session_start();
include '../koneksi.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit;
}

// ========== LOGIKA UPDATE (Mengubah Menu) ==========
if (isset($_POST['edit'])) {
    $id = $_POST['id_menu'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama_menu']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    
    // cek foto baru
    $fotoQuery = "";
    if (!empty($_FILES['foto']['name'])) {
        $foto = time() . "_" . $_FILES['foto']['name'];
        move_uploaded_file($_FILES['foto']['tmp_name'], "../assets/img/" . $foto);
        $fotoQuery = ", foto='$foto'";
    }

    // Query UPDATE menu (tanpa rating_rata, karena itu diurus user)
    mysqli_query($conn, "UPDATE menu SET nama_menu='$nama', kategori='$kategori', harga='$harga', deskripsi='$deskripsi' $fotoQuery WHERE id_menu='$id'");
    echo "<script>alert('Menu berhasil diperbarui!'); window.location='menu.php';</script>";
}

// ========== LOGIKA DELETE (Menghapus Menu) ==========
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM menu WHERE id_menu='$id'");
    echo "<script>alert('Menu berhasil dihapus!'); window.location='menu.php';</script>";
}

// Ambil data menu untuk ditampilkan
$menu = mysqli_query($conn, "SELECT * FROM menu ORDER BY id_menu DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Menu - Admin ZIFOOD</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #FF5722;
            --danger-color: #f44336;
            --success-color: #4CAF50;
            --bg-light: #f7f7f7;
            --text-dark: #333;
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            max-width: 95%;
            margin: auto;
        }

        h1 {
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 5px;
        }

        /* --- STYLING BUTTONS --- */
        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            margin-right: 5px;
            text-decoration: none;
        }
        .btn-add {
            background-color: var(--primary-color);
            color: white;
            margin-bottom: 20px;
            display: inline-block;
        }
        .btn-success {
            background-color: var(--success-color);
            color: white;
            padding: 5px 8px;
            font-size: 12px;
        }
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
            padding: 5px 8px;
            font-size: 12px;
            display: inline-block;
        }
        .back-link {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            margin-bottom: 20px;
            display: inline-block;
        }

        /* --- STYLING TABEL --- */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
            vertical-align: top;
            font-size: 14px;
        }
        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .table-image {
            display: block;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        /* --- STYLING INLINE EDIT FORM --- */
        td form {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        td form input[type="text"], 
        td form input[type="number"],
        td form textarea {
            width: 150px;
            padding: 5px;
            margin: 0;
            font-size: 12px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        td form textarea {
            height: 50px;
            resize: vertical;
        }
    </style>
    <script>
        function confirmDelete() {
            return confirm("Yakin mau hapus menu ini?");
        }
    </script>
</head>
<body>
<div class="container">
    <h1>Kelola Menu Makanan</h1>
    <a href="dashboard.admin.php" class="back-link"><i class="fas fa-chevron-left"></i> Kembali ke Dashboard</a>
    <hr>
    
    <a href="tambah_menu.php" class="btn btn-add"><i class="fas fa-plus"></i> Tambah Menu Baru</a>

    <h2>Daftar Menu Saat Ini</h2>

    <table>
        <tr>
            <th>No</th>
            <th>Foto</th>
            <th>Nama Menu</th>
            <th>Kategori</th>
            <th>Harga</th>
            <th>Rating</th>
            <th>Deskripsi</th> 
            <th>Aksi</th>
        </tr>

        <?php
        $no = 1;
        while ($row = mysqli_fetch_assoc($menu)):
        ?>
        <tr>
            <td><?= $no++; ?></td>
            <td>
                <?php if (!empty($row['foto'])): ?>
                    <img src="../assets/img/<?= $row['foto']; ?>" alt="<?= htmlspecialchars($row['nama_menu']); ?>" width="80" class="table-image">
                <?php else: ?>
                    (tidak ada foto)
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($row['nama_menu']); ?></td>
            <td><?= htmlspecialchars($row['kategori']); ?></td>
            <td>Rp<?= number_format($row['harga'], 0, ',', '.'); ?></td>
            <td><?= number_format($row['rating_rata'], 1); ?> ⭐</td> 
            <td><?= htmlspecialchars(substr($row['deskripsi'], 0, 50)); ?>...</td>
            <td>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_menu" value="<?= $row['id_menu']; ?>">
                    
                    <input type="text" name="nama_menu" value="<?= htmlspecialchars($row['nama_menu']); ?>" required>
                    <input type="text" name="kategori" value="<?= htmlspecialchars($row['kategori']); ?>" required>
                    <input type="number" name="harga" value="<?= htmlspecialchars($row['harga']); ?>" required>
                    <textarea name="deskripsi" required><?= htmlspecialchars($row['deskripsi']); ?></textarea>
                    <input type="file" name="foto">
                    
                    <button type="submit" name="edit" class="btn btn-success"><i class="fas fa-save"></i> Simpan</button>
                </form>
                <a href="menu.php?hapus=<?= $row['id_menu']; ?>" onclick="return confirmDelete()" class="btn btn-danger" style="margin-top: 5px;"><i class="fas fa-trash"></i> Hapus</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>