<?php
session_start();
include '../koneksi.php';


if (isset($_GET['logout'])) {
    session_destroy();    
    unset($_SESSION);    
  
    header("Location: ../index.php"); 
    exit;
}


if (!isset($_SESSION['id_admin'])) {
    header("Location: ../index.php");
    exit;
}

// ==========================================================
// 3. LOGIKA DELETE
// ==========================================================
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    


    $delete = mysqli_query($conn, "DELETE FROM menu WHERE id_menu='$id'");
    
    if ($delete) {
        echo "<script>alert('Menu berhasil dihapus!'); window.location='menu.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus menu!'); window.location='menu.php';</script>";
    }
}

// Ambil data menu untuk ditampilkan
$menu = mysqli_query($conn, "SELECT * FROM menu ORDER BY id_menu DESC");

// ==========================================================
// 4. HITUNG BADGE PESANAN (Untuk Sidebar)
// ==========================================================
$q_badge = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pesanan WHERE status='Menunggu'");
$totalMenunggu = mysqli_fetch_assoc($q_badge)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Menu - Admin ZIFOOD</title>
    <link rel="stylesheet" href="CSS/menu.css">
    <link rel="shortcut icon" href="img/zifood.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        

    </style>
    <script>
        function confirmDelete() { return confirm("Yakin mau hapus menu ini?"); }
        function logoutConfirm() { return confirm("Yakin mau logout dari akun admin?"); }
    </script>
</head>
<body>

<div class="dashboard-wrapper">
    
    <div class="sidebar">
        <a href="dashboard.admin.php" class="nav-link" title="Dashboard"><i class="fas fa-chart-line"></i></a>
        <a href="menu.php" class="nav-link active" title="Kelola Menu"><i class="fas fa-utensils"></i></a>
        <a href="tambah.php" class="nav-link" title="Tambah Menu"><i class="fas fa-plus"></i></a>
        
        <a href="pesanan.php" class="nav-link" title="Kelola Pesanan">
            <i class="fas fa-receipt"></i>
            <?php if ($totalMenunggu > 0): ?>
                <span class="notification-badge"><?= $totalMenunggu; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="?logout=true" class="nav-link" onclick="return logoutConfirm()" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
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
                
                <tr>
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
                        <?php if(isset($row['rating_rata'])): ?>
                            <span class="badge badge-star">⭐ <?= number_format($row['rating_rata'], 1); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-size: 13px; color: #666; line-height: 1.4;">
                            <?= htmlspecialchars(substr($row['deskripsi'], 0, 80)) . (strlen($row['deskripsi']) > 80 ? '...' : ''); ?>
                        </div>
                    </td>
                    <td class="price">Rp<?= number_format($row['harga'], 0, ',', '.'); ?></td>
                    <td>
                        <div class="action-wrapper">
                            <a href="edit.php?id=<?= $row['id_menu']; ?>" class="btn-icon btn-edit" title="Edit">
                                <i class="fas fa-pen"></i>
                            </a>
                            
                            <a href="menu.php?hapus=<?= $row['id_menu']; ?>" onclick="return confirmDelete()" class="btn-icon btn-delete" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>