<?php
session_start();
include '../koneksi.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
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

            // INSERT NOTIFIKASI ke tabel notifikasi
            if (!empty($pesan)) {
                $query_notif = "INSERT INTO notifikasi (id_user, id_pesanan, pesan, status_baca) 
                                VALUES ('$id_user_for_notif', '$id_pesanan', '$pesan', 'Belum')";
                mysqli_query($conn, $query_notif);
            }
            
            // Redirect kembali ke tab status saat ini
            echo "<script>alert('Status pesanan ID $id_pesanan berhasil diubah menjadi $new_status!'); window.location='pesanan.php?status=$current_status';</script>";
        } else {
            echo "<script>alert('Gagal mengubah status: " . mysqli_error($conn) . "');</script>";
        }
    }
}

// ===========================================
// AMBIL DATA PESANAN DARI DATABASE (Dinamis berdasarkan status)
// ===========================================
$where_clause = ($current_status !== 'All') ? "WHERE p.status = '$current_status'" : "";

$query_pesanan = "
    SELECT 
        p.id_pesanan, 
        p.id_user, /* Diambil untuk notifikasi */
        u.nama AS nama_pemesan,
        m.nama_menu,
        p.total,
        p.status,
        DATE(p.tanggal) AS order_date, /* Ambil hanya tanggal */
        TIME(p.tanggal) AS order_time, /* Ambil waktu */
        p.metode,
        p.alamat
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

// Ambil jumlah total pesanan per status untuk tab
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
            --primary-color: #FF5722;
            --success-color: #4CAF50;
            --pending-color: #ffc107; /* Kuning */
            --danger-color: #f44336;
            --bg-light: #f7f7f7;
            --text-dark: #333;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            padding: 30px;
            margin: 0;
        }
        
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            max-width: 95%;
            margin: auto;
        }

        /* HEADER & TABS */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .tabs {
            display: flex;
            gap: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 20px;
        }
        .tab-button {
            padding: 10px 15px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            border-radius: 8px;
            transition: color 0.2s, border-bottom 0.2s;
        }
        .tab-button.active {
            color: var(--primary-color);
            border-bottom: 3px solid var(--primary-color);
        }

        /* STATUS BADGES */
        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-Menunggu {
            background-color: #fff3cd;
            color: var(--pending-color);
        }
        .status-Dikonfirmasi {
            background-color: #d4edda;
            color: var(--success-color);
        }
        .status-Ditolak {
            background-color: #f8d7da;
            color: var(--danger-color);
        }
        .status-Selesai {
            background-color: #cce5ff;
            color: #004085; /* Biru tua */
        }
        
        /* TABLE STYLING */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background-color: var(--bg-light);
            color: var(--text-dark);
            font-weight: 600;
            font-size: 14px;
        }
        td strong {
            display: block;
            font-size: 14px;
        }
        td small {
            color: #999;
            font-size: 11px;
        }

        /* Aksi Buttons */
        .action-button {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            margin-right: 5px;
            color: white;
        }
        .btn-confirm { background-color: var(--success-color); }
        .btn-reject { background-color: var(--danger-color); }
        .btn-complete { background-color: #007bff; }

        .back-link {
            text-decoration: none;
            color: var(--primary-color);
            font-weight: 600;
        }
    </style>
    <script>
        function setStatus(id, newStatus, idUser, currentTab) {
            if (confirm(`Yakin ingin mengubah status pesanan #${id} menjadi ${newStatus}?`)) {
                // Buat form dinamis untuk POST
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `pesanan.php?status=${currentTab}`;

                // Hidden field status
                let inputStatus = document.createElement('input');
                inputStatus.type = 'hidden';
                inputStatus.name = 'status';
                inputStatus.value = newStatus;
                form.appendChild(inputStatus);

                // Hidden field ID Pesanan
                let inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'id_pesanan';
                inputId.value = id;
                form.appendChild(inputId);

                // Hidden field ID User (untuk Notifikasi)
                let inputUser = document.createElement('input');
                inputUser.type = 'hidden';
                inputUser.name = 'id_user_for_notif';
                inputUser.value = idUser;
                form.appendChild(inputUser);

                // Hidden field untuk trigger update
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
    </script>
</head>
<body>

<div class="container">
    <a href="dashboard.admin.php" class="back-link"><i class="fas fa-chevron-left"></i> Kembali ke Dashboard</a>

    <div class="header-section">
        <h2>Kelola Pesanan</h2>
        <a href="tambah_pesanan.php" class="btn" style="background-color: var(--primary-color); color: white;"><i class="fas fa-plus"></i> Pesanan Manual</a>
    </div>

    <div class="tabs">
        <?php 
        $statuses = ['All', 'Menunggu', 'Dikonfirmasi', 'Ditolak', 'Selesai'];
        foreach ($statuses as $status): 
        ?>
            <button class="tab-button <?= $current_status === $status ? 'active' : ''; ?>" onclick="filterStatus('<?= $status; ?>')">
                <?= $status; ?> (<?= $status_counts[$status]; ?>)
            </button>
        <?php endforeach; ?>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Waktu Pesan</th>
                <th>Pemesan & Alamat</th>
                <th>Item & Total</th>
                <th>Status</th>
                <th>Aksi</th>
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
                        <small><?= htmlspecialchars(substr($row['alamat'], 0, 30)); ?>...</small>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($row['nama_menu'] ?? 'Menu Dihapus'); ?></strong>
                        <small>Total: Rp<?= number_format($row['total'], 0, ',', '.'); ?> (<?= $row['metode']; ?>)</small>
                    </td>
                    <td>
                        <span class="status-badge status-<?= $row['status']; ?>"><?= $row['status']; ?></span>
                    </td>
                    <td>
                        <?php if ($row['status'] === 'Menunggu'): ?>
                            <button class="action-button btn-confirm" onclick="setStatus(<?= $row['id_pesanan']; ?>, 'Dikonfirmasi', <?= $row['id_user']; ?>, '<?= $current_status; ?>')">
                                <i class="fas fa-check"></i> Konfirmasi
                            </button>
                            <button class="action-button btn-reject" onclick="setStatus(<?= $row['id_pesanan']; ?>, 'Ditolak', <?= $row['id_user']; ?>, '<?= $current_status; ?>')">
                                <i class="fas fa-times"></i> Tolak
                            </button>
                        <?php elseif ($row['status'] === 'Dikonfirmasi'): ?>
                            <button class="action-button btn-complete" onclick="setStatus(<?= $row['id_pesanan']; ?>, 'Selesai', <?= $row['id_user']; ?>, '<?= $current_status; ?>')">
                                <i class="fas fa-truck"></i> Selesaikan
                            </button>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: #999;">Tidak ada pesanan dalam status ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>