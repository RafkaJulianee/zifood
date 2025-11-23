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
        
        // Untuk Online, nomor meja diisi 0 atau '-'
        $nomor_meja_db = '0'; 

        // Update data user jika berubah
        if ($nohp_final != $data_user['no_hp']) {
             mysqli_query($conn, "UPDATE users SET no_hp='$nohp_final' WHERE id_user='$id_user'");
        }
        mysqli_query($conn, "UPDATE users SET alamat='$alamat_final', no_hp='$nohp_final' WHERE id_user='$id_user'");

        // [PERBAIKAN 1] Menambahkan kolom 'meja' ke query Online agar konsisten dengan database
        $kueri_insert = "INSERT INTO pesanan (id_user, id_menu, jumlah, alamat, catatan, metode, ongkir, total, status, meja)
                         VALUES ('$id_user', '$id_menu', '$jumlah', '$alamat_final', '$catatan_final', '$metode_bayar', '$ongkir_final', '$total_final', '$status_pesan', '$nomor_meja_db')";
    
    } else {
        // --- LOGIKA OFFLINE (DINE-IN) ---
        $nama_pemesan_offline = mysqli_real_escape_string($conn, $_POST['nama_pemesan_offline']);
        $nomor_meja = mysqli_real_escape_string($conn, $_POST['nomor_meja']); // Ambil nomor meja
        $nohp_final_offline = mysqli_real_escape_string($conn, $_POST['telepon_whatsapp_offline']);
        $catatan_final_offline = mysqli_real_escape_string($conn, $_POST['catatan_offline']);
        
        $alamat_dine_in = "Dine-in: $nama_pemesan_offline";
        $catatan_dine_in = $catatan_final_offline;
        
        $ongkir_final = 0;
        $total_final = (int)($subtotal);
        $metode_bayar = 'Bayar di Kasir';
        $status_pesan = 'Menunggu';

        // [PERBAIKAN 2] Memastikan format query Offline benar (Baris 80 yang error sebelumnya)
        $kueri_insert = "INSERT INTO pesanan (id_user, id_menu, jumlah, alamat, catatan, metode, ongkir, total, status, meja)
                         VALUES ('$id_user', '$id_menu', '$jumlah', '$alamat_dine_in', '$catatan_dine_in', '$metode_bayar', '$ongkir_final', '$total_final', '$status_pesan', '$nomor_meja')";
    }

    // Eksekusi query
    if (mysqli_query($conn, $kueri_insert)) {
        echo "<script>alert('Pesanan berhasil dibuat!'); window.location='order.php';</script>";
    } else {
        // Tampilkan error database spesifik untuk debugging
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
       
    </style>

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
                    <label>Pilih Alamat Pengiriman (Maksimum 5 km)</label>
                    <div class="ganti-opsi">
                        <button type="button" id="tombol_alamat_terdaftar" onclick="gantiAlamat(true)"><i class="fas fa-home"></i> Alamat Terdaftar</button>
                        <button type="button" id="tombol_alamat_baru" onclick="gantiAlamat(false)"><i class="fas fa-map-marker-alt"></i> Alamat Baru</button>
                    </div>
                    
                    <div class="baris-penuh" style="margin-top: 15px;">
                        <label>Alamat Lengkap</label>
                        <textarea id="input_alamat_delivery" name="input_alamat_delivery" required></textarea>
                    </div>

                    <div class="baris-input" style="margin-top: 15px; align-items: flex-end;">
                        <div class="form-control" style="flex: 1;">
                            <label>Hitung Ongkos Kirim (Otomatis)</label>
                            <button type="button" id="tombol_cek_lokasi" class="tombol-cek-lokasi" onclick="dapatkanLokasiDanHitung()">
                                <i class="fas fa-map-marker-alt"></i> Cek Lokasi Saya
                            </button>
                        </div>
                        <div class="form-control" style="flex: 2;">
                            <label>Status Pengiriman</label>
                            <strong id="status_pengiriman" style="font-size: 14px; display: block; padding: 10px 0;">Silakan cek lokasi Anda...</strong>
                        </div>
                    </div>
                    <input type="hidden" id="input_jarak" value="0">
                    
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
        // Data PHP
        const HARGA_SATUAN = <?= $harga_satuan; ?>;
        const JUMLAH = <?= $jumlah; ?>; 
        const ALAMAT_TERDAFTAR = "<?= addslashes($data_user['alamat']); ?>";
        
        // --- LOKASI RESTORAN ---
        const RESTO_LAT = -7.186083988588994;
        const RESTO_LON = 108.36223957166722;
        
        // Format Rupiah
        const formatRupiah = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });

        // Logika Perhitungan Ongkir
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

        function perbaruiOngkirDanTotal() {
            // Hanya jalankan jika mode 'online'
            if (document.getElementById('mode_pemesanan').value !== 'online') return; 

            const subtotal = HARGA_SATUAN * JUMLAH;
            
            // Ambil Jarak dari input hidden
            const inputJarak = document.getElementById('input_jarak').value;
            const jarak = parseFloat(inputJarak) || 0;

            let ongkir = hitungOngkir(jarak);
            let total = subtotal;
            let teksStatus = document.getElementById('status_pengiriman');

            if (ongkir === -1) {
                // Melebihi 5 km
                teksStatus.innerHTML = `Jarak ${jarak.toFixed(2)} km. <span style="color:red; font-weight: bold;">Melebihi Batas (5km)</span>`;
                document.getElementById('tombol_submit_checkout').disabled = true;
                
                ongkir = 0; 
                document.getElementById('tampilan_ongkir').innerText = 'N/A';
            } else {
                total = subtotal + ongkir;
                let ongkirText = ongkir === 0 ? 'Gratis' : 'Rp' + formatRupiah.format(ongkir);
                
                teksStatus.innerHTML = `Jarak ${jarak.toFixed(2)} km. <span style="color: var(--warna-sekunder); font-weight: bold;">${ongkirText}</span>`;
                document.getElementById('tombol_submit_checkout').disabled = false;
                
                document.getElementById('tampilan_ongkir').innerText = ongkirText;
            }
            
            document.getElementById('tampilan_subtotal').innerText = 'Rp' + formatRupiah.format(subtotal);
            document.getElementById('tampilan_total').innerText = 'Rp' + formatRupiah.format(total);
            document.getElementById('input_ongkir_final').value = ongkir;
            document.getElementById('input_total_final').value = total;
            document.getElementById('input_alamat_final').value = document.getElementById('input_alamat_delivery').value;
        }

        function gantiAlamat(gunakanTerdaftar) {
            if (document.getElementById('mode_pemesanan').value !== 'online') return;

            const inputAlamatEl = document.getElementById('input_alamat_delivery');
            const tombolTerdaftar = document.getElementById('tombol_alamat_terdaftar');
            const tombolBaru = document.getElementById('tombol_alamat_baru');

            if (gunakanTerdaftar) {
                inputAlamatEl.value = ALAMAT_TERDAFTAR;
                inputAlamatEl.disabled = true;
                tombolTerdaftar.classList.add('active');
                tombolBaru.classList.remove('active');
            } else {
                inputAlamatEl.value = ''; // Kosongkan untuk input baru
                inputAlamatEl.disabled = false;
                tombolTerdaftar.classList.remove('active');
                tombolBaru.classList.add('active');
            }
            // Reset ongkir saat ganti alamat, paksa user hitung ulang
            document.getElementById('input_jarak').value = 0;
            document.getElementById('status_pengiriman').innerHTML = 'Silakan cek lokasi Anda...';
            perbaruiOngkirDanTotal();
        }

        function gantiModePemesanan(mode) {
            const formOnline = document.getElementById('bungkus_formulir_online');
            const formOffline = document.getElementById('bungkus_formulir_offline');
            const btnOnline = document.getElementById('tombol_mode_online');
            const btnOffline = document.getElementById('tombol_mode_offline');
            const tampilanBayar = document.getElementById('tampilan_metode_bayar');
            const inputModeTersembunyi = document.getElementById('mode_pemesanan');

            const subtotal = HARGA_SATUAN * JUMLAH;

            if (mode === 'offline') {
                formOnline.style.display = 'none';
                formOffline.style.display = 'block';
                btnOnline.classList.remove('active');
                btnOffline.classList.add('active');
                tampilanBayar.innerHTML = '<i class="fas fa-cash-register"></i> Bayar di Kasir';
                inputModeTersembunyi.value = 'offline';

                // Set ongkir dan total untuk mode OFFLINE
                document.getElementById('tampilan_ongkir').innerText = 'Rp0';
                document.getElementById('tampilan_total').innerText = 'Rp' + formatRupiah.format(subtotal);
                document.getElementById('input_ongkir_final').value = 0;
                document.getElementById('input_total_final').value = subtotal;
                document.getElementById('tombol_submit_checkout').disabled = false;
                
                // Non-aktifkan input online yang 'required'
                document.querySelector('input[name="telepon_whatsapp"]').required = false;
                document.querySelector('textarea[name="input_alamat_delivery"]').required = false;

            } else { // Mode 'online'
                formOnline.style.display = 'block';
                formOffline.style.display = 'none';
                btnOnline.classList.add('active');
                btnOffline.classList.remove('active');
                tampilanBayar.innerHTML = '<i class="fas fa-money-bill-wave"></i> Bayar di Tempat (COD)';
                inputModeTersembunyi.value = 'online';

                // Aktifkan kembali input online yang 'required'
                document.querySelector('input[name="telepon_whatsapp"]').required = true;
                document.querySelector('textarea[name="input_alamat_delivery"]').required = true;

                // Panggil fungsi update ongkir (akan me-reset ke 0)
                perbaruiOngkirDanTotal(); 
            }
        }

        function dapatkanLokasiDanHitung() {
            const teksStatus = document.getElementById('status_pengiriman');
            const tombolGeo = document.getElementById('tombol_cek_lokasi');

            teksStatus.innerHTML = '<span style="color:#aaa;">Mendapatkan lokasi Anda...</span>';
            tombolGeo.disabled = true;

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(tampilkanPosisi, tampilkanError);
            } else {
                teksStatus.innerHTML = '<span style="color:red;">Geolocation tidak didukung browser ini.</span>';
                tombolGeo.disabled = false;
            }
        }

        function tampilkanPosisi(position) {
            const userLat = position.coords.latitude;
            const userLon = position.coords.longitude;
            const tombolGeo = document.getElementById('tombol_cek_lokasi');

            // Hitung jarak
            const jarakKm = hitungJarak(userLat, userLon, RESTO_LAT, RESTO_LON);

            // Masukkan ke input hidden
            document.getElementById('input_jarak').value = jarakKm.toFixed(2);
            
            // Aktifkan tombol lagi
            tombolGeo.disabled = false;
            
            // Panggil update ongkir & total
            perbaruiOngkirDanTotal(); 
        }

        function tampilkanError(error) {
            const teksStatus = document.getElementById('status_pengiriman');
            const tombolGeo = document.getElementById('tombol_cek_lokasi'); 
            tombolGeo.disabled = false;

            switch(error.code) {
                case error.PERMISSION_DENIED:
                    teksStatus.innerHTML = '<span style="color:red;">Anda menolak izin lokasi.</span>';
                    break;
                case error.POSITION_UNAVAILABLE:
                    teksStatus.innerHTML = '<span style="color:red;">Informasi lokasi tidak tersedia.</span>';
                    break;
                case error.TIMEOUT:
                    teksStatus.innerHTML = '<span style="color:red;">Waktu permintaan lokasi habis.</span>';
                    break;
                case error.UNKNOWN_ERROR:
                    teksStatus.innerHTML = '<span style="color:red;">Terjadi kesalahan.</span>';
                    break;
            }
        }

        function hitungJarak(lat1, lon1, lat2, lon2) {
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

        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi: Gunakan alamat terdaftar
            gantiAlamat(true);
            
            // Event listener untuk input alamat baru
            document.getElementById('input_alamat_delivery').addEventListener('input', function() {
                // Saat alamat diubah manual, reset ongkir
                document.getElementById('input_jarak').value = 0;
                document.getElementById('status_pengiriman').innerHTML = 'Silakan cek lokasi Anda...';
                perbaruiOngkirDanTotal();
            });
            
            // Perbarui total saat page load (akan 0 ongkir)
            perbaruiOngkirDanTotal();
        });
    </script>
</body>
</html>