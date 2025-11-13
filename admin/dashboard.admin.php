<?php
session_start();
include '../koneksi.php';

// Cek apakah admin sudah login
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit;
}

// Ambil data total menu
$totalMenu = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM menu"))['total'];

// Ambil total pesanan
$totalPesanan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM pesanan"))['total'] ?? 0;

// Ambil total pendapatan (FIX: menggunakan kolom 'total')
$pendapatan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total) AS total FROM pesanan WHERE status='Selesai'"))['total'] ?? 0;

// Ambil 5 pesanan terbaru (FIX: menggunakan JOIN untuk nama pemesan)
$pesananBaru = mysqli_query($conn, "SELECT p.*, u.nama FROM pesanan p JOIN users u ON p.id_user = u.id_user ORDER BY p.id_pesanan DESC LIMIT 5");

// Ambil menu dengan rating tertinggi
$menuTop = mysqli_query($conn, "SELECT * FROM menu ORDER BY rating_rata DESC LIMIT 3");


// ==========================================================
// LOGIKA UNTUK GRAFIK PENDAPATAN BULANAN
// ==========================================================
$grafikPendapatanResult = mysqli_query($conn, "
    SELECT 
        DATE_FORMAT(tanggal, '%Y-%m') AS bulan, 
        SUM(total) AS total_pendapatan 
    FROM 
        pesanan 
    WHERE 
        status='Selesai' 
    GROUP BY 
        bulan 
    ORDER BY 
        bulan ASC
");

$dataBulan = [];
$dataPendapatan = [];

while ($data = mysqli_fetch_assoc($grafikPendapatanResult)) {
    // Label Bulan (misal: 2023-11)
    $dataBulan[] = $data['bulan'];
    // Data Pendapatan (dikonversi ke float)
    $dataPendapatan[] = (float)$data['total_pendapatan'];
}

// Konversi array PHP ke format JSON agar bisa digunakan di JavaScript
$jsonBulan = json_encode($dataBulan);
$jsonPendapatan = json_encode($dataPendapatan);
// ==========================================================
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - ZIFOOD</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function logoutConfirm() {
            return confirm("Yakin mau logout dari akun admin?");
        }
    </script>
</head>
<body>
    <h1>Dashboard Admin ZIFOOD</h1>
    <h3>Halo, <?= $_SESSION['username_admin']; ?> 👋</h3>

    <a href="menu.php">Kelola Menu</a> |
    <a href="pesanan.php">Kelola Pesanan</a> |
    <a href="ubah_password.php">Ubah Password</a> |
    <a href="logout.php" onclick="return logoutConfirm()">Logout</a>

    <hr>

    <h2>Ringkasan Data</h2>
    <ul>
        <li>Total Menu: <?= $totalMenu; ?></li>
        <li>Total Pesanan: <?= $totalPesanan; ?></li>
        <li>Total Pendapatan: Rp<?= number_format($pendapatan, 0, ',', '.'); ?></li>
    </ul>

    <hr>
    
    <h2>Grafik Pendapatan Bulanan</h2>
    <div style="width: 80%; margin: auto;">
        <canvas id="pendapatanChart"></canvas>
    </div>

    <script>
        // Data PHP di-inject langsung ke JavaScript
        const labelBulan = <?= $jsonBulan; ?>;
        const dataTotalPendapatan = <?= $jsonPendapatan; ?>;

        const ctx = document.getElementById('pendapatanChart').getContext('2d');
        const pendapatanChart = new Chart(ctx, {
            type: 'line', // Jenis grafik garis
            data: {
                labels: labelBulan,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: dataTotalPendapatan,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Total Pendapatan (Rp)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Bulan (Tahun-Bulan)'
                        }
                    }
                },
                responsive: true
            }
        });
    </script>
    
    <hr>

    <h2>Pesanan Terbaru</h2>
    <?php if (mysqli_num_rows($pesananBaru) > 0): ?>
        <table border="1" cellpadding="8" cellspacing="0">
            <tr>
                <th>ID Pesanan</th>
                <th>Nama Pemesan</th>
                <th>Total Harga</th>
                <th>Status</th>
                <th>Tanggal</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($pesananBaru)): ?>
                <tr>
                    <td><?= $row['id_pesanan']; ?></td>
                    <td><?= htmlspecialchars($row['nama']); ?></td>
                    <td>Rp<?= number_format($row['total'], 0, ',', '.'); ?></td>
                    <td><?= htmlspecialchars($row['status']); ?></td>
                    <td><?= htmlspecialchars($row['tanggal']); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>Belum ada pesanan.</p>
    <?php endif; ?>

    <hr>

    <h2>Menu Terpopuler</h2>
    <?php if (mysqli_num_rows($menuTop) > 0): ?>
        <ol>
            <?php while ($m = mysqli_fetch_assoc($menuTop)): ?>
                <li><?= htmlspecialchars($m['nama_menu']); ?> (⭐ <?= number_format($m['rating_rata'], 1); ?>)</li>
            <?php endwhile; ?>
        </ol>
    <?php else: ?>
        <p>Belum ada data menu.</p>
    <?php endif; ?>

</body>
</html>