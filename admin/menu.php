<?php
session_start();
include '../koneksi.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['id_admin'])) {
    header("Location: ../index.php");
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

    // Query UPDATE menu
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

// ==========================================================
// HITUNG BADGE PESANAN (Untuk Sidebar)
// ==========================================================
$q_badge = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pesanan WHERE status='Menunggu'");
$totalMenunggu = mysqli_fetch_assoc($q_badge)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Menu - Admin ZIFOOD</title>
    <link rel="shortcut icon" href="img/zifood.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --theme-primary: #FF5722;
            --success-color: #4CAF50;
            --danger-color: #f44336;
            --bg-light: #f4f6f9;
            --text-dark: #1f2937;
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
        }

        /* --- LAYOUT UTAMA --- */
        .dashboard-wrapper {
            padding: 20px;
            display: flex;
            gap: 20px;
            min-height: 100vh;
            box-sizing: border-box;
        }

        /* --- SIDEBAR --- */
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
            
            /* Update untuk posisi Badge */
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
        }
        .nav-link:hover, .nav-link.active {
            color: var(--theme-primary);
            background-color: #fcebeb;
        }
        
        /* CSS Badge Merah */
        .notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background-color: #ff3b30; /* Merah terang */
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 5px;
            border-radius: 10px;
            min-width: 15px;
            text-align: center;
            line-height: 1;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        /* --- KONTEN UTAMA --- */
        .main-content {
            flex-grow: 1;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 30px;
            overflow-x: auto;
        }

        /* --- HEADER AREA --- */
        .header-action {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        h1 { font-size: 24px; font-weight: 600; margin: 0; }
        
        .btn-add {
            background-color: var(--theme-primary);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
            font-size: 14px;
        }
        .btn-add:hover { background-color: #e64a19; }

        /* --- TABEL --- */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th {
            background-color: #f9fafb;
            color: #6b7280;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-weight: 600;
        }
        td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        tr:hover { background-color: #fafafa; }

        .img-thumb {
            width: 50px; height: 50px; border-radius: 8px; object-fit: cover; border: 1px solid #eee;
        }

        .badge {
            padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; display: inline-block; margin-right: 5px;
        }
        .badge-cat { background-color: #e3f2fd; color: #1565c0; }
        .badge-star { background-color: #fff8e1; color: #f57f17; }
        .price { font-weight: 600; color: var(--theme-primary); }

        /* --- ACTION BUTTONS --- */
        .action-wrapper { display: flex; gap: 5px; }
        .btn-icon {
            width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center;
            border: none; cursor: pointer; transition: all 0.2s; color: white; font-size: 12px;
        }
        .btn-edit { background-color: #FFB74D; } 
        .btn-edit:hover { background-color: #ffa726; }
        .btn-delete { background-color: #ef9a9a; } 
        .btn-delete:hover { background-color: #ef5350; }

        /* --- EDIT MODE --- */
        .edit-mode { display: none; background-color: #fdfdfd; }
        .view-mode { display: table-row; }
        
        .form-inline { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .form-inline input, .form-inline textarea {
            width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; box-sizing: border-box; font-family: inherit;
        }
        .form-inline textarea { grid-column: 1 / span 2; height: 60px; resize: vertical; }
        
        .btn-save { background-color: var(--success-color); color: white; padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; margin-right: 5px; }
        .btn-cancel { background-color: #999; color: white; padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; }
    </style>
    <script>
        function confirmDelete() { return confirm("Yakin mau hapus menu ini?"); }

        function toggleEdit(id) {
            var viewRow = document.getElementById('view-' + id);
            var editRow = document.getElementById('edit-' + id);
            
            if (viewRow.style.display === 'none') {
                viewRow.style.display = 'table-row';
                editRow.style.display = 'none';
            } else {
                viewRow.style.display = 'none';
                editRow.style.display = 'table-row';
            }
        }

        function logoutConfirm() { return confirm("Yakin mau logout dari akun admin?"); }
    </script>
</head>
<body>

<div class="dashboard-wrapper">
    
    <div class="sidebar">
        <a href="dashboard.admin.php" class="nav-link" title="Dashboard"><i class="fas fa-chart-line"></i></a>
        <a href="menu.php" class="nav-link active" title="Kelola Menu"><i class="fas fa-utensils"></i></a>
        <a href="tambah.php" class="nav-link" title="Tambah Menu"><i class="fas fa-plus"></i></a>
        
        <!-- ICON PESANAN DENGAN NOTIFIKASI -->
        <a href="pesanan.php" class="nav-link" title="Kelola Pesanan">
            <i class="fas fa-receipt"></i>
            <?php if ($totalMenunggu > 0): ?>
                <span class="notification-badge"><?= $totalMenunggu; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="logout.php" class="nav-link" onclick="return logoutConfirm()" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>

    <div class="main-content">
        <div class="header-action">
            <div>
                <h1>Daftar Menu Makanan</h1>
            </div>
            <a href="tambah.php" class="btn-add"><i class="fas fa-plus"></i> Tambah Menu Baru</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="10%">Foto</th>
                    <th width="20%">Info Menu</th>
                    <th width="35%">Deskripsi</th>
                    <th width="15%">Harga</th>
                    <th width="15%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                while ($row = mysqli_fetch_assoc($menu)):
                ?>
                
                <tr id="view-<?= $row['id_menu']; ?>" class="view-mode">
                    <td><?= $no++; ?></td>
                    <td>
                        <?php if (!empty($row['foto'])): ?>
                            <img src="../assets/img/<?= $row['foto']; ?>" class="img-thumb">
                        <?php else: ?>
                            <div style="width:50px; height:50px; background:#eee; border-radius:8px;"></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight: 600; margin-bottom: 3px;"><?= htmlspecialchars($row['nama_menu']); ?></div>
                        <span class="badge badge-cat"><?= htmlspecialchars($row['kategori']); ?></span>
                        <span class="badge badge-star">⭐ <?= number_format($row['rating_rata'], 1); ?></span>
                    </td>
                    <td>
                        <div style="font-size: 13px; color: #666; line-height: 1.4;">
                            <?= htmlspecialchars(substr($row['deskripsi'], 0, 80)) . (strlen($row['deskripsi']) > 80 ? '...' : ''); ?>
                        </div>
                    </td>
                    <td class="price">Rp<?= number_format($row['harga'], 0, ',', '.'); ?></td>
                    <td>
                        <div class="action-wrapper">
                            <button onclick="toggleEdit(<?= $row['id_menu']; ?>)" class="btn-icon btn-edit" title="Edit">
                                <i class="fas fa-pen"></i>
                            </button>
                            <a href="menu.php?hapus=<?= $row['id_menu']; ?>" onclick="return confirmDelete()" class="btn-icon btn-delete" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>

                <tr id="edit-<?= $row['id_menu']; ?>" class="edit-mode">
                    <td colspan="6">
                        <form method="POST" enctype="multipart/form-data" style="padding: 15px; background: #fdfdfd; border-radius: 8px;">
                            <input type="hidden" name="id_menu" value="<?= $row['id_menu']; ?>">
                            
                            <div style="display: flex; gap: 20px; align-items: start;">
                                <div style="width: 120px; flex-shrink: 0;">
                                    <small style="display:block; margin-bottom:5px; color: #666;">Ganti Foto</small>
                                    <input type="file" name="foto" style="font-size: 11px; width: 100%;">
                                </div>

                                <div style="flex-grow: 1;">
                                    <div class="form-inline">
                                        <input type="text" name="nama_menu" value="<?= htmlspecialchars($row['nama_menu']); ?>" placeholder="Nama Menu" required>
                                        <input type="text" name="kategori" value="<?= htmlspecialchars($row['kategori']); ?>" placeholder="Kategori" required>
                                        <input type="number" name="harga" value="<?= htmlspecialchars($row['harga']); ?>" placeholder="Harga" required>
                                        <textarea name="deskripsi" placeholder="Deskripsi Menu" required><?= htmlspecialchars($row['deskripsi']); ?></textarea>
                                    </div>
                                    <div style="margin-top: 10px; text-align: right;">
                                        <button type="button" onclick="toggleEdit(<?= $row['id_menu']; ?>)" class="btn-cancel">Batal</button>
                                        <button type="submit" name="edit" class="btn-save"><i class="fas fa-save"></i> Simpan</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </td>
                </tr>

                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>