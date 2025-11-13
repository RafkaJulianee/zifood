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

// Ambil total pendapatan
$pendapatan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total) AS total FROM pesanan WHERE status='Selesai'"))['total'] ?? 0;

// BARU: Total Pesanan Menunggu Konfirmasi (untuk KPI Card)
$waiting_orders_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pesanan WHERE status='Menunggu'");
$totalMenunggu = mysqli_fetch_assoc($waiting_orders_query)['total'] ?? 0;

// Ambil 5 pesanan terbaru (untuk list di bawah)
$pesananBaru = mysqli_query($conn, "SELECT p.*, u.nama FROM pesanan p JOIN users u ON p.id_user = u.id_user ORDER BY p.id_pesanan DESC LIMIT 5");

// Ambil menu dengan rating tertinggi (digunakan sebagai Sales by Category)
$menuTop = mysqli_query($conn, "SELECT * FROM menu ORDER BY rating_rata DESC LIMIT 5");


// LOGIKA UNTUK GRAFIK PENDAPATAN BULANAN (Revenue Chart)
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
    $dataBulan[] = $data['bulan'];
    $dataPendapatan[] = (float)$data['total_pendapatan'];
}

$jsonBulan = json_encode($dataBulan);
$jsonPendapatan = json_encode($dataPendapatan);

if (isset($_GET['logout'])) {
    session_destroy(); // Hapus semua sesi
    header("Location: login.php"); // Lempar kembali ke login
    exit;
}
// -----------------------------------------

// Cek apakah admin sudah login (Kode lama kamu)
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - ZIFOOD</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --theme-primary: #FF5722; /* Merah-Oranye */
            --theme-primary-dark: #e04e1f; /* Sedikit lebih gelap */
            --bg-light: #f4f6f9;
            --text-dark: #1f2937;
            --card-orange-gradient-start: #FF5722; /* Oranye utama */
            --card-orange-gradient-end: #ffa07a; /* Oranye lebih terang */
            --card-red: #ef4444;
            --card-green: #10b981;
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
        }

        .dashboard-wrapper {
            padding: 20px;
            display: flex;
            gap: 20px;
            min-height: 100vh;
        }

        /* 1. SIDEBAR KIRI (NAVBAR ICON) */
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
        }
        .nav-link {
            padding: 10px;
            margin: 5px 0;
            color: #9ca3af;
            font-size: 20px;
            transition: color 0.2s, background-color 0.2s;
            border-radius: 8px;
            text-decoration: none; /* Tambahkan ini agar link tidak bergaris bawah */
        }
        .nav-link:hover, .nav-link.active {
            color: var(--theme-primary);
            background-color: #fcebeb;
        }

        /* 2. KONTEN UTAMA (GRID) */
        .main-content {
            flex-grow: 1;
            display: grid;
            grid-template-columns: repeat(4, 1fr); /* 4 kolom di bagian atas */
            grid-template-rows: auto auto 1fr; /* Header, KPI, Chart, List */
            gap: 20px;
        }
        .header {
            grid-column: 1 / -1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 24px;
            font-weight: 600;
        }
        .user-greeting {
            color: #4b5563;
        }

        /* KPI CARDS (Total Revenue, Total Orders, etc.) */
        .kpi-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.2s;
            position: relative;
            overflow: hidden;
        }
        .kpi-card.revenue {
            grid-column: 1 / span 2; /* Revenue card mengambil 2 kolom */
            background: linear-gradient(135deg, var(--card-orange-gradient-start), var(--card-orange-gradient-end));
            color: white;
            padding: 30px;
        }
        .kpi-card h3 {
            font-size: 14px;
            font-weight: 500;
            margin: 0 0 10px;
            opacity: 0.8;
        }
        .kpi-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .kpi-footer {
            font-size: 12px;
            opacity: 0.9;
        }
        .kpi-card .trend {
            font-size: 14px;
            font-weight: 600;
            color: var(--card-green);
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 8px;
            border-radius: 4px;
        }
        
        /* REVENUE CHART */
        .chart-card {
            grid-column: 1 / span 4; /* Chart mengambil 4 kolom penuh di bawah KPI */
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        /* RECENT ORDERS & SALES BY CATEGORY */
        .list-card {
            grid-column: 1 / span 4;
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }
        .recent-orders, .sales-by-category {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .list-card h3 {
            font-size: 18px;
            font-weight: 600;
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .order-item {
            padding: 10px 0;
            border-bottom: 1px dashed #eee;
            display: flex;
            justify-content: space-between;
            font-size: 14px;
        }
        .top-menu-item {
            padding: 8px 0;
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            border-bottom: 1px solid #f0f0f0;
        }
        .top-menu-item:last-child { border-bottom: none; }
        .top-menu-item span {
            color: var(--theme-primary);
            font-weight: 600;
        }
    </style>

    <script>
        function logoutConfirm() {
            return confirm("Yakin mau logout dari akun admin?");
        }
    </script>
</head>
<body>

<div class="dashboard-wrapper">
    
    <div class="sidebar">
        <a href="dashboard.admin.php" class="nav-link active" title="Dashboard"><i class="fas fa-chart-line"></i></a>
        <a href="menu.php" class="nav-link" title="Kelola Menu"><i class="fas fa-utensils"></i></a>
        
        <a href="tambah.php" class="nav-link" title="Tambah Menu"><i class="fas fa-plus"></i></a>
        
        <a href="pesanan.php" class="nav-link" title="Kelola Pesanan"><i class="fas fa-receipt"></i></a>
        <a href="dashboard.admin.php?logout=true" class="nav-link" onclick="return logoutConfirm()" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>

    <div class="main-content">
        
        <div class="header">
            <h1>Hello, <?= $_SESSION['username_admin']; ?>!</h1>
            <div class="user-greeting">This is what's happening in your store this month.</div>
        </div>

        <div class="kpi-card revenue">
            <h3>Total Revenue (Pendapatan)</h3>
            <div class="kpi-value">Rp<?= number_format($pendapatan, 0, ',', '.'); ?></div>
            <div class="kpi-footer">
                <span class="trend" style="color: var(--card-green);">+5.6%</span> This month vs last
            </div>
        </div>
        
        <div class="kpi-card">
            <h3>Total Orders (Pesanan)</h3>
            <div class="kpi-value"><?= $totalPesanan; ?></div>
            <div class="kpi-footer">
                <span style="color: var(--card-red); font-weight: 600;">-2.4%</span> This month vs last
            </div>
        </div>
        
        <div class="kpi-card">
            <h3>Total Menu (Visitors)</h3>
            <div class="kpi-value"><?= $totalMenu; ?></div>
            <div class="kpi-footer">
                 <span style="color: var(--card-green); font-weight: 600;">+3.1%</span> Items in stock
            </div>
        </div>
        
        <div class="chart-card">
            <h3>Revenue Chart (Pendapatan Bulanan)</h3>
            <canvas id="pendapatanChart" style="max-height: 250px;"></canvas>
        </div>

        <div class="list-card">
            
            <div class="recent-orders">
                <h3>Pesanan Terbaru</h3>
                <div class="order-summary-kpi" style="background-color: var(--bg-light); padding: 15px; border-radius: 8px; margin-bottom: 15px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 700; color: var(--theme-primary);"><?= $totalMenunggu; ?></div>
                    <small style="color: #666;">Orders awaiting confirmation</small>
                </div>

                <?php if (mysqli_num_rows($pesananBaru) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($pesananBaru)): ?>
                        <div class="order-item">
                            <span>#<?= $row['id_pesanan']; ?> - <?= htmlspecialchars($row['nama']); ?></span>
                            <span style="font-weight: 600; color: var(--text-dark);">Rp<?= number_format($row['total'], 0, ',', '.'); ?></span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="font-size: 12px; color: #999;">Belum ada pesanan terbaru.</p>
                <?php endif; ?>
                <a href="pesanan.php" style="display: block; text-align: center; margin-top: 15px; color: var(--theme-primary); text-decoration: none;">Lihat Semua Pesanan →</a>
            </div>

            <div class="sales-by-category">
                <h3>Top Menu Items (Rating)</h3>
                 <?php if (mysqli_num_rows($menuTop) > 0): ?>
                    <ol style="padding-left: 20px; margin: 0;">
                    <?php while ($m = mysqli_fetch_assoc($menuTop)): ?>
                        <li class="top-menu-item">
                            <span><?= htmlspecialchars($m['nama_menu']); ?></span>
                            <span>⭐ <?= number_format($m['rating_rata'], 1); ?></span>
                        </li>
                    <?php endwhile; ?>
                    </ol>
                <?php else: ?>
                     <p style="font-size: 12px; color: #999;">Belum ada data menu top.</p>
                <?php endif; ?>
            </div>
            
        </div>
        
    </div>
</div>

<script>
    // Data PHP di-inject langsung ke JavaScript
    const labelBulan = <?= $jsonBulan; ?>;
    const dataTotalPendapatan = <?= $jsonPendapatan; ?>;

    const ctx = document.getElementById('pendapatanChart').getContext('2d');
    const pendapatanChart = new Chart(ctx, {
        type: 'bar', // Menggunakan bar chart sesuai konsep
        data: {
            labels: labelBulan,
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: dataTotalPendapatan,
                backgroundColor: 'rgba(255, 87, 34, 0.8)', 
                borderColor: 'rgba(255, 87, 34, 1)',
                borderWidth: 1,
                borderRadius: 5,
                barThickness: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { display: false },
                    title: { display: true, text: 'Rupiah (Rp)' }
                },
                x: {
                    grid: { display: false },
                    title: { display: true, text: 'Bulan' }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: { mode: 'index', intersect: false }
            }
        }
    });
</script>

<script>
    function logoutConfirm() {
        return confirm("Yakin mau logout dari akun admin?");
    }
</script>
</body>
</html>