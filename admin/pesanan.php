<?php
session_start();
include '../koneksi.php';

// ==========================================================
// 1. LOGIKA LOGOUT
// ==========================================================
if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION);
    header("Location: login.php"); 
    exit;
}

// ==========================================================
// 2. CEK LOGIN ADMIN
// ==========================================================
if (!isset($_SESSION['id_admin'])) {
    header("Location: ../index.php");
    exit;
}

// Ambil status filter dari URL
$current_status = $_GET['status'] ?? 'All';

// ===========================================
// LOGIKA UPDATE STATUS & NOTIFIKASI
// ===========================================
if (isset($_POST['update_status'])) {
    $id_pesanan = mysqli_real_escape_string($conn, $_POST['id_pesanan']);
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    $id_user_for_notif = mysqli_real_escape_string($conn, $_POST['id_user_for_notif']);
    
    // Status yang diperbolehkan
    $allowed_statuses = ['Dikonfirmasi', 'Ditolak', 'Selesai'];

    if (in_array($new_status, $allowed_statuses)) {
        
        $pesan = "";
        if ($new_status === 'Dikonfirmasi') {
            $pesan = "Pesanan Anda (ID #$id_pesanan) telah dikonfirmasi dan sedang diproses.";
        } elseif ($new_status === 'Ditolak') {
            $pesan = "Pesanan Anda (ID #$id_pesanan) sayangnya ditolak.";
        } elseif ($new_status === 'Selesai') {
            $pesan = "Pesanan Anda (ID #$id_pesanan) telah selesai. Terima kasih!";
        }

        $query_update = "UPDATE pesanan SET status='$new_status' WHERE id_pesanan='$id_pesanan'";
        
        if (mysqli_query($conn, $query_update)) {
            // INSERT NOTIFIKASI
            if (!empty($pesan)) {
                $query_notif = "INSERT INTO notifikasi (id_user, id_pesanan, pesan, status_baca) 
                                VALUES ('$id_user_for_notif', '$id_pesanan', '$pesan', 'Belum')";
                mysqli_query($conn, $query_notif);
            }
            
            echo "<script>alert('Status pesanan ID $id_pesanan berhasil diubah menjadi $new_status!'); window.location='pesanan.php?status=$current_status';</script>";
        } else {
            echo "<script>alert('Gagal mengubah status: " . mysqli_error($conn) . "');</script>";
        }
    }
}

// ===========================================
// LOGIKA FILTER STATUS (WHERE CLAUSE)
// ===========================================
if ($current_status === 'Dine-in') {
    // Jika tab Dine-in, cari berdasarkan metode bayar
    $where_clause = "WHERE p.metode = 'Bayar di Kasir'"; 
} elseif ($current_status !== 'All') {
    // Jika tab status biasa (Menunggu, Selesai, dll)
    $where_clause = "WHERE p.status = '$current_status'";
} else {
    // All
    $where_clause = "";
}

$query_pesanan = "
    SELECT 
        p.id_pesanan, 
        p.id_user, 
        u.nama AS nama_pemesan,
        u.no_hp, 
        m.nama_menu,
        p.total,
        p.status,
        DATE(p.tanggal) AS order_date, 
        TIME(p.tanggal) AS order_time, 
        p.metode,
        p.alamat,
        p.catatan,
        p.meja AS nomor_meja 
    FROM 
        pesanan p
    JOIN 
        users u ON p.id_user = u.id_user
    LEFT JOIN 
        menu m ON p.id_menu = m.id_menu
    $where_clause
    ORDER BY 
        p.tanggal DESC
";

$result_pesanan = mysqli_query($conn, $query_pesanan);

// ===========================================
// HITUNG JUMLAH (BADGE)
// ===========================================
// 1. Hitung status normal
$count_query = mysqli_query($conn, "SELECT status, COUNT(*) as count FROM pesanan GROUP BY status");
$status_counts = ['All' => 0, 'Menunggu' => 0, 'Dikonfirmasi' => 0, 'Ditolak' => 0, 'Selesai' => 0, 'Dine-in' => 0];

// Total Semua
$total_all = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan"))['total'];
$status_counts['All'] = $total_all;

while ($row = mysqli_fetch_assoc($count_query)) {
    if (isset($status_counts[$row['status']])) {
        $status_counts[$row['status']] = $row['count'];
    }
}

// 2. Hitung khusus Dine-in (Berdasarkan Metode)
$dine_in_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM pesanan WHERE metode = 'Bayar di Kasir'");
$dine_in_count = mysqli_fetch_assoc($dine_in_query)['total'];
$status_counts['Dine-in'] = $dine_in_count;

// Variabel untuk badge merah di sidebar
$totalMenunggu = $status_counts['Menunggu']; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Pesanan - Admin ZIFOOD</title>
    <link rel="stylesheet" href="CSS/pesanan.css">
    <link rel="shortcut icon" href="img/zifood.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
        function setStatus(id, newStatus, idUser, currentTab) {
            let confirmMsg = `Yakin ingin mengubah status pesanan #${id} menjadi ${newStatus}?`;
            if (newStatus === 'Selesai') confirmMsg = `Selesaikan pesanan #${id}?`;

            if (confirm(confirmMsg)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `pesanan.php?status=${currentTab}`;

                const inputs = [
                    {name: 'status', value: newStatus},
                    {name: 'id_pesanan', value: id},
                    {name: 'id_user_for_notif', value: idUser},
                    {name: 'update_status', value: '1'}
                ];

                inputs.forEach(data => {
                    let input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = data.name;
                    input.value = data.value;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
            }
        }

        function filterStatus(status) {
            window.location.href = `pesanan.php?status=${status}`;
        }

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
        <a href="tambah.php" class="nav-link" title="Tambah Menu"><i class="fas fa-plus"></i></a>
        
        <a href="pesanan.php" class="nav-link active" title="Kelola Pesanan">
            <i class="fas fa-receipt"></i>
            <?php if ($totalMenunggu > 0): ?>
                <span class="notification-badge"><?= $totalMenunggu; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="?logout=true" class="nav-link" onclick="return logoutConfirm()" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>

    <div class="main-content">
        
        <div class="header-section">
            <h2>Kelola Pesanan</h2>
        </div>

        <div class="tabs">
            <?php 
            $statuses = ['All', 'Menunggu', 'Dikonfirmasi', 'Dine-in', 'Ditolak', 'Selesai'];
            foreach ($statuses as $status): 
            ?>
                <button class="tab-button <?= $current_status === $status ? 'active' : ''; ?>" onclick="filterStatus('<?= $status; ?>')">
                    <?= $status; ?> 
                    <span style="font-size: 11px; background: #eee; padding: 2px 6px; border-radius: 10px; margin-left: 5px;">
                        <?= $status_counts[$status] ?? 0; ?>
                    </span>
                </button>
            <?php endforeach; ?>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">ID</th>
                    <th style="width: 12%;">Waktu</th>
                    <th style="width: 15%;">Pemesan</th>
                    <th style="width: 20%;">Alamat / Meja</th> 
                    <th style="width: 15%;">Catatan</th>
                    <th style="width: 18%;">Detail Item</th>
                    <th style="width: 5%;">Status</th>
                    <th style="width: 10%;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result_pesanan) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result_pesanan)): ?>
                    <tr>
                        <td>#<?= $row['id_pesanan']; ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['order_date']); ?></strong>
                            <small><?= htmlspecialchars($row['order_time']); ?></small>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($row['nama_pemesan']); ?></strong>
                            <small>ID: <?= $row['id_user']; ?></small>
                            
                            <?php if ($row['metode'] === 'Bayar di Tempat (COD)' && !empty($row['no_hp'])): ?>
                                <?php
                                    // Format nomor HP agar diawali '62'
                                    $nomor_wa = $row['no_hp'];
                                    if (substr($nomor_wa, 0, 1) == '0') {
                                        $nomor_wa = '62' . substr($nomor_wa, 1);
                                    }
                                ?>
                                <a href="https://wa.me/<?= $nomor_wa; ?>" target="_blank" title="Chat via WhatsApp" 
                                   style="color: var(--success-color); font-weight: 500; margin-top: 5px; display: inline-block; text-decoration: none;">
                                    <i class="fab fa-whatsapp"></i> <?= htmlspecialchars($row['no_hp']); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small><?= htmlspecialchars($row['alamat']); ?></small>
                            
                            <?php if ($row['metode'] === 'Bayar di Kasir'): ?>
                                <strong style="color: var(--theme-primary); margin-top: 5px; font-size: 14px; display:block;">
                                    <i class="fas fa-chair"></i> Meja: <?= htmlspecialchars($row['nomor_meja']); ?>
                                </strong>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small><?= empty($row['catatan']) ? '-' : htmlspecialchars($row['catatan']); ?></small>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($row['nama_menu'] ?? 'Menu Dihapus'); ?></strong>
                            <small style="color: var(--theme-primary); font-weight: 600;">Rp<?= number_format($row['total'], 0, ',', '.'); ?></small>
                            <small><?= $row['metode']; ?></small>
                        </td>
                        <td>
                            <?php 
                                if ($row['metode'] === 'Bayar di Kasir' && $row['status'] === 'Menunggu') {
                                    echo '<span class="status-badge status-Dine-in">Dine-in</span>';
                                } else {
                                    echo '<span class="status-badge status-'.$row['status'].'">'.$row['status'].'</span>';
                                }
                            ?>
                        </td>
                        <td>
                            <?php 
                            // TOMBOL AKSI BERDASARKAN STATUS & METODE
                            
                            // 1. Dine-in & Menunggu -> Tombol Selesai
                            if ($row['metode'] === 'Bayar di Kasir' && $row['status'] === 'Menunggu'): 
                            ?>
                                <button class="action-button btn-complete" onclick="setStatus(<?= $row['id_pesanan']; ?>, 'Selesai', <?= $row['id_user']; ?>, '<?= $current_status; ?>')">
                                    <i class="fas fa-check-double"></i> Selesai
                                </button>

                            <?php 
                            // 2. Online & Menunggu -> Terima / Tolak
                            elseif ($row['status'] === 'Menunggu'): 
                            ?>
                                <button class="action-button btn-confirm" onclick="setStatus(<?= $row['id_pesanan']; ?>, 'Dikonfirmasi', <?= $row['id_user']; ?>, '<?= $current_status; ?>')">
                                    <i class="fas fa-check"></i> Terima
                                </button>
                                <button class="action-button btn-reject" onclick="setStatus(<?= $row['id_pesanan']; ?>, 'Ditolak', <?= $row['id_user']; ?>, '<?= $current_status; ?>')">
                                    <i class="fas fa-times"></i> Tolak
                                </button>
                            
                            <?php 
                            // 3. Online & Dikonfirmasi -> Selesai
                            elseif ($row['status'] === 'Dikonfirmasi'): 
                            ?>
                                <button class="action-button btn-complete" onclick="setStatus(<?= $row['id_pesanan']; ?>, 'Selesai', <?= $row['id_user']; ?>, '<?= $current_status; ?>')">
                                    <i class="fas fa-check-double"></i> Selesai
                                </button>
                            
                            <?php else: ?>
                                <span style="font-size: 12px; color: #aaa;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #999; padding: 40px;">Tidak ada pesanan dalam kategori ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>