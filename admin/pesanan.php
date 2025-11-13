<?php
session_start();
include '../koneksi.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit;
}

// ===========================================
// LOGIKA UPDATE STATUS PESANAN (Konfirmasi/Tolak)
// ===========================================
if (isset($_POST['update_status'])) {
    $id_pesanan = mysqli_real_escape_string($conn, $_POST['id_pesanan']);
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Pastikan status yang dikirim valid sebelum menjalankan query
    if (in_array($new_status, ['Dikonfirmasi', 'Ditolak', 'Selesai'])) {
        $query_update = "UPDATE pesanan SET status='$new_status' WHERE id_pesanan='$id_pesanan'";
        
        if (mysqli_query($conn, $query_update)) {
            echo "<script>alert('Status pesanan ID $id_pesanan berhasil diubah menjadi $new_status!'); window.location='pesanan.php';</script>";
        } else {
            // Tampilkan error jika query gagal
            echo "<script>alert('Gagal mengubah status: " . mysqli_error($conn) . "');</script>";
        }
    } else {
        echo "<script>alert('Status tidak valid.');</script>";
    }
}

// ===========================================
// AMBIL DATA PESANAN DARI DATABASE
// ===========================================
$query_pesanan = "
    SELECT 
        p.id_pesanan, 
        u.nama AS nama_pemesan,
        m.nama_menu,
        p.jumlah,
        p.total,
        p.status,
        p.tanggal,
        p.alamat,
        p.metode
    FROM 
        pesanan p
    JOIN 
        users u ON p.id_user = u.id_user
    LEFT JOIN 
        menu m ON p.id_menu = m.id_menu
    ORDER BY 
        p.id_pesanan DESC
";

$result_pesanan = mysqli_query($conn, $query_pesanan);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Pesanan - Admin ZIFOOD</title>
</head>
<body>
    <h1>Kelola Pesanan</h1>
    <a href="dashboard.admin.php">Kembali ke Dashboard</a>
    <hr>

    <h2>Daftar Semua Pesanan</h2>

    <?php if (mysqli_num_rows($result_pesanan) > 0): ?>
        <table border="1" cellpadding="8" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>ID Pesanan</th>
                    <th>Pemesan</th>
                    <th>Menu Dipesan</th>
                    <th>Qty</th>
                    <th>Total Bayar</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result_pesanan)): ?>
                <tr>
                    <td><?= $row['id_pesanan']; ?></td>
                    <td><?= htmlspecialchars($row['nama_pemesan']); ?></td>
                    <td>
                        <?= htmlspecialchars($row['nama_menu'] ?? 'Menu Dihapus'); ?>
                        <br>
                        <small>Alamat: <?= htmlspecialchars($row['alamat']); ?></small>
                        <br>
                        <small>Metode: <?= htmlspecialchars($row['metode']); ?></small>
                    </td>
                    <td><?= $row['jumlah']; ?></td>
                    <td>Rp<?= number_format($row['total'], 0, ',', '.'); ?></td>
                    <td><?= htmlspecialchars($row['tanggal']); ?></td>
                    <td><strong><?= htmlspecialchars($row['status']); ?></strong></td>
                    <td>
                        <?php if ($row['status'] === 'Menunggu'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id_pesanan" value="<?= $row['id_pesanan']; ?>">
                                <input type="hidden" name="status" value="Dikonfirmasi">
                                <button type="submit" name="update_status" style="background-color:green; color:white;">Konfirmasi</button>
                            </form>

                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id_pesanan" value="<?= $row['id_pesanan']; ?>">
                                <input type="hidden" name="status" value="Ditolak">
                                <button type="submit" name="update_status" style="background-color:red; color:white;">Tolak</button>
                            </form>
                        <?php elseif ($row['status'] === 'Dikonfirmasi'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id_pesanan" value="<?= $row['id_pesanan']; ?>">
                                <input type="hidden" name="status" value="Selesai">
                                <button type="submit" name="update_status" style="background-color:blue; color:white;">Selesai</button>
                            </form>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Tidak ada data pesanan yang ditemukan.</p>
    <?php endif; ?>

</body>
</html>