<?php
session_start();
include '../koneksi.php';

// Cek apakah admin sudah login
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
    
    if (in_array($new_status, ['Dikonfirmasi', 'Ditolak', 'Selesai'])) {
        $query_update = "UPDATE pesanan SET status='$new_status' WHERE id_pesanan='$id_pesanan'";
        
        if (mysqli_query($conn, $query_update)) {
            $pesan = "";
            if ($new_status === 'Dikonfirmasi') {
                $pesan = "Pesanan Anda (ID #$id_pesanan) telah dikonfirmasi dan sedang diproses.";
            } elseif ($new_status === 'Ditolak') {
                $pesan = "Pesanan Anda (ID #$id_pesanan) sayangnya ditolak.";
            } elseif ($new_status === 'Selesai') {
                $pesan = "Pesanan Anda (ID #$id_pesanan) telah selesai diantar. Terima kasih!";
            }

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
// AMBIL DATA PESANAN
// ===========================================
$where_clause = ($current_status !== 'All') ? "WHERE p.status = '$current_status'" : "";

$query_pesanan = "
    SELECT 
        p.id_pesanan, 
        p.id_user, 
        u.nama AS nama_pemesan,
        m.nama_menu,
        p.total,
        p.status,
        DATE(p.tanggal) AS order_date, 
        TIME(p.tanggal) AS order_time, 
        p.metode,
        p.alamat,
        p.catatan
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

// Hitung jumlah untuk Tab
$count_query = mysqli_query($conn, "SELECT status, COUNT(*) as count FROM pesanan GROUP BY status WITH ROLLUP");
$status_counts = ['All' => 0, 'Menunggu' => 0, 'Dikonfirmasi' => 0, 'Ditolak' => 0, 'Selesai' => 0];
while ($row = mysqli_fetch_assoc($count_query)) {
    if ($row['status'] === NULL) {
        $status_counts['All'] = $row['count'];
    } elseif (isset($status_counts[$row['status']])) {
        $status_counts[$row['status']] = $row['count'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Pesanan - Admin ZIFOOD</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --theme-primary: #FF5722;
            --success-color: #4CAF50;
            --pending-color: #ffc107;
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

        /* LAYOUT WRAPPER (Sama seperti Dashboard) */
        .dashboard-wrapper {
            padding: 20px;
            display: flex;
            gap: 20px;
            min-height: 100vh;
        }

        /* SIDEBAR (Sama seperti Dashboard) */
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

        /* KONTEN UTAMA */
        .main-content {
            flex-grow: 1;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 30px;
            overflow-x: auto;
        }

        /* HEADER & TABS */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        h2 {
            margin: 0;
            color: var(--text-dark);
            font-size: 24px;
        }
        .back-link {
            text-decoration: none;
            color: var(--theme-primary);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 10px;
        }

        .tabs {
            display: flex;
            gap: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
            overflow-x: auto;
        }
        .tab-button {
            padding: 8px 16px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            border-radius: 8px;
            transition: all 0.2s;
            white-space: nowrap;
            font-family: inherit;
            font-size: 14px;
        }
        .tab-button:hover {
            background-color: #f9f9f9;
        }
        .tab-button.active {
            color: var(--theme-primary);
            background-color: #ffece6;
            font-weight: 600;
        }

        /* STATUS BADGES */
        .status-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .status-Menunggu { background-color: #fff3cd; color: #856404; }
        .status-Dikonfirmasi { background-color: #d4edda; color: #155724; }
        .status-Ditolak { background-color: #f8d7da; color: #721c24; }
        .status-Selesai { background-color: #cce5ff; color: #004085; }
        
        /* TABLE STYLING */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
            font-size: 14px;
        }
        th {
            background-color: #f9fafb;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        tr:hover { background-color: #fafafa; }
        td strong { display: block; color: var(--text-dark); margin-bottom: 3px; }
        td small { color: #888; font-size: 12px; line-height: 1.4; display: block; }

        /* Aksi Buttons */
        .action-button {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            color: white;
            display: flex; 
            align-items: center;
            justify-content: center;
            gap: 5px;
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 5px;
            transition: opacity 0.2s;
        }
        .action-button:hover { opacity: 0.9; }
        .btn-confirm { background-color: var(--success-color); }
        .btn-reject { background-color: var(--danger-color); }
        .btn-complete { background-color: #007bff; }

    </style>
    <script>
        function setStatus(id, newStatus, idUser, currentTab) {
            if (confirm(`Yakin ingin mengubah status pesanan #${id} menjadi ${newStatus}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `pesanan.php?status=${currentTab}`;

                let inputStatus = document.createElement('input');
                inputStatus.type = 'hidden';
                inputStatus.name = 'status';
                inputStatus.value = newStatus;
                form.appendChild(inputStatus);

                let inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'id_pesanan';
                inputId.value = id;
                form.appendChild(inputId);

                let inputUser = document.createElement('input');
                inputUser.type = 'hidden';
                inputUser.name = 'id_user_for_notif';
                inputUser.value = idUser;
                form.appendChild(inputUser);

                let inputUpdate = document.createElement('input');
                inputUpdate.type = 'hidden';
                inputUpdate.name = 'update_status';
                inputUpdate.value = '1';
                form.appendChild(inputUpdate);

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
        
        <a href="pesanan.php" class="nav-link active" title="Kelola Pesanan"><i class="fas fa-receipt"></i></a>
        
        <a href="logout.php" class="nav-link" onclick="return logoutConfirm()" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>

    <div class="main-content">
        <a href="dashboard.admin.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>

        <div class="header-section">
            <h2>Kelola Pesanan</h2>
        </div>

        <div class="tabs">
            <?php 
            $statuses = ['All', 'Menunggu', 'Dikonfirmasi', 'Ditolak', 'Selesai'];
            foreach ($statuses as $status): 
            ?>
                <button class="tab-button <?= $current_status === $status ? 'active' : ''; ?>" onclick="filterStatus('<?= $status; ?>')">
                    <?= $status; ?> <span style="font-size: 11px; background: #eee; padding: 2px 6px; border-radius: 10px; margin-left: 5px;"><?= $status_counts[$status]; ?></span>
                </button>
            <?php endforeach; ?>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">ID</th>
                    <th style="width: 12%;">Waktu</th>
                    <th style="width: 15%;">Pemesan</th>
                    <th style="width: 20%;">Alamat</th>
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
                        </td>
                        <td>
                            <small><?= htmlspecialchars($row['alamat']); ?></small>
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
                            <span class="status-badge status-<?= $row['status']; ?>"><?= $row['status']; ?></span>
                        </td>
                        <td>
                            <?php if ($row['status'] === 'Menunggu'): ?>
                                <button class="action-button btn-confirm" onclick="setStatus(<?= $row['id_pesanan']; ?>, 'Dikonfirmasi', <?= $row['id_user']; ?>, '<?= $current_status; ?>')">
                                    <i class="fas fa-check"></i> Terima
                                </button>
                                <button class="action-button btn-reject" onclick="setStatus(<?= $row['id_pesanan']; ?>, 'Ditolak', <?= $row['id_user']; ?>, '<?= $current_status; ?>')">
                                    <i class="fas fa-times"></i> Tolak
                                </button>
                            <?php elseif ($row['status'] === 'Dikonfirmasi'): ?>
                                <button class="action-button btn-complete" onclick="setStatus(<?= $row['id_pesanan']; ?>, 'Selesai', <?= $row['id_user']; ?>, '<?= $current_status; ?>')">
                                    <i class="fas fa-check-double"></i> Selesai
                                </button>
                            <?php else: ?>
                                <span style="font-size: 12px; color: #aaa;">Selesai</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #999; padding: 40px;">Tidak ada pesanan dalam status ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>