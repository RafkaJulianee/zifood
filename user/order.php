<?php
session_start();
include '../koneksi.php';

// Cek login dan pastikan ada menu yang dipesan (minimal 1 ID Menu dari dashboard)
if (!isset($_SESSION['id_user']) || !isset($_GET['id_menu'])) {
    header("Location: dashboard.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$id_menu = (int)$_GET['id_menu'];
$qty = 1; // Kuantitas default 1 karena langsung dari tombol 'Pesan'

// 1. Ambil Detail Menu
$menu_q = mysqli_query($conn, "SELECT nama_menu, harga, foto FROM menu WHERE id_menu='$id_menu'");
$menu_data = mysqli_fetch_assoc($menu_q);
$harga_satuan = (float)($menu_data['harga'] ?? 0);

// 2. Ambil Detail User (Nama, Alamat, No. HP tersimpan)
$user_q = mysqli_query($conn, "SELECT nama, alamat, no_hp FROM users WHERE id_user='$id_user'");
$user_data = mysqli_fetch_assoc($user_q);
// Membagi nama lengkap menjadi First Name dan Last Name (hanya untuk tampilan form)
$nama_parts = explode(' ', $user_data['nama'], 2);
$first_name = $nama_parts[0] ?? '';
$last_name = $nama_parts[1] ?? '';

// 3. Logika Perhitungan Awal Ongkir
// (Fungsi ini sekarang HANYA digunakan oleh JavaScript)
$subtotal = $harga_satuan * $qty;
$ongkir_default = 0; // Ongkir default 0, akan dihitung JS
$total = $subtotal + $ongkir_default;

// Logika Submit Pesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // [MODIFIKASI] Ambil mode pemesanan
    $mode_pemesanan = mysqli_real_escape_string($conn, $_POST['mode_pemesanan']);

    if ($mode_pemesanan === 'online') {
        // --- LOGIKA UNTUK ONLINE (DELIVERY) ---
        $final_alamat = mysqli_real_escape_string($conn, $_POST['delivery_address']);
        $final_nohp = mysqli_real_escape_string($conn, $_POST['whatsapp_phone']);
        $final_catatan = mysqli_real_escape_string($conn, $_POST['catatan']);
        $final_ongkir = (int)$_POST['ongkir_final'];
        $final_total = (int)$_POST['total_final'];
        $metode_bayar = 'Bayar di Tempat (COD)';
        $status_pesan = 'Menunggu'; // Status untuk online

        // Perbarui no_hp user jika berbeda
        if ($final_nohp != $user_data['no_hp']) {
             mysqli_query($conn, "UPDATE users SET no_hp='$final_nohp' WHERE id_user='$id_user'");
        }
        // Alamat dan Nomor HP di-update
        mysqli_query($conn, "UPDATE users SET alamat='$final_alamat', no_hp='$final_nohp' WHERE id_user='$id_user'");

        $query = "INSERT INTO pesanan (id_user, id_menu, jumlah, alamat, catatan, metode, ongkir, total, status)
                    VALUES ('$id_user', '$id_menu', '$qty', '$final_alamat', '$final_catatan', '$metode_bayar', '$final_ongkir', '$final_total', '$status_pesan')";
    
    } else {
        // --- LOGIKA UNTUK OFFLINE (DINE-IN) ---
        $nama_pemesan_offline = mysqli_real_escape_string($conn, $_POST['nama_pemesan_offline']);
        $nomor_meja = mysqli_real_escape_string($conn, $_POST['nomor_meja']);
        $final_nohp_offline = mysqli_real_escape_string($conn, $_POST['whatsapp_phone_offline']);
        $final_catatan_offline = mysqli_real_escape_string($conn, $_POST['catatan_offline']);
        
        $alamat_dine_in = "Dine-in: $nama_pemesan_offline (Meja $nomor_meja)";
        $catatan_dine_in = $final_catatan_offline;
        
        $final_ongkir = 0; // Tidak ada ongkir untuk dine-in
        $final_total = (int)($subtotal); // Total hanya subtotal
        $metode_bayar = 'Bayar di Kasir';
        $status_pesan = 'Dine-in'; // Status khusus untuk offline

        $query = "INSERT INTO pesanan (id_user, id_menu, jumlah, alamat, catatan, metode, ongkir, total, status)
                    VALUES ('$id_user', '$id_menu', '$qty', '$alamat_dine_in', '$catatan_dine_in', '$metode_bayar', '$final_ongkir', '$final_total', '$status_pesan')";
    }


    // Eksekusi query
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Pesanan berhasil dibuat!'); window.location='order.php';</script>";
    } else {
        echo "<script>alert('Gagal membuat pesanan: " . mysqli_error($conn) . "');</script>";
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Checkout - ZIFOOD</title>
    <link rel="shortcut icon" href="img/zifood.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #FF5722;
            --secondary-color: #4CAF50;
            --bg-light: #f7f7f7;
            --text-dark: #333;
            --border-radius: 12px;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .checkout-wrapper {
            display: flex;
            width: 1000px;
            max-width: 95%;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            margin: 20px 0;
        }
        .form-area {
            flex: 2;
            padding: 40px;
            overflow-y: auto;
            max-height: 90vh;
        }
        .form-area > a {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            display: block;
        }
        .form-area h2 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 30px;
        }
        .form-group h3 {
            font-size: 18px;
            color: var(--text-dark);
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .input-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .input-row > div, .full-row {
            flex: 1;
        }
        input[type="text"], input[type="number"], input[type="email"], select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-family: inherit;
        }
        label {
            display: block;
            font-size: 13px;
            margin-bottom: 5px;
            color: #666;
        }
        .option-toggle {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }
        .option-toggle button {
            flex: 1;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 14px;
        }
        .option-toggle button.active {
            background-color: #ffece6;
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        /* Tombol custom untuk cek lokasi */
        .btn-cek-lokasi {
            width: 100%;
            padding: 12px;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            margin-top: 5px;
        }
        .btn-cek-lokasi:disabled {
            background-color: #aaa;
            cursor: not-allowed;
        }

        /* Kanan: Summary */
        .summary-area {
            flex: 1;
            background-color: var(--bg-light);
            padding: 40px;
        }
        .summary-area h2 {
            font-size: 20px;
            margin-bottom: 30px;
            color: var(--text-dark);
        }
        .order-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .item-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            margin-right: 15px;
        }
        .price-details div {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }
        .totals-row.total {
            font-size: 20px;
            font-weight: 700;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            margin-top: 10px;
        }
        .totals-row.total span {
            color: var(--primary-color);
        }
        .btn-checkout {
            width: 100%;
            padding: 15px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
        }
    </style>
    <script>
        // Data PHP
        const HARGA_SATUAN = <?= $harga_satuan; ?>;
        const ALAMAT_TERDAFTAR = "<?= addslashes($user_data['alamat']); ?>";
        
        // --- [BARU] LOKASI RESTORAN ---
        // GANTI DENGAN KOORDINAT (LATITUDE, LONGITUDE) RESTORAN ANDA
        // Contoh ini menggunakan lokasi di Bandung
        const RESTO_LAT = -6.917464; 
        const RESTO_LON = 107.619123;
        // ---------------------------------

        // Format Rupiah (untuk JS)
        const formatter = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });

        // Logika Perhitungan Ongkir di JS
        function hitungOngkir(jarak) {
            jarak = parseFloat(jarak);
            if (isNaN(jarak) || jarak <= 0) return 0;
            
            if (jarak <= 1) return 0;
            if (jarak <= 2) return 3000;
            if (jarak <= 3) return 5000;
            if (jarak <= 4) return 7000;
            if (jarak <= 5) return 10000;

            return -1; // Jarak melebihi 5 km
        }

        function updateOngkirAndTotal() {
            // Hanya jalankan jika mode 'online'
            if (document.getElementById('mode_pemesanan').value !== 'online') return; 

            const qty = 1;
            const subtotal = HARGA_SATUAN * qty;
            
            // Ambil Jarak dari input hidden
            const inputJarak = document.getElementById('jarak_input').value;
            const jarak = parseFloat(inputJarak) || 0;

            let ongkir = hitungOngkir(jarak);
            let total = subtotal;
            let deliveryAvailable = true;
            let statusText = document.getElementById('delivery_status');

            if (ongkir === -1) {
                // Melebihi 5 km
                deliveryAvailable = false;
                statusText.innerHTML = `Jarak ${jarak.toFixed(2)} km. <span style="color:red; font-weight: bold;">Melebihi Batas (5km)</span>`;
                document.getElementById('checkout_btn_submit').disabled = true;
                
                ongkir = 0; 
                document.getElementById('ongkir_display').innerText = 'N/A';
            } else {
                total = subtotal + ongkir;
                let ongkirText = ongkir === 0 ? 'Gratis' : 'Rp' + formatter.format(ongkir);
                
                statusText.innerHTML = `Jarak ${jarak.toFixed(2)} km. <span style="color: var(--secondary-color); font-weight: bold;">${ongkirText}</span>`;
                document.getElementById('checkout_btn_submit').disabled = false;
                
                document.getElementById('ongkir_display').innerText = ongkirText;
            }
            
            document.getElementById('subtotal_display').innerText = 'Rp' + formatter.format(subtotal);
            document.getElementById('total_display').innerText = 'Rp' + formatter.format(total);
            document.getElementById('ongkir_final').value = ongkir;
            document.getElementById('total_final').value = total;
            document.getElementById('delivery_address_final').value = document.getElementById('alamat_delivery_input').value;
        }

        function toggleAddress(useRegistered) {
            if (document.getElementById('mode_pemesanan').value !== 'online') return;

            const deliveryInput = document.getElementById('alamat_delivery_input');
            const registeredButton = document.getElementById('btn_registered');
            const newButton = document.getElementById('btn_new');

            if (useRegistered) {
                deliveryInput.value = ALAMAT_TERDAFTAR;
                deliveryInput.disabled = true;
                registeredButton.classList.add('active');
                newButton.classList.remove('active');
            } else {
                deliveryInput.value = ''; // Kosongkan untuk input baru
                deliveryInput.disabled = false;
                registeredButton.classList.remove('active');
                newButton.classList.add('active');
            }
            // Reset ongkir saat ganti alamat, paksa user hitung ulang
            document.getElementById('jarak_input').value = 0;
            document.getElementById('delivery_status').innerHTML = 'Silakan cek lokasi Anda...';
            updateOngkirAndTotal();
        }

        // --- [FUNGSI BARU] Toggle Mode Online/Offline ---
        function toggleMode(mode) {
            const formOnline = document.getElementById('form_online_wrapper');
            const formOffline = document.getElementById('form_offline_wrapper');
            const btnOnline = document.getElementById('btn_mode_online');
            const btnOffline = document.getElementById('btn_mode_offline');
            const paymentDisplay = document.getElementById('payment_method_display');
            const hiddenModeInput = document.getElementById('mode_pemesanan');

            const subtotal = HARGA_SATUAN * 1;

            if (mode === 'offline') {
                formOnline.style.display = 'none';
                formOffline.style.display = 'block';
                btnOnline.classList.remove('active');
                btnOffline.classList.add('active');
                paymentDisplay.innerHTML = '<i class="fas fa-cash-register"></i> Bayar di Kasir';
                hiddenModeInput.value = 'offline';

                // Set ongkir dan total untuk mode OFFLINE
                document.getElementById('ongkir_display').innerText = 'Rp0';
                document.getElementById('total_display').innerText = 'Rp' + formatter.format(subtotal);
                document.getElementById('ongkir_final').value = 0;
                document.getElementById('total_final').value = subtotal;
                document.getElementById('checkout_btn_submit').disabled = false;
                
                // Non-aktifkan input online yang 'required'
                document.querySelector('input[name="whatsapp_phone"]').required = false;
                document.querySelector('textarea[name="delivery_address_input"]').required = false;

            } else { // Mode 'online'
                formOnline.style.display = 'block';
                formOffline.style.display = 'none';
                btnOnline.classList.add('active');
                btnOffline.classList.remove('active');
                paymentDisplay.innerHTML = '<i class="fas fa-money-bill-wave"></i> Bayar di Tempat (COD)';
                hiddenModeInput.value = 'online';

                // Aktifkan kembali input online yang 'required'
                document.querySelector('input[name="whatsapp_phone"]').required = true;
                document.querySelector('textarea[name="delivery_address_input"]').required = true;

                // Panggil fungsi update ongkir (akan me-reset ke 0)
                updateOngkirAndTotal(); 
            }
        }

        // --- [FUNGSI BARU] Geolocation ---
        function getLocationAndCalculate() {
            const statusText = document.getElementById('delivery_status');
            const geoButton = document.getElementById('btn_cek_lokasi');

            statusText.innerHTML = '<span style="color:#aaa;">Mendapatkan lokasi Anda...</span>';
            geoButton.disabled = true;

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(showPosition, showError);
            } else {
                statusText.innerHTML = '<span style="color:red;">Geolocation tidak didukung browser ini.</span>';
                geoButton.disabled = false;
            }
        }

        function showPosition(position) {
            const userLat = position.coords.latitude;
            const userLon = position.coords.longitude;
            const statusText = document.getElementById('delivery_status');
            const geoButton = document.getElementById('btn_cek_lokasi');

            // Hitung jarak
            const jarakKm = calculateDistance(userLat, userLon, RESTO_LAT, RESTO_LON);

            // Masukkan ke input hidden
            document.getElementById('jarak_input').value = jarakKm.toFixed(2);
            
            // Aktifkan tombol lagi
            geoButton.disabled = false;
            
            // Panggil update ongkir & total
            updateOngkirAndTotal(); 
        }

        function showError(error) {
            const statusText = document.getElementById('delivery_status');
            const geoButton = document.getElementById('btn_cek_lokasi');
            geoButton.disabled = false;

            switch(error.code) {
                case error.PERMISSION_DENIED:
                    statusText.innerHTML = '<span style="color:red;">Anda menolak izin lokasi.</span>';
                    break;
                case error.POSITION_UNAVAILABLE:
                    statusText.innerHTML = '<span style="color:red;">Informasi lokasi tidak tersedia.</span>';
                    break;
                case error.TIMEOUT:
                    statusText.innerHTML = '<span style="color:red;">Waktu permintaan lokasi habis.</span>';
                    break;
                case error.UNKNOWN_ERROR:
                    statusText.innerHTML = '<span style="color:red;">Terjadi kesalahan.</span>';
                    break;
            }
        }

        // --- [FUNGSI BARU] Rumus Haversine untuk Hitung Jarak ---
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // Radius bumi dalam KM
            const dLat = deg2rad(lat2 - lat1);
            const dLon = deg2rad(lon2 - lon1);
            const a =
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            const d = R * c; // Jarak dalam KM
            return d;
        }

        function deg2rad(deg) {
            return deg * (Math.PI / 180);
        }
        // --- [AKHIR FUNGSI BARU] ---


        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi: Gunakan alamat terdaftar
            toggleAddress(true);
            
            // Event listener untuk input alamat baru
            document.getElementById('alamat_delivery_input').addEventListener('input', function() {
                // Saat alamat diubah manual, reset ongkir
                document.getElementById('jarak_input').value = 0;
                document.getElementById('delivery_status').innerHTML = 'Silakan cek lokasi Anda...';
                updateOngkirAndTotal();
            });
            
            // Perbarui total saat page load (akan 0 ongkir)
            updateOngkirAndTotal();
        });
    </script>
</head>
<body>

<form method="POST" action="">
    <div class="checkout-wrapper">
        
        <div class="form-area">
            <a href="dashboard.php" style="color: var(--text-dark); text-decoration: none; font-weight: 500;"><i class="fas fa-arrow-left"></i> Checkout</a>
            <hr>

            <div class="form-group">
                <h3>Mode Pemesanan</h3>
                <div class="option-toggle">
                    <button type="button" id="btn_mode_online" class="active" onclick="toggleMode('online')">
                        <i class="fas fa-motorcycle"></i> Online (Delivery)
                    </button>
                    <button type="button" id="btn_mode_offline" onclick="toggleMode('offline')">
                        <i class="fas fa-store-alt"></i> Offline (Dine-in)
                    </button>
                </div>
            </div>
            <div id="form_online_wrapper"> 
                
                <div class="form-group">
                    <h3>1. Contact Information</h3>
                    <div class="input-row">
                        <div>
                            <label>First Name</label>
                            <input type="text" value="<?= htmlspecialchars($first_name); ?>" disabled>
                        </div>
                        <div>
                            <label>Last Name</label>
                            <input type="text" value="<?= htmlspecialchars($last_name); ?>" disabled>
                        </div>
                    </div>
                    <div class="input-row">
                        <div>
                            <label>No. WhatsApp</label>
                            <input type="text" name="whatsapp_phone" value="<?= htmlspecialchars($user_data['no_hp']); ?>" placeholder="Contoh: 0812xxxx" required>
                        </div>
                        <div>
                            <label>E-mail (Disabled)</label>
                            <input type="email" value="Email tidak digunakan" disabled>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <h3>2. Delivery Details</h3>
                    <label>Pilih Alamat Pengiriman (Maksimum 5 km)</label>
                    <div class="option-toggle">
                        <button type="button" id="btn_registered" onclick="toggleAddress(true)"><i class="fas fa-home"></i> Alamat Terdaftar</button>
                        <button type="button" id="btn_new" onclick="toggleAddress(false)"><i class="fas fa-map-marker-alt"></i> Alamat Baru</button>
                    </div>
                    
                    <div class="full-row" style="margin-top: 15px;">
                        <label>Alamat Lengkap</label>
                        <textarea id="alamat_delivery_input" name="delivery_address_input" required></textarea>
                    </div>

                    <div class="input-row" style="margin-top: 15px; align-items: flex-end;">
                        <div class="form-control" style="flex: 1;">
                            <label>Hitung Ongkos Kirim (Otomatis)</label>
                            <button type="button" id="btn_cek_lokasi" class="btn-cek-lokasi" onclick="getLocationAndCalculate()">
                                <i class="fas fa-map-marker-alt"></i> Cek Lokasi Saya
                            </button>
                        </div>
                        <div class="form-control" style="flex: 2;">
                            <label>Status Pengiriman</label>
                            <strong id="delivery_status" style="font-size: 14px; display: block; padding: 10px 0;">Silakan cek lokasi Anda...</strong>
                        </div>
                    </div>
                    <input type="hidden" id="jarak_input" value="0">
                    
                    <div class="full-row" style="margin-top: 15px;">
                        <label>Catatan (Opsional)</label>
                        <input type="text" name="catatan" placeholder="Contoh: Jangan terlalu pedas, antar ke pintu belakang.">
                    </div>
                </div>
            </div> 
            <div id="form_offline_wrapper" style="display: none;">
                <div class="form-group">
                    <h3>1. Detail Pemesan (Dine-in)</h3>
                    
                    <div class="input-row">
                        <div>
                            <label>Nama Pemesan</label>
                            <input type="text" name="nama_pemesan_offline" id="nama_pemesan_offline" value="<?= htmlspecialchars($first_name . ' ' . $last_name); ?>">
                        </div>
                        <div>
                            <label>Nomor Meja</label>
                            <select name="nomor_meja" id="nomor_meja">
                                <?php 
                                // Ganti angka 20 ini jika admin ingin mengubah jumlah meja
                                $jumlah_meja = 20; 
                                for ($i = 1; $i <= $jumlah_meja; $i++): 
                                ?>
                                    <option value="<?= $i; ?>">Meja <?= $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="full-row" style="margin-top: 15px;">
                        <label>No. WhatsApp (Opsional untuk Dine-in)</label>
                        <input type="text" name="whatsapp_phone_offline" value="<?= htmlspecialchars($user_data['no_hp']); ?>">
                    </div>

                    <div class="full-row" style="margin-top: 15px;">
                        <label>Catatan (Opsional)</label>
                        <input type="text" name="catatan_offline" placeholder="Contoh: Jangan terlalu pedas">
                    </div>

                </div>
            </div>
            <div class="form-group">
                <h3>3. Payment Method</h3>
                <label>Metode Pembayaran</label>
                <div class="option-toggle">
                    <button type="button" id="payment_method_display" class="active" style="flex: none; padding: 10px 20px;">
                        <i class="fas fa-money-bill-wave"></i> Bayar di Tempat (COD)
                    </button>
                </div>
            </div>
            
        </div>

        <div class="summary-area">
            <h2>Order Summary</h2>
            
            <div class="order-item">
                <img src="../assets/img/<?= htmlspecialchars($menu_data['foto'] ?? 'default.jpg'); ?>" alt="<?= htmlspecialchars($menu_data['nama_menu']); ?>" class="item-img">
                <div>
                    <strong><?= htmlspecialchars($menu_data['nama_menu']); ?></strong>
                    <div style="color: var(--primary-color);">Rp<?= number_format($harga_satuan, 0, ',', '.'); ?> x <?= $qty; ?></div>
                </div>
            </div>
            
            <div class="totals-row">
                <span>Subtotal</span>
                <span id="subtotal_display">Rp<?= number_format($subtotal, 0, ',', '.'); ?></span>
            </div>
            <div class="totals-row">
                <span>Ongkos Kirim</span>
                <span id="ongkir_display">Rp<?= number_format($ongkir_default, 0, ',', '.'); ?></span>
            </div>
            <div class="totals-row total">
                <span>TOTAL</span>
                <span id="total_display">Rp<?= number_format($total, 0, ',', '.'); ?></span>
            </div>
            
            <input type="hidden" name="ongkir_final" id="ongkir_final" value="<?= $ongkir_default; ?>">
            <input type="hidden" name="total_final" id="total_final" value="<?= $total; ?>">
            <input type="hidden" name="id_menu" value="<?= $id_menu; ?>">
            <input type="hidden" name="delivery_address" id="delivery_address_final" value="<?= htmlspecialchars($user_data['alamat']); ?>">
            <input type="hidden" name="mode_pemesanan" id="mode_pemesanan" value="online">

            <button type="submit" class="btn-checkout" id="checkout_btn_submit">
                Checkout & Pesan
            </button>

            <p style="text-align: center; font-size: 11px; margin-top: 15px; color: #666;">
                Dengan menekan tombol Checkout, Anda menyetujui syarat dan ketentuan yang berlaku.
            </p>
        </div>
        
    </div>
</form>

</body>
</html>