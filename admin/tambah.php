<?php
session_start();
include '../koneksi.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit;
}

// ========== CREATE (Menambahkan Menu Baru) ==========
if (isset($_POST['tambah'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_menu']); 
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']); 
    $harga = mysqli_real_escape_string($conn, $_POST['harga']); 
    // Rating tidak diambil dari form, rating_rata akan otomatis 0 (DEFAULT di DB)
    
    // Proses upload foto
    $foto = "";
    if (!empty($_FILES['foto']['name'])) {
        $foto = time() . "_" . $_FILES['foto']['name'];
        // PENTING: Pastikan folder ../assets/img/ ada dan dapat ditulis (writable)
        move_uploaded_file($_FILES['foto']['tmp_name'], "../assets/img/" . $foto);
    }

    // Query INSERT data menu (Kolom rating_rata dihilangkan, menggunakan nilai default 0)
    $query_insert = "INSERT INTO menu (nama_menu, kategori, harga, foto) 
                     VALUES ('$nama', '$kategori', '$harga', '$foto')";
                     
    mysqli_query($conn, $query_insert);
    // Menu akan langsung terlihat di dashboard user karena data diambil dari tabel menu
    echo "<script>alert('Menu berhasil ditambahkan dan sudah tayang di web user!'); window.location='menu.php';</script>";
}

// ========== UPDATE ==========
if (isset($_POST['edit'])) {
    $id = $_POST['id_menu'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama_menu']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);
    // Rating tidak diupdate oleh admin

    // cek foto baru
    $fotoQuery = "";
    if (!empty($_FILES['foto']['name'])) {
        $foto = time() . "_" . $_FILES['foto']['name'];
        move_uploaded_file($_FILES['foto']['tmp_name'], "../assets/img/" . $foto);
        $fotoQuery = ", foto='$foto'";
    }

    // Query UPDATE tanpa kolom rating_rata
    mysqli_query($conn, "UPDATE menu SET nama_menu='$nama', kategori='$kategori', harga='$harga' $fotoQuery WHERE id_menu='$id'");
    echo "<script>alert('Menu berhasil diperbarui!'); window.location='menu.php';</script>";
}

// ========== DELETE ==========
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
    <script>
        function confirmDelete() {
            return confirm("Yakin mau hapus menu ini?");
        }

        function validateForm() {
            const harga = document.forms["menuForm"]["harga"].value;
            if (isNaN(harga) || harga <= 0) {
                alert("Harga harus angka positif!");
                return false;
            }
            // Validasi rating dihilangkan
            return true;
        }
    </script>
</head>
<body>
    <h1>Kelola Menu</h1>
    <a href="dashboard.admin.php">Kembali ke Dashboard</a>
    <hr>

    <h2>Tambah Menu Baru</h2>
    <form name="menuForm" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
        <label>Nama Menu:</label><br>
        <input type="text" name="nama_menu" required><br><br>

        <label>Kategori:</label><br>
        <input type="text" name="kategori" required><br><br>

        <label>Harga:</label><br>
        <input type="number" name="harga" required><br><br>

        <label>Foto:</label><br>
        <input type="file" name="foto"><br><br>
        
        <button type="submit" name="tambah">Tambah Menu</button>
    </form>

    <hr>
    <h2>Daftar Menu Saat Ini</h2>

    <table border="1" cellpadding="8" cellspacing="0">
        <tr>
            <th>No</th>
            <th>Foto</th>
            <th>Nama Menu</th>
            <th>Kategori</th>
            <th>Harga</th>
            <th>Rating</th>
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
                    <img src="../assets/img/<?= $row['foto']; ?>" width="80">
                <?php else: ?>
                    (tidak ada foto)
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($row['nama_menu']); ?></td>
            <td><?= htmlspecialchars($row['kategori']); ?></td>
            <td>Rp<?= number_format($row['harga'], 0, ',', '.'); ?></td>
            <td><?= number_format($row['rating_rata'], 1); ?> ⭐</td> 
            <td>
                <form method="POST" enctype="multipart/form-data" style="display:inline;">
                    <input type="hidden" name="id_menu" value="<?= $row['id_menu']; ?>">
                    <input type="text" name="nama_menu" value="<?= $row['nama_menu']; ?>" required>
                    <input type="text" name="kategori" value="<?= $row['kategori']; ?>" required>
                    <input type="number" name="harga" value="<?= $row['harga']; ?>" required>
                    <input type="file" name="foto">
                    <button type="submit" name="edit">Simpan</button>
                </form>
                <a href="menu.php?hapus=<?= $row['id_menu']; ?>" onclick="return confirmDelete()">Hapus</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

</body>
</html>