<?php

include 'koneksi.php'; 
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama_pengguna = mysqli_real_escape_string($conn, $_POST['username']);
    $kata_sandi    = mysqli_real_escape_string($conn, $_POST['password']); 

    // 1. CEK KE TABEL ADMIN
    $kueri_admin = "SELECT * FROM admin WHERE username='$nama_pengguna' AND password='$kata_sandi'";
    $hasil_admin = mysqli_query($conn, $kueri_admin);

    if (mysqli_num_rows($hasil_admin) === 1) {
        // --- LOGIN SEBAGAI ADMIN ---
        $data_admin = mysqli_fetch_assoc($hasil_admin);
        
        $_SESSION['id_admin']       = $data_admin['id_admin'];
        $_SESSION['username_admin'] = $data_admin['username'];
        
        header("Location: admin/dashboard.admin.php"); 
        exit;

    } else {
        // 2. CEK KE TABEL USERS
        $kueri_pengguna = "SELECT * FROM users WHERE username='$nama_pengguna' AND password='$kata_sandi'";
        $hasil_pengguna = mysqli_query($conn, $kueri_pengguna);

        if (mysqli_num_rows($hasil_pengguna) === 1) {
            // --- LOGIN SEBAGAI USER ---
            $data_pengguna = mysqli_fetch_assoc($hasil_pengguna);
            
            $_SESSION['id_user']  = $data_pengguna['id_user'];
            $_SESSION['username'] = $data_pengguna['username'];
            
            header("Location: user/dashboard.php"); 
            exit;
        } else {
            // 3. GAGAL LOGIN
            echo "<script>alert('Username atau password salah!');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Zifood</title>
    <link rel="stylesheet" href="user/CSS/index.css">
    <link rel="shortcut icon" href="user/img/zifood.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="main-container">
        <div class="login-side">
            <h1 class="welcome-text">Selamat Datang Kembali</h1>
            <p class="tagline">Zifood Pesan makanan favoritmu dengan cepat, mudah, dan tanpa ribet.</p>

            <form method="POST" action="" onsubmit="return validasiLogin()">
                <input type="text" name="username" id="username" placeholder="Username" required class="input-field"><br>
                <input type="password" name="password" id="password" placeholder="Password" required class="input-field">
                
                <button type="submit" class="login-btn">Login</button>
            </form>
            
            <p class="register-info">Belum Punya Akun? <a href="user/register.php" class="register-link">Registrasi Sekarang</a></p>
        </div>

        <div class="illustration-side">
            <img src="user/img/foodimg1.jpeg" alt="Delicious Food" class="illustration-img"> 
          
        </div>
    </div>

    <script>
        // Mengubah nama fungsi dan variabel script ke Bahasa Indonesia
        function validasiLogin() {
            const namaPengguna = document.getElementById("username").value.trim();
            const kataSandi    = document.getElementById("password").value.trim();

            if (namaPengguna === "" || kataSandi === "") {
                alert("Username dan Password tidak boleh kosong!");
                return false;
            }
            if (kataSandi.length < 8) {
                alert("Password harus minimal 8 karakter!");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>