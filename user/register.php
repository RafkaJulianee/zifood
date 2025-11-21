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
    <link rel="shortcut icon" href="img/zifood.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS DARI DESIGN LOGIN (INDEX.PHP) */
        :root {
            --theme-color: #FF5722;
            --text-on-image-color: #FFFFFF;
            --text-shadow-color: rgba(0, 0, 0, 0.7);
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif; 
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f0f0f0;
            padding: 20px;
        }

        .main-container {
            display: flex;
            width: 90vw; 
            height: 90vh; 
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            min-width: 700px;
            min-height: 550px;
        }

        /* SISI KIRI: FORMULIR */
        .login-side {
            flex: 1; 
            padding: 40px 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .welcome-text {
            font-size: 32px;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .tagline {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .input-field {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px; 
            box-sizing: border-box;
            font-family: inherit;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background-color: #ffa07a; 
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px; 
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        /* FOOTER LOGIN LINK */
        .register-info {
            position: static;
            text-align: center;
            width: 100%;
            margin-top: 20px;
            margin-bottom: 0;
            font-size: 14px;
        }

        .register-link {
            color: var(--theme-color); 
            font-weight: bold;
            text-decoration: none;
        }

        /* SISI KANAN: ILUSTRASI */
        .illustration-side {
            flex: 1; 
            background-color: #F4F6F4; 
            position: relative; 
            overflow: hidden; 
            padding: 0; 
        }
        
        .illustration-img {
            width: 100%;
            height: 100%;
            object-fit: cover; 
            position: relative; 
        }

        .marketing-text {
            position: absolute; 
            bottom: 0; 
            right: 0; 
            padding: 20px 25px; 
            background-color: rgba(0, 0, 0, 0.75); 
            border-top-left-radius: 10px; 
            border-bottom-right-radius: 20px; 
            text-align: right; 
            max-width: 100%;
            align-self: flex-end;
            justify-self: flex-end;
            z-index: 2;
        }

        .marketing-text h3 {
            color: var(--text-on-image-color); 
            font-size: 18px;
            margin: 0;
            font-weight: 600;
            text-shadow: 0 0 5px var(--text-shadow-color); 
        }
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