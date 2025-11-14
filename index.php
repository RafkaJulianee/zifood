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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Mendefinisikan warna tema */
        :root {
            --theme-color: #FF5722; /* Merah-Oranye */
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

        .forgot-password {
            display: block;
            text-align: right;
            color: var(--theme-color); 
            text-decoration: none;
            font-size: 13px;
            margin-top: -10px;
            margin-bottom: 30px; 
            font-weight: 500;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background-color: black; 
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        /* FOOTER REGISTER LINK */
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
</head>
<body>
    <div class="main-container">
        <div class="login-side">
            <h1 class="welcome-text">Welcome back!</h1>
            <p class="tagline">Simplify your workflow and boost your productivity with Zifood App. Get started for free.</p>

            <form method="POST" action="" onsubmit="return validateLogin()">
                <input type="text" name="username" id="username" placeholder="Username" required class="input-field"><br>

                <input type="password" name="password" id="password" placeholder="Password" required class="input-field">
                
                <a href="#" class="forgot-password">Forgot Password?</a>
                
                <button type="submit" class="login-btn">Login</button>
            </form>
            
            <p class="register-info">Not a member? <a href="user/register.php" class="register-link">Register now</a></p>
        </div>

        <div class="illustration-side">
            <img src="user/img/foodimg1.jpeg" alt="Delicious Food" class="illustration-img"> 
            
            <div class="marketing-text">
                <h3>Make your ordering easier and organized with ZIFOOD</h3>
            </div>
        </div>
    </div>
</body>
</html>