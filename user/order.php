<?php
session_start();
include '../koneksi.php';

// Cek login dan pastikan ada menu yang dipesan
if (!isset($_SESSION['id_user']) || !isset($_GET['id_menu'])) {
    header("Location: dashboard.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$id_menu = (int)$_GET['id_menu'];
$qty = 1; // Kuantitas default 1

// 1. Ambil Detail Menu
$menu_q = mysqli_query($conn, "SELECT nama_menu, harga, foto FROM menu WHERE id_menu='$id_menu'");
$menu_data = mysqli_fetch_assoc($menu_q);
$harga_satuan = (float)($menu_data['harga'] ?? 0);

// 2. Ambil Detail User (Nama, Alamat, No. HP tersimpan)
$user_q = mysqli_query($conn, "SELECT nama, alamat, no_hp FROM users WHERE id_user='$id_user'");
$user_data = mysqli_fetch_assoc($user_q);
$nama_parts = explode(' ', $user_data['nama'], 2);
$first_name = $nama_parts[0] ?? '';
$last_name = $nama_parts[1] ?? '';

// 3. Logika Perhitungan Awal (Default ongkir 0)
$ongkir_default = 0;
$subtotal = $harga_satuan * $qty;
$total = $subtotal + $ongkir_default;

// ==========================================================
// LOGIKA SUBMIT PESANAN
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metode_pengambilan = mysqli_real_escape_string($conn, $_POST['metode_pengambilan']);
    $final_nohp = mysqli_real_escape_string($conn, $_POST['whatsapp_phone']);
    $final_catatan = mysqli_real_escape_string($conn, $_POST['catatan']);
    $final_ongkir = (int)$_POST['ongkir_final'];
    $final_total = (int)$_POST['total_final'];
    
    $final_alamat = "";
    $metode_bayar = "Bayar di Tempat (COD)";

    if ($metode_pengambilan === 'Dine-in') {
        // Jika Dine-in, alamat diisi nomor meja
        $final_alamat = "Dine-in, " . mysqli_real_escape_string($conn, $_POST['meja_nomor']);
    } else {
        // Jika Delivery, ambil alamat dari input
        $final_alamat = mysqli_real_escape_string($conn, $_POST['delivery_address']);
    }

    // Perbarui no_hp user jika berbeda
    if ($final_nohp != $user_data['no_hp']) {
         mysqli_query($conn, "UPDATE users SET no_hp='$final_nohp' WHERE id_user='$id_user'");
    }
    
    // Insert Pesanan Baru
    $query = "INSERT INTO pesanan (id_user, id_menu, jumlah, alamat, catatan, metode, ongkir, total, status)
              VALUES ('$id_user', '$id_menu', '$qty', '$final_alamat', '$final_catatan', '$metode_bayar', '$final_ongkir', '$final_total', 'Menunggu')";
    
    if (mysqli_query($conn, $query)) {
        // Update alamat default user jika dia pakai alamat baru (opsional)
        if ($metode_pengambilan === 'Delivery') {
            mysqli_query($conn, "UPDATE users SET alamat='$final_alamat' WHERE id_user='$id_user'");
        }
        echo "<script>alert('Pesanan berhasil dibuat! Menunggu konfirmasi.'); window.location='dashboard.php';</script>";
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
        }
        /* Kiri: Form */
        .form-area {
            flex: 2;
            padding: 40px;
            overflow-y: auto;
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
        input[type="text"], input[type="number"], select, textarea {
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
        
        /* Opsi Delivery */
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
        }
        .option-toggle button.active {
            background-color: #ffece6;
            border-color: var(--primary-color);
            color: var(--primary-color);
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

        /* Final Button */
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
        let deliveryMetode = 'Delivery'; // Default

        // Logika Perhitungan Ongkir di JS
        function hitungOngkir(jarak) {
            jarak = parseFloat(jarak);
            if (isNaN(jarak) || jarak <= 0) return 0;
            
            if (jarak <= 1) return 0;
            if (jarak <= 2) return 3000;
            if (jarak <= 3) return 5000;
            if (jarak <= 4) return 7000;
            if (jarak <= 5) return 10000;

            return -1; // Melebihi 5 km
        }

        function updateOngkirAndTotal() {
            const qty = 1;
            const subtotal = HARGA_SATUAN * qty;
            
            let ongkir = 0;
            let total = subtotal;
            let deliveryAvailable = true;

            const formatter = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });

            if (deliveryMetode === 'Delivery') {
                // Ambil jarak dari SELECT
                const inputJarak = document.getElementById('jarak_select').value;
                const jarak = parseFloat(inputJarak) || 1;

                ongkir = hitungOngkir(jarak);

                if (ongkir === -1) {
                    deliveryAvailable = false;
                    document.getElementById('delivery_status').innerText = 'Jarak Melebihi Batas (> 5km)';
                    document.getElementById('checkout_btn_submit').disabled = true;
                    document.getElementById('ongkir_display').innerText = 'N/A';
                    total = subtotal;
                    ongkir = 0; 
                } else {
                    total = subtotal + ongkir;
                    document.getElementById('delivery_status').innerText = ongkir === 0 ? 'Gratis' : 'Rp' + formatter.format(ongkir);
                    document.getElementById('checkout_btn_submit').disabled = false;
                    document.getElementById('ongkir_display').innerText = 'Rp' + formatter.format(ongkir);
                }
            
            } else { // Mode Dine-in
                ongkir = 0;
                total = subtotal;
                deliveryAvailable = true;
                document.getElementById('delivery_status').innerText = 'Dine-in (Ambil di Resto)';
                document.getElementById('checkout_btn_submit').disabled = false;
                document.getElementById('ongkir_display').innerText = 'Rp 0';
            }
            
            // Update Summary DOM
            document.getElementById('subtotal_display').innerText = 'Rp' + formatter.format(subtotal);
            document.getElementById('total_display').innerText = 'Rp' + formatter.format(total);
            
            // Update hidden inputs untuk PHP
            document.getElementById('ongkir_final').value = ongkir;
            document.getElementById('total_final').value = total;
            document.getElementById('metode_pengambilan_final').value = deliveryMetode;
            
            // Update alamat yang akan disubmit (jika delivery)
            if(deliveryMetode === 'Delivery') {
                document.getElementById('delivery_address_final').value = document.getElementById('alamat_delivery_input').value;
            }
        }
        
        // Ganti antara Alamat Terdaftar / Alamat Baru
        function toggleAddress(useRegistered) {
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
            updateOngkirAndTotal();
        }

        // Ganti antara Delivery / Dine-in
        function toggleMetode(metode) {
            deliveryMetode = metode;
            const deliverySection = document.getElementById('delivery_details_section');
            const dineinSection = document.getElementById('dinein_details_section');
            const btnDelivery = document.getElementById('btn_delivery');
            const btnDinein = document.getElementById('btn_dinein');

            if (metode === 'Delivery') {
                deliverySection.style.display = 'block';
                dineinSection.style.display = 'none';
                btnDelivery.classList.add('active');
                btnDinein.classList.remove('active');
            } else { // Dine-in
                deliverySection.style.display = 'none';
                dineinSection.style.display = 'block';
                btnDelivery.classList.remove('active');
                btnDinein.classList.add('active');
            }
            updateOngkirAndTotal();
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi: Default ke Delivery & Alamat Terdaftar
            toggleMetode('Delivery');
            toggleAddress(true);
            updateOngkirAndTotal();
            
            // Event listener
            document.getElementById('jarak_select').addEventListener('change', updateOngkirAndTotal);
            document.getElementById('alamat_delivery_input').addEventListener('input', updateOngkirAndTotal);
        });
    </script>
</head>
<body>

<form method="POST" action="">
    <div class="checkout-wrapper">
        
        <div class="form-area">
            <a href="dashboard.php" style="color: var(--text-dark); text-decoration: none; font-weight: 500;"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
            <hr>

            <div class="form-group">
                <h3>1. Metode Pengambilan</h3>
                <div class="option-toggle">
                    <button type="button" id="btn_delivery" onclick="toggleMetode('Delivery')"><i class="fas fa-truck"></i> Delivery</button>
                    <button type="button" id="btn_dinein" onclick="toggleMetode('Dine-in')"><i class="fas fa-store"></i> Dine-in (Makan di Tempat)</button>
                </div>
            </div>

            <div class="form-group">
                <h3>2. Contact Information</h3>
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
                </div>
            </div>

            <div class="form-group" id="delivery_details_section">
                <h3>3. Delivery Details</h3>
                <label>Pilih Alamat Pengiriman (Maksimum 5 km)</label>
                <div class="option-toggle">
                    <button type="button" id="btn_registered" onclick="toggleAddress(true)"><i class="fas fa-home"></i> Alamat Terdaftar</button>
                    <button type="button" id="btn_new" onclick="toggleAddress(false)"><i class="fas fa-map-marker-alt"></i> Alamat Baru</button>
                </div>
                
                <div class="full-row" style="margin-top: 15px;">
                    <label>Alamat Lengkap</label>
                    <textarea id="alamat_delivery_input" name="delivery_address_input" required></textarea>
                </div>

                <div class="input-row" style="margin-top: 15px;">
                    <div class="form-control">
                        <label>Perkiraan Zona Jarak</label>
                        <select id="jarak_select">
                            <option value="1">Sangat Dekat (<= 1km)</option>
                            <option value="2">Dekat (2km)</option>
                            <option value="3">Sedang (3km)</option>
                            <option value="4">Jauh (4km)</option>
                            <option value="5">Sangat Jauh (5km)</option>
                            <option value="6">Di Luar Jangkauan (> 5km)</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label>Status Ongkir</label>
                        <strong id="delivery_status" style="font-size: 16px; display: block; padding: 10px 0;">Menghitung...</strong>
                    </div>
                </div>
            </div>

            <div class="form-group" id="dinein_details_section" style="display: none;">
                <h3>3. Detail Dine-in</h3>
                <div class="full-row">
                    <label>Pilih Nomor Meja</label>
                    <select id="meja_select" name="meja_nomor">
                        <option value="Meja 1">Meja 1</option>
                        <option value="Meja 2">Meja 2</option>
                        <option value="Meja 3">Meja 3</option>
                        <option value="Meja 4">Meja 4</option>
                        <option value="Meja 5">Meja 5</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <h3>4. Lain-lain</h3>
                <div class="full-row">
                    <label>Catatan (Opsional)</label>
                    <input type="text" name="catatan" placeholder="Contoh: Jangan terlalu pedas, antar ke pintu belakang.">
                </div>
            </div>
            
            <div class="form-group">
                <h3>5. Payment Method</h3>
                <label>Metode Pembayaran (Hanya Bayar di Tempat yang tersedia)</label>
                <div class="option-toggle">
                    <button type="button" class="active" style="flex: none; padding: 10px 20px;"><i class="fas fa-money-bill-wave"></i> Bayar di Tempat (COD)</button>
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
            <input type="hidden" name="metode_pengambilan" id="metode_pengambilan_final" value="Delivery">
            <input type="hidden" name="delivery_address" id="delivery_address_final" value="<?= htmlspecialchars($user_data['alamat']); ?>">
            
            <button type="submit" class="btn-checkout" id="checkout_btn_submit">
                Checkout & Pesan
            </button>

            <p style="text-align: center; font-size: 11px; margin-top: 15px; color: #666;">
                Dengan menekan tombol Checkout, Anda menyetujui syarat dan ketentuan.
            </p>
        </div>
        
    </div>
</form>

</body>
</html>