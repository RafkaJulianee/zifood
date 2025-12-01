<?php
session_start();
include '../koneksi.php'; // Pastikan path ini sesuai dengan struktur foldermu

// Cek login dan pastikan ada menu yang dipesan
if (!isset($_SESSION['id_user']) || !isset($_GET['id_menu'])) {
    header("Location: dashboard.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$id_menu = (int)$_GET['id_menu'];
$jumlah = 1; 

// 1. Ambil Detail Menu
$kueri_menu = mysqli_query($conn, "SELECT nama_menu, harga, foto FROM menu WHERE id_menu='$id_menu'");
$data_menu = mysqli_fetch_assoc($kueri_menu);
$harga_satuan = (float)($data_menu['harga'] ?? 0);

// 2. Ambil Detail User
$kueri_user = mysqli_query($conn, "SELECT nama, alamat, no_hp FROM users WHERE id_user='$id_user'");
$data_user = mysqli_fetch_assoc($kueri_user);

$pecah_nama = explode(' ', $data_user['nama'], 2);
$nama_depan = $pecah_nama[0] ?? '';
$nama_belakang = $pecah_nama[1] ?? '';

// 3. Logika Perhitungan Awal
$subtotal = $harga_satuan * $jumlah;
$ongkir_awal = 0;
$total = $subtotal + $ongkir_awal;

// Logika Submit Pesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Ambil mode pemesanan
    $mode_pemesanan = mysqli_real_escape_string($conn, $_POST['mode_pemesanan']);

    if ($mode_pemesanan === 'online') {
        // --- LOGIKA ONLINE (DELIVERY) ---
        $alamat_final = mysqli_real_escape_string($conn, $_POST['alamat_delivery_final']);
        $nohp_final = mysqli_real_escape_string($conn, $_POST['telepon_whatsapp']);
        $catatan_final = mysqli_real_escape_string($conn, $_POST['catatan']);
        $ongkir_final = (int)$_POST['ongkir_final'];
        $total_final = (int)$_POST['total_final'];
        $metode_bayar = 'Bayar di Tempat (COD)';
        $status_pesan = 'Menunggu';
        
        // Untuk Online, nomor meja diisi 0
        $nomor_meja_db = '0'; 

        // Update data user jika berubah
        if ($nohp_final != $data_user['no_hp']) {
             mysqli_query($conn, "UPDATE users SET no_hp='$nohp_final' WHERE id_user='$id_user'");
        }
        mysqli_query($conn, "UPDATE users SET alamat='$alamat_final', no_hp='$nohp_final' WHERE id_user='$id_user'");

        $kueri_insert = "INSERT INTO pesanan (id_user, id_menu, jumlah, alamat, catatan, metode, ongkir, total, status, meja)
                         VALUES ('$id_user', '$id_menu', '$jumlah', '$alamat_final', '$catatan_final', '$metode_bayar', '$ongkir_final', '$total_final', '$status_pesan', '$nomor_meja_db')";
    
    } else {
        // --- LOGIKA OFFLINE (DINE-IN) ---
        $nama_pemesan_offline = mysqli_real_escape_string($conn, $_POST['nama_pemesan_offline']);
        $nomor_meja = mysqli_real_escape_string($conn, $_POST['nomor_meja']); 
        $nohp_final_offline = mysqli_real_escape_string($conn, $_POST['telepon_whatsapp_offline']);
        $catatan_final_offline = mysqli_real_escape_string($conn, $_POST['catatan_offline']);
        
        $alamat_dine_in = "Dine-in: $nama_pemesan_offline";
        $catatan_dine_in = $catatan_final_offline;
        
        $ongkir_final = 0;
        $total_final = (int)($subtotal);
        $metode_bayar = 'Bayar di Kasir';
        $status_pesan = 'Menunggu';

        $kueri_insert = "INSERT INTO pesanan (id_user, id_menu, jumlah, alamat, catatan, metode, ongkir, total, status, meja)
                         VALUES ('$id_user', '$id_menu', '$jumlah', '$alamat_dine_in', '$catatan_dine_in', '$metode_bayar', '$ongkir_final', '$total_final', '$status_pesan', '$nomor_meja')";
    }

    // Eksekusi query
    if (mysqli_query($conn, $kueri_insert)) {
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
    <link rel="stylesheet" href="CSS/order.css">
    <link rel="shortcut icon" href="img/zifood.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<form method="POST" action="">
    <div class="bungkus-checkout">
        
        <div class="area-formulir">
            <a href="dashboard.php" style="color: var(--teks-gelap); text-decoration: none; font-weight: 500;"><i class="fas fa-arrow-left"></i> Checkout</a>
            <hr>

            <div class="grup-formulir">
                <h3>Mode Pemesanan</h3>
                <div class="ganti-opsi">
                    <button type="button" id="tombol_mode_online" class="active" onclick="gantiModePemesanan('online')">
                        <i class="fas fa-motorcycle"></i> Online (Delivery)
                    </button>
                    <button type="button" id="tombol_mode_offline" onclick="gantiModePemesanan('offline')">
                        <i class="fas fa-store-alt"></i> Offline (Dine-in)
                    </button>
                </div>
            </div>
            
            <div id="bungkus_formulir_online"> 
                
                <div class="grup-formulir">
                    <h3>1. Informasi Kontak</h3>
                    <div class="baris-input">
                        <div>
                            <label>Nama Depan</label>
                            <input type="text" value="<?= htmlspecialchars($nama_depan); ?>" disabled>
                        </div>
                        <div>
                            <label>Nama Belakang</label>
                            <input type="text" value="<?= htmlspecialchars($nama_belakang); ?>" disabled>
                        </div>
                    </div>
                    <div class="baris-input">
                        <div>
                            <label>No. WhatsApp</label>
                            <input type="text" name="telepon_whatsapp" value="<?= htmlspecialchars($data_user['no_hp']); ?>" placeholder="Contoh: 0812xxxx" required>
                        </div>
                    </div>
                </div>

                <div class="grup-formulir">
                    <h3>2. Detail Pengiriman</h3>
                    
                    <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #90caf9;">
                        <label style="color: #1565c0; font-weight:bold;">📍 Cek Jarak Pengiriman</label>
                        <p style="font-size: 12px; margin-bottom: 10px; color: #555;">
                            Wajib cek lokasi untuk menentukan ongkir. Maksimal jarak pengiriman adalah <strong>5 KM</strong>.
                        </p>
                        <button type="button" onclick="cekLokasi()" class="tombol-cek-lokasi" style="background: #1976d2; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-size: 13px;">
                            <i class="fas fa-map-marked-alt"></i> Cek Lokasi Saya
                        </button>
                        <p id="hasil_cek_lokasi" style="margin-top: 10px; font-size: 13px; font-weight: 600;"></p>
                    </div>
                    <label>Pilih Alamat Pengiriman</label>
                    <div class="ganti-opsi">
                        <button type="button" id="tombol_alamat_terdaftar" onclick="gantiAlamat(true)"><i class="fas fa-home"></i> Alamat Terdaftar</button>
                        <button type="button" id="tombol_alamat_baru" onclick="gantiAlamat(false)"><i class="fas fa-map-marker-alt"></i> Alamat Baru</button>
                    </div>
                    
                    <div class="baris-penuh" style="margin-top: 15px;">
                        <label>Alamat Lengkap</label>
                        <textarea id="input_alamat_delivery" name="input_alamat_delivery" required></textarea>
                    </div>
                    
                    <div class="baris-penuh" style="margin-top: 15px;">
                        <label>Catatan (Opsional)</label>
                        <input type="text" name="catatan" placeholder="Contoh: Jangan terlalu pedas, antar ke pintu belakang.">
                    </div>
                </div>
            </div> 
            
            <div id="bungkus_formulir_offline" style="display: none;">
                <div class="grup-formulir">
                    <h3>1. Detail Pemesan (Dine-in)</h3>
                    
                    <div class="baris-input">
                        <div>
                            <label>Nama Pemesan</label>
                            <input type="text" name="nama_pemesan_offline" id="nama_pemesan_offline" value="<?= htmlspecialchars($nama_depan . ' ' . $nama_belakang); ?>">
                        </div>
                        <div>
                            <label>Nomor Meja</label>
                            <select name="nomor_meja" id="nomor_meja">
                                <?php 
                                $jumlah_meja = 20; 
                                for ($i = 1; $i <= $jumlah_meja; $i++): 
                                ?>
                                    <option value="<?= $i; ?>">Meja <?= $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="baris-penuh" style="margin-top: 15px;">
                        <label>No. WhatsApp (Opsional untuk Dine-in)</label>
                        <input type="text" name="telepon_whatsapp_offline" value="<?= htmlspecialchars($data_user['no_hp']); ?>">
                    </div>

                    <div class="baris-penuh" style="margin-top: 15px;">
                        <label>Catatan (Opsional)</label>
                        <input type="text" name="catatan_offline" placeholder="Contoh: Jangan terlalu pedas">
                    </div>

                </div>
            </div>
            
            <div class="grup-formulir">
                <h3>3. Metode Pembayaran</h3>
                <label>Metode Pembayaran</label>
                <div class="ganti-opsi">
                    <button type="button" id="tampilan_metode_bayar" class="active" style="flex: none; padding: 10px 20px;">
                        <i class="fas fa-money-bill-wave"></i> Bayar di Tempat (COD)
                    </button>
                </div>
            </div>
            
        </div>

        <div class="area-ringkasan">
            <h2>Ringkasan Pesanan</h2>
            
            <div class="item-pesanan">
                <img src="../assets/img/<?= htmlspecialchars($data_menu['foto'] ?? 'default.jpg'); ?>" alt="<?= htmlspecialchars($data_menu['nama_menu']); ?>" class="gambar-item">
                <div>
                    <strong><?= htmlspecialchars($data_menu['nama_menu']); ?></strong>
                    <div style="color: var(--warna-primer);">Rp<?= number_format($harga_satuan, 0, ',', '.'); ?> x <?= $jumlah; ?></div>
                </div>
            </div>
            
            <div class="baris-total">
                <span>Subtotal</span>
                <span id="tampilan_subtotal">Rp<?= number_format($subtotal, 0, ',', '.'); ?></span>
            </div>
            <div class="baris-total">
                <span>Ongkos Kirim</span>
                <span id="tampilan_ongkir">Rp<?= number_format($ongkir_awal, 0, ',', '.'); ?></span>
            </div>
            <div class="baris-total total">
                <span>TOTAL</span>
                <span id="tampilan_total">Rp<?= number_format($total, 0, ',', '.'); ?></span>
            </div>
            
            <input type="hidden" name="ongkir_final" id="input_ongkir_final" value="<?= $ongkir_awal; ?>">
            <input type="hidden" name="total_final" id="input_total_final" value="<?= $total; ?>">
            <input type="hidden" name="id_menu" value="<?= $id_menu; ?>">
            <input type="hidden" name="alamat_delivery_final" id="input_alamat_final" value="<?= htmlspecialchars($data_user['alamat']); ?>">
            <input type="hidden" name="mode_pemesanan" id="mode_pemesanan" value="online">

            <button type="submit" class="tombol-checkout" id="tombol_submit_checkout">
                Checkout & Pesan
            </button>

            <p style="text-align: center; font-size: 11px; margin-top: 15px; color: #666;">
                Dengan menekan tombol Checkout, Anda menyetujui syarat dan ketentuan yang berlaku.
            </p>
        </div>
        
    </div>
</form>

<script>
    // === KONFIGURASI RESTORAN ===
    const LAT_RESTORAN = -7.185874778722931; // Koordinat Baru
    const LNG_RESTORAN = 108.3629497689564; 

    // Data PHP
    const HARGA_SATUAN = <?= $harga_satuan; ?>;
    const JUMLAH = <?= $jumlah; ?>; 
    const ALAMAT_TERDAFTAR = "<?= addslashes($data_user['alamat']); ?>";
    
    // Variabel Global
    let ongkirSaatIni = 0;
    let jarakKm = 0;
    let sudahCekLokasi = false;

    // Format Rupiah
    const formatRupiah = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });

    // --- 1. FUNGSI GEOLOKASI ---
    function cekLokasi() {
        const statusTxt = document.getElementById('hasil_cek_lokasi');
        statusTxt.innerHTML = "Sedang melacak lokasi... <i class='fas fa-spinner fa-spin'></i>";
        statusTxt.style.color = "#666";

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(suksesLokasi, errorLokasi);
        } else {
            statusTxt.innerHTML = "Browser Anda tidak mendukung Geolocation.";
        }
    }

    function suksesLokasi(position) {
        const userLat = position.coords.latitude;
        const userLng = position.coords.longitude;

        // Hitung Jarak dengan Rumus Haversine
        jarakKm = hitungJarak(LAT_RESTORAN, LNG_RESTORAN, userLat, userLng);
        
        // --- LOGIKA ONGKIR SESUAI REQUEST ---
        let pesan = "";
        let warna = "green";
        let valid = true;

        if (jarakKm > 5) {
            ongkirSaatIni = 0;
            pesan = `Jarak: ${jarakKm.toFixed(2)} KM. Maaf, lokasi Anda terlalu jauh (> 5 KM). Tidak bisa pesan delivery.`;
            warna = "red";
            valid = false;
        } else if (jarakKm <= 1) {
            ongkirSaatIni = 0;
            pesan = `Jarak: ${jarakKm.toFixed(2)} KM. Dekat sekali! Ongkir GRATIS.`;
        } else if (jarakKm <= 2) {
            ongkirSaatIni = 3000;
            pesan = `Jarak: ${jarakKm.toFixed(2)} KM. Ongkir: Rp3.000`;
        } else if (jarakKm <= 3) {
            ongkirSaatIni = 5000;
            pesan = `Jarak: ${jarakKm.toFixed(2)} KM. Ongkir: Rp5.000`;
        } else if (jarakKm <= 4) {
            ongkirSaatIni = 7000;
            pesan = `Jarak: ${jarakKm.toFixed(2)} KM. Ongkir: Rp7.000`;
        } else { // Jarak 4.xx sampai 5 KM
            ongkirSaatIni = 10000;
            pesan = `Jarak: ${jarakKm.toFixed(2)} KM. Ongkir: Rp10.000`;
        }

        // Update Tampilan Status
        const statusTxt = document.getElementById('hasil_cek_lokasi');
        statusTxt.innerHTML = pesan;
        statusTxt.style.color = warna;
        
        // Simpan status validasi
        sudahCekLokasi = valid;

        // Update Harga
        perbaruiOngkirDanTotal();
        updateTombolCheckout();
    }

    function errorLokasi() {
        document.getElementById('hasil_cek_lokasi').innerHTML = "Gagal mengambil lokasi. Pastikan GPS aktif dan izinkan akses lokasi.";
        sudahCekLokasi = false;
        updateTombolCheckout();
    }

    // Rumus Matematika Menghitung Jarak (Haversine Formula)
    function hitungJarak(lat1, lon1, lat2, lon2) {
        const R = 6371; // Radius bumi dalam KM
        const dLat = (lat2 - lat1) * (Math.PI / 180);
        const dLon = (lon2 - lon1) * (Math.PI / 180);
        const a = 
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * (Math.PI / 180)) * Math.cos(lat2 * (Math.PI / 180)) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c; // Hasil dalam KM
    }

    // --- 2. FUNGSI UPDATE UI ---
    function perbaruiOngkirDanTotal() {
        // Jika mode offline, ongkir selalu 0
        if (document.getElementById('mode_pemesanan').value === 'offline') {
            ongkirSaatIni = 0;
        }

        const subtotal = HARGA_SATUAN * JUMLAH;
        const total = subtotal + ongkirSaatIni;
        
        const ongkirText = ongkirSaatIni === 0 ? 'Gratis' : 'Rp' + formatRupiah.format(ongkirSaatIni);

        document.getElementById('tampilan_ongkir').innerText = ongkirText;
        document.getElementById('tampilan_subtotal').innerText = 'Rp' + formatRupiah.format(subtotal);
        document.getElementById('tampilan_total').innerText = 'Rp' + formatRupiah.format(total);
        
        // Update input hidden untuk dikirim ke database
        document.getElementById('input_ongkir_final').value = ongkirSaatIni;
        document.getElementById('input_total_final').value = total;
        
        // Ambil nilai alamat dari textarea (jika ada perubahan manual)
        const alamatInput = document.getElementById('input_alamat_delivery');
        if(alamatInput) {
             document.getElementById('input_alamat_final').value = alamatInput.value;
        }
    }

    function updateTombolCheckout() {
        const mode = document.getElementById('mode_pemesanan').value;
        const btn = document.getElementById('tombol_submit_checkout');

        if (mode === 'online') {
            // Syarat: Harus sudah cek lokasi DAN lokasi valid (<= 5km)
            if (sudahCekLokasi) {
                btn.disabled = false;
                btn.style.backgroundColor = "var(--warna-primer)";
                btn.innerText = "Checkout & Pesan";
            } else {
                btn.disabled = true;
                btn.style.backgroundColor = "#ccc";
                btn.innerText = "Cek Lokasi Dulu / Jarak Terlalu Jauh";
            }
        } else {
            // Kalau Offline/Dine-in selalu boleh
            btn.disabled = false;
            btn.style.backgroundColor = "var(--warna-primer)";
            btn.innerText = "Checkout & Pesan";
        }
    }

    function gantiAlamat(gunakanTerdaftar) {
        if (document.getElementById('mode_pemesanan').value !== 'online') return;

        const inputAlamatEl = document.getElementById('input_alamat_delivery');
        const tombolTerdaftar = document.getElementById('tombol_alamat_terdaftar');
        const tombolBaru = document.getElementById('tombol_alamat_baru');

        if (gunakanTerdaftar) {
            inputAlamatEl.value = ALAMAT_TERDAFTAR;
            // inputAlamatEl.disabled = true; 
            tombolTerdaftar.classList.add('active');
            tombolBaru.classList.remove('active');
        } else {
            inputAlamatEl.value = ''; 
            inputAlamatEl.disabled = false;
            tombolTerdaftar.classList.remove('active');
            tombolBaru.classList.add('active');
        }
        // Update hidden input alamat
        document.getElementById('input_alamat_final').value = inputAlamatEl.value;
    }

    function gantiModePemesanan(mode) {
        const formOnline = document.getElementById('bungkus_formulir_online');
        const formOffline = document.getElementById('bungkus_formulir_offline');
        const btnOnline = document.getElementById('tombol_mode_online');
        const btnOffline = document.getElementById('tombol_mode_offline');
        const tampilanBayar = document.getElementById('tampilan_metode_bayar');
        const inputModeTersembunyi = document.getElementById('mode_pemesanan');

        if (mode === 'offline') {
            formOnline.style.display = 'none';
            formOffline.style.display = 'block';
            btnOnline.classList.remove('active');
            btnOffline.classList.add('active');
            tampilanBayar.innerHTML = '<i class="fas fa-cash-register"></i> Bayar di Kasir';
            inputModeTersembunyi.value = 'offline';

            document.querySelector('input[name="telepon_whatsapp"]').required = false;
            document.querySelector('textarea[name="input_alamat_delivery"]').required = false;

            // Reset ongkir jadi 0
            ongkirSaatIni = 0;
            perbaruiOngkirDanTotal();
            updateTombolCheckout(); // Selalu aktif di mode offline

        } else { // Mode 'online'
            formOnline.style.display = 'block';
            formOffline.style.display = 'none';
            btnOnline.classList.add('active');
            btnOffline.classList.remove('active');
            tampilanBayar.innerHTML = '<i class="fas fa-money-bill-wave"></i> Bayar di Tempat (COD)';
            inputModeTersembunyi.value = 'online';

            document.querySelector('input[name="telepon_whatsapp"]').required = true;
            document.querySelector('textarea[name="input_alamat_delivery"]').required = true;

            // Cek ulang tombol checkout (mungkin user belum cek lokasi)
            updateTombolCheckout();
            perbaruiOngkirDanTotal();
        }
    }

    // --- INISIALISASI SAAT LOAD ---
    document.addEventListener('DOMContentLoaded', function() {
        gantiAlamat(true);
        
        // Listener jika user mengetik alamat manual
        document.getElementById('input_alamat_delivery').addEventListener('input', function() {
            document.getElementById('input_alamat_final').value = this.value;
        });
        
        // Panggil ini agar tombol checkout disable di awal (sebelum cek lokasi)
        updateTombolCheckout();
    });
</script>
</body>
</html>