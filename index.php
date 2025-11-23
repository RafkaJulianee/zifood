<?php
// Path ke koneksi.php (diasumsikan berada di folder yang sama)
include 'koneksi.php'; 
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    
    // --- FIX: KITA HAPUS FUNGSI MD5() ---
    // Kita ambil password mentah (plain text) langsung dari form
    $password_plain = mysqli_real_escape_string($conn, $_POST['password']);

    // 1. CEK KE TABEL ADMIN (Menggunakan password TEKS BIASA)
    $query_admin = "SELECT * FROM admin WHERE username='$username' AND password='$password_plain'";
    $result_admin = mysqli_query($conn, $query_admin);

    if (mysqli_num_rows($result_admin) === 1) {
        // --- LOGIN SEBAGAI ADMIN ---
        $data_admin = mysqli_fetch_assoc($result_admin);
        
        $_SESSION['id_admin'] = $data_admin['id_admin'];
        $_SESSION['username_admin'] = $data_admin['username'];
        
        // Arahkan ke dashboard ADMIN
        header("Location: admin/dashboard.admin.php"); 
        exit;

    } else {
        // 2. CEK KE TABEL USERS (Juga menggunakan password TEKS BIASA)
        $query_user = "SELECT * FROM users WHERE username='$username' AND password='$password_plain'";
        $result_user = mysqli_query($conn, $query_user);

        if (mysqli_num_rows($result_user) === 1) {
            // --- LOGIN SEBAGAI USER ---
            $data_user = mysqli_fetch_assoc($result_user);
            
            $_SESSION['id_user'] = $data_user['id_user'];
            $_SESSION['username'] = $data_user['username'];
            
            // Arahkan ke dashboard USER
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
    <style>
       
    </style>
</head>
<body>
    <div class="main-container">
        <div class="login-side">
            <h1 class="welcome-text">Selamat Datang Kembali</h1>
            <p class="tagline">Zifood Pesan makanan favoritmu dengan cepat, mudah, dan tanpa ribet.</p>

            <form method="POST" action="" onsubmit="return validateLogin()">
                <input type="text" name="username" id="username" placeholder="Username" required class="input-field"><br>

                <input type="password" name="password" id="password" placeholder="Password" required class="input-field">
               
                
                <button type="submit" class="login-btn">Login</button>
            </form>
            
            <p class="register-info">Belum Punya Akun? <a href="user/register.php" class="register-link">Registrasi Sekarang</a></p>
        </div>

        <div class="illustration-side">
            <img src="user/img/foodimg1.jpeg" alt="Delicious Food" class="illustration-img"> 
            
            <div class="marketing-text">
                <h3>Make your ordering easier and organized with ZIFOOD</h3>
            </div>
        </div>
    </div>
       <script>
        function validateLogin() {
            const user = document.getElementById("username").value.trim();
            const pass = document.getElementById("password").value.trim();

            if (user === "" || pass === "") {
                alert("Username dan Password tidak boleh kosong!");
                return false;
            }
            if (pass.length < 8) {
                alert("Password harus minimal 8 karakter!");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>