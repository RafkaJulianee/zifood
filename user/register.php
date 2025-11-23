<?php
include '../koneksi.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    // Ambil data alamat dari POST request
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);

    // Cek apakah username sudah dipakai
    $cek = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>alert('Username sudah digunakan!');window.location='register.php';</script>";
    } else {
        if (strlen($password) < 8) {
            echo "<script>alert('Password minimal 8 karakter!');window.location='register.php';</script>";
            exit;
        }

        // --- FIX: Menghapus MD5() agar password disimpan sebagai teks biasa ---
        $password_hash = $password; 
        
        // Query INSERT untuk memasukkan kolom 'alamat'
        $query = "INSERT INTO users (username, password, nama, alamat)
                  VALUES ('$username', '$password_hash', '$nama', '$alamat')";
                  
        if (mysqli_query($conn, $query)) {
            // Arahkan kembali ke index.php (login) setelah berhasil
            echo "<script>alert('Registrasi berhasil! Silakan login.');window.location='../index.php';</script>";
        } else {
            echo "<script>alert('Registrasi gagal! " . mysqli_error($conn) . "');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Akun - Zifood</title>
    <link rel="stylesheet" href="CSS/register.css">
    <link rel="shortcut icon" href="img/zifood.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
       
    </style>
    <script>
        function validateForm() {
            const password = document.getElementById("password").value;

            if (password.length < 8) {
                alert("Password harus minimal 8 karakter!");
                return false; 
            }
            return true;
        }
    </script>
</head>
<body>
    <div class="main-container">
        <div class="login-side">
            <h1 class="welcome-text">Create Account</h1>
            <p class="tagline">Join ZIFOOD now to start ordering your favorite meals.</p>

            <form method="POST" action="" onsubmit="return validateForm()">
                <input type="text" name="nama" placeholder="Nama Lengkap" required class="input-field">

                <input type="text" name="username" placeholder="Username" required class="input-field">

                <input type="text" name="alamat" placeholder="Alamat Lengkap" required class="input-field"> 

                <input type="password" name="password" id="password" placeholder="Password (min. 8 characters)" required class="input-field">
                
                <button type="submit" class="login-btn">Daftar</button>
            </form>
            
            <p class="register-info">Sudah punya akun? <a href="../index.php" class="register-link">Login</a></p>
        </div>

        <div class="illustration-side">
            <img src="img/foodimg1.jpeg" alt="Delicious Food" class="illustration-img"> 
            
            <div class="marketing-text">
                <h3>Make your ordering easier and organized with ZIFOOD</h3>
            </div>
        </div>
    </div>
</body>
</html>